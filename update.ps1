$owner = "pmmp"
$repo = "PocketMine-MP"
$protocolRepo = "BedrockProtocol"


$latestReleaseUrl = "https://api.github.com/repos/$owner/$repo/releases/latest"

Write-Host -ForegroundColor DarkBlue "Fetch $latestReleaseUrl"
$latestReleaseJson = (Invoke-WebRequest $latestReleaseUrl -Headers @{'Accept' = 'application/vnd.github+json'}).Content | ConvertFrom-Json

$pmmpBranch = $latestReleaseJson.'tag_name'

Write-Host -ForegroundColor Green "Choosing branch: $pmmpBranch"


$versionInfoUrl = "https://raw.githubusercontent.com/$owner/$repo/$pmmpBranch/src/VersionInfo.php"

Write-Host -ForegroundColor DarkBlue "Fetch $versionInfoUrl"
$versionInfo = (Invoke-WebRequest $versionInfoUrl).Content -split '\n'

$baseVersion = $versionInfo | Select-String -Pattern 'public const BASE_VERSION = ' -CaseSensitive -SimpleMatch
$baseVersion = $baseVersion -replace '.+ = "(.+)";','$1';


$composerJsonUrl = "https://raw.githubusercontent.com/$owner/$repo/$pmmpBranch/composer.json"

Write-Host -ForegroundColor DarkBlue "Fetch $composerJsonUrl"
$composerJson = (Invoke-WebRequest $composerJsonUrl).Content | ConvertFrom-Json

$protocolBranch = $composerJson.'require'.'pocketmine/bedrock-protocol'.Substring(1) # Remove ~ at the start of the tag name


$protocolInfoUrl = "https://raw.githubusercontent.com/$owner/$protocolRepo/$protocolBranch/src/ProtocolInfo.php"

Write-Host -ForegroundColor DarkBlue "Fetch $protocolInfoUrl"
$protcolInfo = (Invoke-WebRequest $protocolInfoUrl).Content -split '\n'

$currentProtocol = $protcolInfo | Select-String -Pattern 'public const CURRENT_PROTOCOL = ' -CaseSensitive -SimpleMatch
$currentProtocol = $currentProtocol -replace '.+ = (\d+);','$1'

$minecraftVersion = $protcolInfo | Select-String -Pattern 'public const MINECRAFT_VERSION = ' -CaseSensitive -SimpleMatch
$minecraftVersion = $minecraftVersion -replace '.+ = ''v(.+)'';','$1';


Write-Output "PMMP version: $baseVersion"
Write-Output "Current protocol: $currentProtocol"
Write-Output "Minecraft version: $minecraftVersion"
Write-Output "Commit: git commit -am ""Compatibility with PocketMine $baseVersion for Minecraft $minecraftVersion (Protocol $currentProtocol)"""


$pluginYmlFile = '.\plugin.yml'
$pluginYml = Get-Content $pluginYmlFile
$textToInsert = "  - $currentProtocol # v$minecraftVersion"
$insertBefore = 'author: '

if ($pluginYml.Contains($textToInsert)) {
    Write-Host -ForegroundColor Red "Already in $pluginYmlFile!"
} else {
    $pluginVersion = (($pluginYml | Select-String 'version:') -replace 'version: (.+)','$1').Split('.')
    $pluginOldVersion = $pluginVersion[0],$pluginVersion[1],$pluginVersion[2]
    $pluginVersion[2] = 1 + $pluginVersion[2]
    Write-Host -ForegroundColor Green "Plugin version changes from $($pluginOldVersion -join '.') to $($pluginVersion -join '.')"

    Write-Host -ForegroundColor Green "Inserting ""$textToInsert"" into $pluginYmlFile"
    $pluginYml -replace $insertBefore,"$textToInsert`n$insertBefore" -replace "version: $($pluginOldVersion -join '.')","version: $($pluginVersion -join '.')" | Set-Content $pluginYmlFile
}