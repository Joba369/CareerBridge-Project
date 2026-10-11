@echo off
REM ------------------------------------------------------------------
REM  CareerBridge - share your local XAMPP site on the internet (ngrok)
REM  1. Install ngrok: https://ngrok.com/download  (free account)
REM  2. Run once:  ngrok config add-authtoken YOUR_TOKEN
REM  3. Start Apache + MySQL in the XAMPP Control Panel
REM  4. Double-click this file. Open the https://....ngrok-free.app URL
REM     it prints and add /careerbridge/ to the end.
REM ------------------------------------------------------------------
where ngrok >nul 2>nul || (echo ngrok was not found. Install it from https://ngrok.com/download and try again. & pause & exit /b 1)
echo Starting ngrok tunnel to http://localhost:80 ...
echo Your public address will be:  https://YOUR-SUBDOMAIN.ngrok-free.app/careerbridge/
echo Press Ctrl+C to stop sharing.
ngrok http 80
pause
