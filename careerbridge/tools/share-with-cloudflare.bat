@echo off
REM ------------------------------------------------------------------
REM  CareerBridge - share your local XAMPP site (Cloudflare Quick Tunnel)
REM  No account needed.
REM  1. Install cloudflared:  winget install --id Cloudflare.cloudflared
REM     (or download from https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/)
REM  2. Start Apache + MySQL in the XAMPP Control Panel
REM  3. Double-click this file. Open the https://....trycloudflare.com URL
REM     it prints and add /careerbridge/ to the end.
REM ------------------------------------------------------------------
where cloudflared >nul 2>nul || (echo cloudflared was not found. Install it with: winget install --id Cloudflare.cloudflared & pause & exit /b 1)
echo Starting Cloudflare quick tunnel to http://localhost:80 ...
echo Look for a line like  https://random-words.trycloudflare.com  and add /careerbridge/
echo Press Ctrl+C to stop sharing.
cloudflared tunnel --url http://localhost:80
pause
