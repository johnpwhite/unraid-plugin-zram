# publish-to-github.ps1
# Automates the release from Private GitLab to Public GitHub

# --- CONFIGURATION ---
$GITHUB_USER = "johnpwhite"
$REPO_NAME = "unraid-plugin-zram"
$PLG_FILE = "unraid-zram-card.plg"
$GITHUB_RAW_URL = "https://github.com/$GITHUB_USER/$REPO_NAME/raw/main"
$GITLAB_RAW_URL = "https://gitlab.johnpwhite.com/johner/unraid-zram-card/-/raw/master"

# 1. Verification
$status = git status --porcelain
if ($status) {
    Write-Host "ERROR: You have uncommitted changes on master. Please commit or stash them first." -ForegroundColor Red
    exit
}

# 2. Extract Version
$version = (Select-String -Path $PLG_FILE -Pattern 'ENTITY version\s+"([\d\.]+)"').Matches.Groups[1].Value
Write-Host "Preparing release for Version $version ..." -ForegroundColor Cyan

# 3. Create temporary deployment branch
Write-Host "Creating temporary deployment branch..."
git checkout -b deploy-github

try {
    # 4. Transformations (URL Rewriting & Changelog Cleanup)
    Write-Host "Rewriting URLs and cleaning Changelog in .plg for GitHub..."
    $content = Get-Content $PLG_FILE -Raw
    
    # URL Rewriting
    $content = $content -replace [regex]::Escape($GITLAB_RAW_URL), $GITHUB_RAW_URL
    
    # Changelog Replacement
    if (Test-Path "CHANGES.public.xml") {
        $publicChanges = Get-Content "CHANGES.public.xml" -Raw
        $content = $content -replace '(?s)<CHANGES>.*?</CHANGES>', $publicChanges
    }
    
    Set-Content $PLG_FILE $content

    # 5. File Swapping (README)
    if (Test-Path "README.public.md") {
        Write-Host "Replacing internal README with public version..."
        if (Test-Path "README.md") { Remove-Item "README.md" -Force }
        Copy-Item "README.public.md" "README.md"
    }

    # 6. Cleaning (Remove internal files)
    Write-Host "Removing internal files and temporary debug logs..."
    $internalFiles = @(
        "AGENT_SKILL_UNRAID_PLUGIN.md",
        "README.public.md",
        "CHANGES.public.xml",
        "publish-to-github.ps1",
        "debug files",
        "screen-shots",
        ".git"
    )

    foreach ($file in $internalFiles) {
        if (Test-Path $file) {
            Write-Host "  > Removing $file"
            git rm -r "$file" --force --quiet 2>$null
        }
    }

    # 7. Commit & Squash
    Write-Host "Creating clean squash commit..."
    git add -A
    git commit -m "Official Release v$version" --quiet

    # 8. Push to GitHub
    Write-Host "Pushing to GitHub..." -ForegroundColor Yellow
    git push github deploy-github:main --force

    Write-Host "SUCCESS: Version $version is now live on GitHub!" -ForegroundColor Green

}
catch {
    Write-Host "An error occurred during publishing: $($_.Exception.Message)" -ForegroundColor Red
}
finally {
    # 9. Cleanup
    Write-Host "Cleaning up local workspace..."
    git checkout master --quiet
    git branch -D deploy-github --quiet
    Write-Host "Returned to master (GitLab branch)." -ForegroundColor Gray
}