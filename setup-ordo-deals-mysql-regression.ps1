$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ExpectedIdentity = 'LAPTOP-QCB457BV\PRECIOUS D. DIGNOS'
$ServerHost = '127.0.0.1'
$ServerPort = 13321
$ExpectedVersion = '8.4.13'
$ExpectedDataDirectory = 'C:\ORDO-Restore-20261109\mysql-data'
$RootCredentialPath = 'C:\ORDO-Restore-20261109\run\mysql-root-credential.xml'
$TestCredentialPath = 'C:\ORDO-Restore-20261109\run\deals-regression-credential.xml'
$Mysql = 'C:\ORDO-MySQL84-Isolated\mysql-8.4.13-winx64\bin\mysql.exe'
$Database = 'ordo_deals_regression_20261109_a1'
$TestUser = 'ordo_deals_test_20261109_a1'
$Workspace = $PSScriptRoot

function Assert-Equal([string]$Actual, [string]$Expected, [string]$Message) {
    if ($Actual.Trim().TrimEnd('\','/') -ine $Expected.Trim().TrimEnd('\','/')) {
        throw "$Message (expected '$Expected'; received '$Actual')"
    }
}

function Protect-TempFile([string]$Path) {
    $acl = New-Object System.Security.AccessControl.FileSecurity
    $acl.SetAccessRuleProtection($true, $false)
    $sid = [System.Security.Principal.WindowsIdentity]::GetCurrent().User
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule($sid, 'FullControl', 'Allow')
    $acl.AddAccessRule($rule)
    Set-Acl -LiteralPath $Path -AclObject $acl
}

function Invoke-Mysql([string]$OptionsFile, [string]$Sql) {
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $Mysql
    $psi.Arguments = '--defaults-extra-file="' + $OptionsFile + '" --protocol=TCP --host=' + $ServerHost + ' --port=' + $ServerPort + ' --batch --raw --skip-column-names'
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true
    $psi.RedirectStandardInput = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $proc = New-Object System.Diagnostics.Process
    $proc.StartInfo = $psi
    $started = $false
    try {
        if (-not $proc.Start()) { throw 'Could not start the MySQL client.' }
        $started = $true
        $stdoutTask = $proc.StandardOutput.ReadToEndAsync()
        $stderrTask = $proc.StandardError.ReadToEndAsync()
        $proc.StandardInput.WriteLine($Sql)
        $proc.StandardInput.Close()
        $proc.WaitForExit()
        $stdout = $stdoutTask.Result
        $stderr = $stderrTask.Result
        if ($proc.ExitCode -ne 0) {
            $safeError = $stderr -replace '(?i)(password|identified by)\s+[^\s;]+','$1 [redacted]'
            throw "MySQL command failed (exit $($proc.ExitCode)): $safeError"
        }
        if ($stdout) {
            $rows = [string[]]@($stdout -split "`r?`n" | Where-Object { $_ -ne '' })
            return ,$rows
        }
        $emptyRows = [string[]]@()
        return ,$emptyRows
    }
    finally {
        if ($started -and -not $proc.HasExited) {
            try { $proc.Kill(); $proc.WaitForExit() } catch { }
        }
        $proc.Dispose()
    }
}

function Write-MyCnf([string]$Path, [string]$Username, [string]$Password, [string]$DatabaseName = '') {
    $escapedPassword = $Password.Replace('\','\\').Replace('"','\"')
    $databaseLine = if ($DatabaseName) { "database=$DatabaseName`r`n" } else { '' }
    $content = "[client]`r`nuser=$Username`r`npassword=`"$escapedPassword`"`r`n$databaseLine"
    $file = [System.IO.File]::Open($Path, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None)
    $file.Dispose()
    Protect-TempFile $Path
    [System.IO.File]::WriteAllText($Path, $content, [System.Text.Encoding]::ASCII)
}

function Apply-ValidatedConfigEdits([object[]]$Edits) {
    $staged = @{}
    $original = @{}
    foreach ($edit in $Edits) {
        $path = [System.IO.Path]::GetFullPath($edit.Path)
        if (-not $staged.ContainsKey($path)) {
            if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Configuration file is missing: $path" }
            $staged[$path] = [System.IO.File]::ReadAllText($path)
            $original[$path] = $staged[$path]
        }
        $text = [string]$staged[$path]
        $actual = ([regex]::Matches($text, [regex]::Escape([string]$edit.Old))).Count
        if ($actual -ne [int]$edit.Count) {
            throw "Configuration preflight failed for $path, '$($edit.Old)': found $actual occurrence(s), expected $($edit.Count). No configuration files were written."
        }
        $staged[$path] = $text.Replace([string]$edit.Old, [string]$edit.New)
    }

    $written = @()
    try {
        foreach ($path in $staged.Keys) {
            [System.IO.File]::WriteAllText($path, [string]$staged[$path], (New-Object System.Text.UTF8Encoding($false)))
            $written += $path
        }
    }
    catch {
        foreach ($path in $written) {
            try { [System.IO.File]::WriteAllText($path, [string]$original[$path], (New-Object System.Text.UTF8Encoding($false))) }
            catch { Write-Error "Failed to restore original configuration file after write error: $path" }
        }
        throw
    }
}

$identity = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
Write-Output "Windows identity: $identity"
Assert-Equal $identity $ExpectedIdentity 'Wrong Windows account; run this script in the authorized account session.'
if (-not (Test-Path -LiteralPath $Mysql -PathType Leaf)) { throw 'The specified MySQL 8.4.13 client is missing.' }
if (-not (Test-Path -LiteralPath $RootCredentialPath -PathType Leaf)) { throw 'The protected MySQL root credential file is missing.' }

try { $rootCredential = Import-Clixml -LiteralPath $RootCredentialPath }
catch { throw "Could not decrypt the saved MySQL credential under '$identity'. Run only as the authorized Windows account. Details: $($_.Exception.Message)" }
if ($rootCredential -isnot [System.Management.Automation.PSCredential]) { throw 'The saved root credential is not a PSCredential.' }

$tempCnf = Join-Path $env:TEMP ('ordo-deals-root-' + [guid]::NewGuid().ToString('N') + '.cnf')
$plainRoot = $rootCredential.GetNetworkCredential().Password
try {
    Write-MyCnf $tempCnf $rootCredential.UserName $plainRoot
    $server = Invoke-Mysql $tempCnf 'SELECT @@version, @@port, @@datadir, CURRENT_USER();'
    if ($server.Count -ne 1) { throw 'Could not establish a single MySQL server identity result.' }
    $parts = $server[0] -split "`t"
    Assert-Equal $parts[0] $ExpectedVersion 'Unexpected MySQL version.'
    Assert-Equal $parts[1] "$ServerPort" 'Unexpected MySQL port.'
    Assert-Equal $parts[2] $ExpectedDataDirectory 'Unexpected MySQL data directory.'
    if ($parts[3] -notmatch '^root@') { throw 'Saved credential did not authenticate as a root account.' }
    Write-Output "MySQL identity: $($parts[0]) on port $($parts[1]); data directory verified; authenticated as root."

    $schemas = Invoke-Mysql $tempCnf 'SHOW DATABASES;'
    $schemas = @($schemas | Where-Object { $_ })
    $expectedSchemas = @('information_schema','mysql','performance_schema','sys')
    $schemaSet = (@($schemas | Sort-Object) -join ',')
    $expectedSchemaSet = (@($expectedSchemas | Sort-Object) -join ',')
    if ($schemaSet -cne $expectedSchemaSet) {
        throw "Server schema inventory differs from the verified four-system-schema baseline: $($schemas -join ', ')"
    }
    $activity = Invoke-Mysql $tempCnf "SELECT ID, USER, HOST, DB, COMMAND, COALESCE(STATE,'') FROM information_schema.PROCESSLIST WHERE ID <> CONNECTION_ID() AND USER NOT IN ('event_scheduler');"
    if ($activity.Count -gt 0) { throw 'Other active MySQL sessions exist; restore/maintenance activity state is not clear.' }
    $existing = Invoke-Mysql $tempCnf "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$Database';"
    if ($existing.Count -gt 0) { throw "Proposed database already exists: $Database. No existing schema will be reused or modified." }
    $users = Invoke-Mysql $tempCnf "SELECT User FROM mysql.user WHERE User = '$TestUser' AND Host = '127.0.0.1';"
    if ($users.Count -gt 0) { throw "Proposed test account already exists: '$TestUser'@'127.0.0.1'. No existing account will be modified." }

    $credentialDirectory = Split-Path -Parent $TestCredentialPath
    if (-not (Test-Path -LiteralPath $credentialDirectory -PathType Container)) { throw 'Protected credential destination directory is missing.' }
    if (Test-Path -LiteralPath $TestCredentialPath) { throw "Protected test credential already exists: $TestCredentialPath. Refusing to overwrite it." }
    $randomBytes = New-Object byte[] 48
    $rng = New-Object Security.Cryptography.RNGCryptoServiceProvider
    $rng.GetBytes($randomBytes)
    $testPassword = [Convert]::ToBase64String($randomBytes).TrimEnd('=').Replace('+','-').Replace('/','_')
    $rng.Dispose()
    $sqlPassword = $testPassword.Replace("'", "''")
    $setupSql = @"
CREATE DATABASE $Database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$TestUser'@'127.0.0.1' IDENTIFIED BY '$sqlPassword';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON $Database.* TO '$TestUser'@'127.0.0.1';
"@
    Invoke-Mysql $tempCnf $setupSql | Out-Null
    $testCredential = [pscredential]::new("$TestUser@127.0.0.1", (ConvertTo-SecureString $testPassword -AsPlainText -Force))
    $testCredential | Export-Clixml -LiteralPath $TestCredentialPath -Force
    $testPassword = $null
    $sqlPassword = $null

    $testCnf = Join-Path $env:TEMP ('ordo-deals-test-' + [guid]::NewGuid().ToString('N') + '.cnf')
    $plainTest = $testCredential.GetNetworkCredential().Password
    try {
        Write-MyCnf $testCnf $TestUser $plainTest $Database
        $identityRow = Invoke-Mysql $testCnf 'SELECT @@version, @@port, @@datadir, DATABASE(), CURRENT_USER();'
        if ($identityRow.Count -ne 1) { throw 'Could not establish a single test-account identity result.' }
        $id = $identityRow[0] -split "`t"
        Assert-Equal $id[0] $ExpectedVersion 'Test account reached the wrong MySQL version.'
        Assert-Equal $id[1] "$ServerPort" 'Test account reached the wrong port.'
        Assert-Equal $id[2] $ExpectedDataDirectory 'Test account reached the wrong data directory.'
        Assert-Equal $id[3] $Database 'Test account reached the wrong database.'
        Assert-Equal $id[4] "$TestUser@127.0.0.1" 'Test account authenticated as an unexpected MySQL identity.'
        $objects = Invoke-Mysql $testCnf 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();'
        if ($objects.Count -ne 1) { throw 'Could not verify the regression database table count.' }
        if ($objects[0] -ne '0') { throw 'New regression database is not empty; tests will not run.' }
        $grants = Invoke-Mysql $testCnf 'SHOW GRANTS;'
        foreach ($grant in $grants) {
            if ($grant -match '(?i)ON\s+`?\*`?\.\*' -and $grant -notmatch '(?i)GRANT USAGE ON') { throw 'Test account has a global grant.' }
            if ($grant -match '(?i)ON\s+`([^`]+)`\.\*' -and $Matches[1] -ne $Database) { throw 'Test account has a grant on another schema.' }
        }
    }
    finally { Remove-Item -LiteralPath $testCnf -Force -ErrorAction SilentlyContinue; $plainTest = $null }

    $files = @(
        @{ Path=(Join-Path $Workspace 'phpunit.deals-regression.xml'); Old='ordo_deals_regression_test_20261109'; New=$Database; Count=1 },
        @{ Path=(Join-Path $Workspace 'phpunit.deals-regression.xml'); Old='value="ordo_deals_regression"'; New="value=`"$TestUser`""; Count=1 },
        @{ Path=(Join-Path $Workspace 'phpunit.deals-regression.xml'); Old='3307'; New="$ServerPort"; Count=1 },
        @{ Path=(Join-Path $Workspace 'tests\DealsMysqlTestCase.php'); Old='ordo_deals_regression_test_20261109'; New=$Database; Count=5 },
        @{ Path=(Join-Path $Workspace 'tests\DealsMysqlTestCase.php'); Old='ordo_deals_regression@'; New="$TestUser@"; Count=2 },
        @{ Path=(Join-Path $Workspace 'tests\DealsMysqlTestCase.php'); Old='3307'; New="$ServerPort"; Count=3 },
        @{ Path=(Join-Path $Workspace 'tests\DealsMysqlIsolationProvider.php'); Old='ordo_deals_regression_test_20261109'; New=$Database; Count=1 },
        @{ Path=(Join-Path $Workspace 'tests\DealsMysqlIsolationProvider.php'); Old="'ordo_deals_regression'"; New="'$TestUser'"; Count=1 },
        @{ Path=(Join-Path $Workspace 'tests\DealsMysqlIsolationProvider.php'); Old='3307'; New="$ServerPort"; Count=1 }
    )
    Apply-ValidatedConfigEdits $files
    Write-Output "Isolation verified: empty database '$Database'; account '$TestUser'@'127.0.0.1'; grants limited to that schema. Credential protected at '$TestCredentialPath'."

    $env:DB_PASSWORD = $testCredential.GetNetworkCredential().Password
    $testExit = 1
    try {
        Push-Location $Workspace
        try { & (Join-Path $Workspace 'vendor\bin\phpunit.bat') -c (Join-Path $Workspace 'phpunit.deals-regression.xml') --no-progress --no-coverage; $testExit = $LASTEXITCODE }
        finally { Pop-Location }
    }
    finally { Remove-Item Env:\DB_PASSWORD -ErrorAction SilentlyContinue }
    if ($testExit -ne 0) { throw "Deals MySQL regression suite failed with exit code $testExit." }
}
finally {
    Remove-Item -LiteralPath $tempCnf -Force -ErrorAction SilentlyContinue
    $plainRoot = $null
}
