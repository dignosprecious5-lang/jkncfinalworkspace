$candidateNodes = @(
    (Get-Command node -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source -ErrorAction SilentlyContinue),
    'C:\Program Files\Adobe\Adobe Creative Cloud Experience\libs\node.exe',
    'C:\Program Files\Adobe\Adobe Photoshop 2022\node.exe',
    'C:\Program Files\Common Files\Adobe\Creative Cloud Libraries\libs\node.exe',
    'C:\Users\Miru\AppData\Local\Autodesk\webdeploy\production\37a4fdb912fcb7645f48036186017c0cc6730354\NODEJS\node.exe'
) | Where-Object { $_ -and (Test-Path $_) } | Select-Object -Unique

if (-not $candidateNodes) {
    Write-Error 'No Node executable was found. Install Node.js or add node.exe to PATH.'
    exit 1
}

$nodePath = $candidateNodes[0]
$script = @'
const fs = require("fs");
const path = require("path");
const source = fs.readFileSync(path.join(process.cwd(), "public", "js", "finance.js"), "utf8");
new Function(source);
console.log("finance.js syntax OK");
'@

$script | & $nodePath

if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
