param([switch]$TargetedOnly)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ExpectedIdentity = 'LAPTOP-QCB457BV\PRECIOUS D. DIGNOS'
$HostName = '127.0.0.1'
$Port = 13321
$Database = 'ordo_deals_regression_20261109_a1'
$Username = 'ordo_deals_test_20261109_a1'
$DataDirectory = 'C:\ORDO-Restore-20261109\mysql-data'
$CredentialPath = 'C:\ORDO-Restore-20261109\run\deals-regression-credential.xml'
$Mysql = 'C:\ORDO-MySQL84-Isolated\mysql-8.4.13-winx64\bin\mysql.exe'
$Workspace = $PSScriptRoot

function Protect-TempFile([string]$Path) {
    $acl = New-Object System.Security.AccessControl.FileSecurity
    $acl.SetAccessRuleProtection($true, $false)
    $sid = [System.Security.Principal.WindowsIdentity]::GetCurrent().User
    $rule = New-Object System.Security.AccessControl.FileSystemAccessRule($sid, 'FullControl', 'Allow')
    $acl.AddAccessRule($rule)
    Set-Acl -LiteralPath $Path -AclObject $acl
}

function Invoke-MysqlCommand([string]$OptionsFile, [string]$Sql) {
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $Mysql
    $psi.Arguments = '--defaults-extra-file="' + $OptionsFile + '" --protocol=TCP --host=' + $HostName + ' --port=' + $Port + ' --batch --raw --skip-column-names'
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
        if ($proc.ExitCode -ne 0) { throw "MySQL command failed (exit $($proc.ExitCode)): $stderr" }
        if ($stdout) { $rows = [string[]]@($stdout -split "`r?`n" | Where-Object { $_ -ne '' }); return ,$rows }
        $emptyRows = [string[]]@()
        return ,$emptyRows
    }
    finally {
        if ($started -and -not $proc.HasExited) { try { $proc.Kill(); $proc.WaitForExit() } catch { } }
        $proc.Dispose()
    }
}

function Assert-TargetIdentity([string]$OptionsFile) {
    $rows = Invoke-MysqlCommand $OptionsFile 'SELECT @@version, @@port, @@datadir, DATABASE(), CURRENT_USER();'
    if ($rows.Count -ne 1) { throw 'Could not obtain one authenticated MySQL identity row.' }
    $parts = $rows[0] -split "`t"
    if ($parts[0] -ne '8.4.13' -or $parts[1] -ne "$Port" -or $parts[2].TrimEnd('\','/') -ine $DataDirectory.TrimEnd('\','/') -or $parts[3] -ne $Database -or $parts[4] -notin @("$Username@127.0.0.1", "$Username@localhost")) {
        throw 'Authenticated MySQL identity does not match the isolated test instance.'
    }
    return ,$parts
}

function Reset-VerifiedTestTables([string]$OptionsFile) {
    $null = Assert-TargetIdentity $OptionsFile
    $grants = Invoke-MysqlCommand $OptionsFile 'SHOW GRANTS;'
    foreach ($grant in $grants) {
        if ($grant -match '(?i)ON\s+`?\*`?\.\*' -and $grant -notmatch '(?i)GRANT USAGE ON') { throw 'Test account has a global privilege; schema reset refused.' }
        if ($grant -match '(?i)ON\s+`([^`]+)`\.\*' -and $Matches[1] -ne $Database) { throw 'Test account has a grant on another schema; schema reset refused.' }
    }
    $countRows = Invoke-MysqlCommand $OptionsFile 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();'
    if ($countRows.Count -ne 1) { throw 'Could not verify isolated test schema object count.' }
    if ($countRows[0] -eq '0') { return }
    $tables = Invoke-MysqlCommand $OptionsFile "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';"
    if ($tables.Count -ne [int]$countRows[0]) { throw 'Schema contains non-table objects; safe disposable reset refused.' }
    foreach ($table in $tables) { if ($table -notmatch '\A[A-Za-z0-9_]+\z') { throw 'Unexpected table identifier; safe disposable reset refused.' } }
    $dropStatements = @($tables | ForEach-Object { 'DROP TABLE IF EXISTS `' + $_.Replace('`','``') + '`;' })
    $resetSql = 'SET FOREIGN_KEY_CHECKS = 0;' + ($dropStatements -join '') + 'SET FOREIGN_KEY_CHECKS = 1;'
    Invoke-MysqlCommand $OptionsFile $resetSql | Out-Null
    $null = Assert-TargetIdentity $OptionsFile
    $remaining = Invoke-MysqlCommand $OptionsFile 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();'
    if ($remaining.Count -ne 1 -or $remaining[0] -ne '0') { throw 'Disposable test-table reset did not leave an empty schema.' }
    Write-Output "Reset $($tables.Count) existing base tables within the verified disposable schema only."
}

function Invoke-PhpUnitRun([string]$ReportPath, [string]$Filter) {
    $arguments = @('-c', (Join-Path $Workspace 'phpunit.deals-regression.xml'), '--no-progress', '--no-coverage', '--log-junit', $ReportPath)
    if ($Filter) { $arguments += @('--filter', $Filter) }
    $php = (Get-Command php.exe -ErrorAction Stop).Source
    $phpUnitEntryPoint = Join-Path $Workspace 'vendor\bin\phpunit'
    & $php $phpUnitEntryPoint @arguments
    $script:PhpUnitExit = $LASTEXITCODE
}

function Show-PhpUnitSummary([string]$ReportPath, [string]$RunName, [string]$TestPassword, [string]$TestAppKey) {
    if (-not (Test-Path -LiteralPath $ReportPath -PathType Leaf)) { Write-Output "${RunName}: PHPUnit did not produce a JUnit report."; return }
    $reportText = [System.IO.File]::ReadAllText($ReportPath)
    if ($TestPassword) { $reportText = $reportText.Replace($TestPassword, '[redacted]') }
    if ($TestAppKey) { $reportText = $reportText.Replace($TestAppKey, '[redacted]') }
    $reportText = $reportText -replace '(?im)^(DB_PASSWORD|APP_KEY)\s*=.*$','$1=[redacted]'
    [System.IO.File]::WriteAllText($ReportPath, $reportText, (New-Object System.Text.UTF8Encoding($false)))
    [xml]$report = $reportText
    $cases = @($report.SelectNodes('//testcase'))
    $assertions = 0
    $errors = 0
    $failures = 0
    $risky = 0
    $groups = @{}
    foreach ($case in $cases) {
        $assertions += [int]$case.GetAttribute('assertions')
        foreach ($child in $case.ChildNodes) {
            if ($child.LocalName -notin @('error','failure')) { continue }
            if ($child.LocalName -eq 'error') { $errors++ } else { $failures++ }
            $type = $child.GetAttribute('type')
            $message = $child.GetAttribute('message')
            if (-not $message) { $message = ($child.InnerText -split "`r?`n")[0] }
            if ($type -match 'RiskyTestError' -or $message -match '(?i)risky|handler') { $risky++ }
            $groupKey = "$($child.LocalName)|$type|$message"
            if (-not $groups.ContainsKey($groupKey)) { $groups[$groupKey] = [pscustomobject]@{ Count=0; Sample=$case.GetAttribute('name') } }
            $groups[$groupKey].Count++
            Write-Output "${RunName} exception in $($case.GetAttribute('class'))::$($case.GetAttribute('name')): $($child.LocalName) $type"
            Write-Output "Message: $message"
            $trace = $child.InnerText.Trim()
            if ($trace) { Write-Output "Stack/cause:`r`n$trace" }
        }
    }
    Write-Output "$RunName summary: tests=$($cases.Count), assertions=$assertions, errors=$errors, failures=$failures, risky=$risky."
    foreach ($entry in $groups.GetEnumerator() | Sort-Object { $_.Value.Count } -Descending) {
        Write-Output "$RunName group count=$($entry.Value.Count) type=$($entry.Key); sample=$($entry.Value.Sample)"
    }
}

$identity = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
Write-Output "Windows identity: $identity"
if ($identity -ine $ExpectedIdentity) { throw 'Run this diagnostic as the authorized Windows account.' }
if (-not (Test-Path -LiteralPath $CredentialPath -PathType Leaf)) { throw 'The DPAPI-protected test credential is missing.' }
if (-not (Test-Path -LiteralPath $Mysql -PathType Leaf)) { throw 'The specified MySQL client is missing.' }

try { $credential = Import-Clixml -LiteralPath $CredentialPath }
catch { throw "Could not decrypt the test credential under '$identity'. Details: $($_.Exception.Message)" }
if ($credential -isnot [System.Management.Automation.PSCredential]) { throw 'The protected test credential has an unexpected type.' }
if ($credential.UserName -ine "$Username@127.0.0.1") { throw 'The protected test credential username does not match the approved test account.' }

$configXml = [xml][System.IO.File]::ReadAllText((Join-Path $Workspace 'phpunit.deals-regression.xml'))
$envNodes = @{}
foreach ($node in $configXml.SelectNodes('/phpunit/php/env')) { $envNodes[$node.name] = $node.value }
if ($envNodes['APP_ENV'] -ne 'testing' -or $envNodes['ORDO_DEALS_ISOLATED_TEST'] -ne '1' -or $envNodes['DB_CONNECTION'] -ne 'mysql' -or $envNodes['DB_HOST'] -ne $HostName -or $envNodes['DB_PORT'] -ne "$Port" -or $envNodes['DB_DATABASE'] -ne $Database -or $envNodes['DB_USERNAME'] -ne $Username) {
    throw 'PHPUnit configuration does not match the approved isolated test target.'
}
$caseText = [System.IO.File]::ReadAllText((Join-Path $Workspace 'tests\DealsMysqlTestCase.php'))
$providerText = [System.IO.File]::ReadAllText((Join-Path $Workspace 'tests\DealsMysqlIsolationProvider.php'))
if (-not $caseText.Contains("!== '$Username'") -or -not $providerText.Contains("!== '$Username'")) { throw 'Test harness account guards do not match the dedicated account.' }

$tempCnf = Join-Path $env:TEMP ('ordo-deals-regression-' + [guid]::NewGuid().ToString('N') + '.cnf')
$plainPassword = $credential.GetNetworkCredential().Password
$randomBytes = New-Object byte[] 32
$rng = New-Object Security.Cryptography.RNGCryptoServiceProvider
$rng.GetBytes($randomBytes)
$testAppKey = 'base64:' + [Convert]::ToBase64String($randomBytes)
$rng.Dispose()
[Array]::Clear($randomBytes, 0, $randomBytes.Length)
$reportDirectory = Join-Path $Workspace 'storage\logs'
if (-not (Test-Path -LiteralPath $reportDirectory -PathType Container)) { New-Item -ItemType Directory -Path $reportDirectory -Force | Out-Null }
$reportId = [guid]::NewGuid().ToString('N')
$targetReport = Join-Path $reportDirectory ('ordo-deals-targeted-' + $reportId + '.xml')
$fullReport = Join-Path $reportDirectory ('ordo-deals-full-' + $reportId + '.xml')
try {
    $file = [System.IO.File]::Open($tempCnf, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None)
    $file.Dispose()
    Protect-TempFile $tempCnf
    $escapedPassword = $plainPassword.Replace('\','\\').Replace('"','\"')
    $cnf = "[client]`r`nuser=$Username`r`npassword=`"$escapedPassword`"`r`ndatabase=$Database`r`n"
    [System.IO.File]::WriteAllText($tempCnf, $cnf, [System.Text.Encoding]::ASCII)

    $identityParts = Assert-TargetIdentity $tempCnf
    $grants = Invoke-MysqlCommand $tempCnf 'SHOW GRANTS;'
    foreach ($grant in $grants) {
        if ($grant -match '(?i)ON\s+`?\*`?\.\*' -and $grant -notmatch '(?i)GRANT USAGE ON') { throw 'Test account has a global privilege; tests were not started.' }
        if ($grant -match '(?i)ON\s+`([^`]+)`\.\*' -and $Matches[1] -ne $Database) { throw 'Test account has a grant on another schema; tests were not started.' }
    }
    Reset-VerifiedTestTables $tempCnf
    Write-Output "Isolated MySQL verified: $($identityParts[0]), port $($identityParts[1]), database '$Database', account '$Username'@127.0.0.1, scoped grants."

    $env:DB_PASSWORD = $plainPassword
    $env:APP_KEY = $testAppKey
    $env:APP_PREVIOUS_KEYS = ''
    try {
        $php = Get-Command php.exe -ErrorAction Stop
        $verifyCode = "exit((getenv('DB_PASSWORD') !== false && substr(getenv('APP_KEY'), 0, 7) === 'base64:' && strlen(base64_decode(substr(getenv('APP_KEY'), 7), true)) === 32) ? 0 : 1);"
        & $php.Source -r $verifyCode
        if ($LASTEXITCODE -ne 0) { throw 'PHP did not receive the isolated DB credential and valid test-only APP_KEY; PHPUnit was not started.' }
        Write-Output 'PHP environment verified: DB_PASSWORD is present and APP_KEY is a valid random test-only key.'

        $rollbackAndRepresentativeTests = @(
            'test_approval_and_deal_updates_roll_back_if_the_deal_update_fails',
            'test_version_approval_and_event_roll_back_if_event_insert_fails',
            'test_legacy_service_uses_master_requirements_output_zero_price_and_tax',
            'test_approved_deletion_request_archives_and_retains_related_history',
            'test_guest_client_disabled_and_archived_users_are_denied',
            'test_first_valid_approval_records_the_client_and_moves_the_deal_to_negotiation',
            'test_unauthorized_create_assignment_is_rejected_before_any_deal_write',
            'test_normalized_memberships_authorize_assigned_roles_but_text_assignments_do_not',
            'test_deletion_request_with_issued_version_is_marked_pending_without_losing_history',
            'test_each_issued_version_has_at_most_one_delivery_record',
            'test_assigned_eligible_finance_user_can_approve_active_deal_quotation',
            'test_stage_mutation_endpoints_deny_without_permission_and_do_not_write',
            'test_versioned_send_flag_defaults_disabled_and_preserves_legacy_email_behavior',
            'test_enabled_versioned_send_captures_one_html_snapshot_pdf_and_delivery_without_synchronous_mail',
            'test_same_send_uuid_reuses_frozen_snapshot_and_token_after_live_data_changes',
            'test_conflicting_send_uuid_is_rejected_without_rotating_token_or_creating_records',
            'test_failed_versioned_pdf_render_does_not_rotate_or_create_a_client_token',
            'test_failed_private_artifact_write_does_not_rotate_or_create_a_client_token'
        )
        $filter = '(' + ($rollbackAndRepresentativeTests -join '|') + ')'
        Push-Location $Workspace
        try { Invoke-PhpUnitRun $targetReport $filter }
        finally { Pop-Location }
        $targetExit = $script:PhpUnitExit
        Show-PhpUnitSummary $targetReport 'Targeted' $plainPassword $testAppKey
        if (Test-Path -LiteralPath $targetReport) { Write-Output "Sanitized targeted JUnit report: $targetReport" }
        if ($targetExit -ne 0) { throw "Targeted Deals tests reported failures, errors, or risky tests (exit $targetExit); full suite was not started." }
        if ($TargetedOnly) { Write-Output 'Targeted-only mode requested; full regression suite was not started.'; return }

        Reset-VerifiedTestTables $tempCnf
        Push-Location $Workspace
        try { Invoke-PhpUnitRun $fullReport '' }
        finally { Pop-Location }
        $fullExit = $script:PhpUnitExit
        Show-PhpUnitSummary $fullReport 'Full regression' $plainPassword $testAppKey
        if (Test-Path -LiteralPath $fullReport) { Write-Output "Sanitized full JUnit report: $fullReport" }
        if ($fullExit -ne 0) { throw "Deals MySQL regression suite failed with exit code $fullExit." }
    }
    finally {
        Remove-Item Env:\DB_PASSWORD,Env:\APP_KEY,Env:\APP_PREVIOUS_KEYS -ErrorAction SilentlyContinue
    }
}
finally {
    Remove-Item -LiteralPath $tempCnf -Force -ErrorAction SilentlyContinue
    $plainPassword = $null
    $testAppKey = $null
}
