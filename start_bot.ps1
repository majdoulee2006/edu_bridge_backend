[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 -bor [Net.SecurityProtocolType]::Tls11 -bor [Net.SecurityProtocolType]::Tls
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

Write-Host ">>> Starting Edu Bridge Server & Telegram Bot..." -ForegroundColor Cyan

# 1. Read .env file to get TELEGRAM_BOT_TOKEN
$EnvFile = Join-Path $ScriptDir ".env"
$BotToken = ""
if (Test-Path $EnvFile) {
    Get-Content $EnvFile | ForEach-Object {
        if ($_ -match "^TELEGRAM_BOT_TOKEN=(.+)$") {
            $BotToken = $matches[1].Trim()
        }
    }
}

if (-not $BotToken) {
    Write-Host "[ERROR] Could not find TELEGRAM_BOT_TOKEN in .env file!" -ForegroundColor Red
    exit
}

# 2. Start Laravel server if not already running on port 8000
$PortCheck = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
if (-not $PortCheck) {
    Write-Host "[INFO] Starting Laravel server (php artisan serve)..." -ForegroundColor Yellow
    Start-Process -FilePath "php" -ArgumentList "artisan serve" -WorkingDirectory $ScriptDir -WindowStyle Minimized
    Start-Sleep -Seconds 2
} else {
    Write-Host "[OK] Laravel server is already running on port 8000." -ForegroundColor Green
}

# 3. Start Tunnel (ngrok or localtunnel)
$TunnelUrl = ""
$LocalNgrok = Join-Path $ScriptDir "ngrok.exe"
$NgrokCmd = Get-Command "ngrok" -ErrorAction SilentlyContinue

$NgrokExecutable = $null
if (Test-Path $LocalNgrok) {
    $NgrokExecutable = $LocalNgrok
} elseif ($NgrokCmd) {
    $NgrokExecutable = "ngrok"
}

if ($NgrokExecutable) {
    Write-Host "[INFO] Starting ngrok tunnel on port 8000 ($NgrokExecutable)..." -ForegroundColor Yellow
    Get-Process -Name "ngrok" -ErrorAction SilentlyContinue | Stop-Process -Force
    Start-Sleep -Milliseconds 500

    Start-Process -FilePath $NgrokExecutable -ArgumentList "http 8000" -WindowStyle Minimized

    for ($i = 1; $i -le 10; $i++) {
        Start-Sleep -Seconds 1
        try {
            $Response = Invoke-RestMethod -Uri "http://127.0.0.1:4040/api/tunnels" -ErrorAction Stop
            $HttpsTunnel = $Response.tunnels | Where-Object { $_.public_url -like "https://*" } | Select-Object -First 1
            if ($HttpsTunnel) {
                $TunnelUrl = $HttpsTunnel.public_url
                break
            }
        } catch {}
    }
}

if (-not $TunnelUrl) {
    Write-Host "[INFO] ngrok not found, starting localtunnel..." -ForegroundColor Yellow
    Start-Process -FilePath "cmd.exe" -ArgumentList "/c npx localtunnel --port 8000 --subdomain edubridge-attend" -WindowStyle Minimized
    Start-Sleep -Seconds 3
    $TunnelUrl = "https://edubridge-attend.loca.lt"
}

if (-not $TunnelUrl) {
    Write-Host "[ERROR] Failed to obtain tunnel URL!" -ForegroundColor Red
    exit
}

Write-Host "[OK] Public Tunnel URL: $TunnelUrl" -ForegroundColor Green

# 4. Update APP_URL in .env
try {
    $EnvContent = Get-Content $EnvFile -Raw
    if ($EnvContent -match "APP_URL=.*") {
        $EnvContent = $EnvContent -replace "APP_URL=.*", "APP_URL=$TunnelUrl"
        Set-Content -Path $EnvFile -Value $EnvContent -Encoding UTF8
        Write-Host "[OK] Updated APP_URL in .env successfully." -ForegroundColor Gray
    }
} catch {
    Write-Host "[WARN] Could not update .env: $_" -ForegroundColor DarkYellow
}

# 5. Set Telegram Webhook
$WebhookUrl = "$TunnelUrl/api/telegram/webhook"
Write-Host "[INFO] Registering webhook with Telegram API..." -ForegroundColor Yellow

$TgApiUrl = "https://api.telegram.org/bot$BotToken/setWebhook?url=$WebhookUrl"
try {
    $TgRes = Invoke-RestMethod -Uri $TgApiUrl -Method Get
    if ($TgRes.ok) {
        Write-Host "[SUCCESS] Telegram Webhook registered successfully!" -ForegroundColor Green
    } else {
        Write-Host "[WARN] Telegram response: $($TgRes.description)" -ForegroundColor Yellow
    }
} catch {
    # Fallback to curl.exe which natively supports modern TLS
    $CurlOutput = curl.exe -s "$TgApiUrl"
    if ($CurlOutput -match '"ok":\s*true') {
        Write-Host "[SUCCESS] Telegram Webhook registered successfully (via curl)!" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Telegram API request failed: $CurlOutput" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  Edu Bridge Bot is READY to receive messages!          " -ForegroundColor Green
Write-Host "  Open Telegram on your phone and test the bot now.     " -ForegroundColor Cyan
Write-Host "  (Keep this window open while testing)                 " -ForegroundColor DarkGray
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host ""
