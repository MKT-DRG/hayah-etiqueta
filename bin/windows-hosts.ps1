# Execute no PowerShell do Windows como administrador.
$ErrorActionPreference = 'Stop'
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Abra o PowerShell como administrador para editar o hosts do Windows.'
}
$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$content = Get-Content $hostsPath
$entry = $content | Where-Object { ($_ -split '#')[0] -match '(^|\s)etiquetas\.br(\s|$)' }
if ($entry) {
    Write-Output "Entrada existente preservada: $entry"
} else {
    Add-Content -Path $hostsPath -Value "`r`n127.0.0.1 etiquetas.br # projeto-local"
    ipconfig /flushdns
    Write-Output 'Domínio local configurado: http://etiquetas.br'
}
