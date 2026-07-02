@echo off
title Smart Complaint System Services
cd /d "c:\Users\admin\OneDrive\Documents\smart-complaint-system"

echo =======================================================
echo     Starting College Smart Complaint Portal Services
echo =======================================================
echo.

:: 1. Start MariaDB Server
echo [1/3] Launching MariaDB database...
start "MariaDB Database" /min "C:\Users\admin\dev-env\mariadb\bin\mysqld.exe" --console
echo Waiting for database to initialize...
timeout /t 4 >nul

:: 2. Start PHP Web Server
echo [2/3] Launching PHP Web Server (Port 8000)...
start "PHP Web Server" /min "C:\Users\admin\dev-env\php\php.exe" -S localhost:8000
timeout /t 2 >nul

:: 3. Start Cloudflare Tunnel
echo [3/3] Launching Cloudflare Public Tunnel...
echo.
echo -------------------------------------------------------
echo  Your Public Tunnel URL will be printed below:
echo  (Look for the trycloudflare.com link)
echo -------------------------------------------------------
"C:\Users\admin\dev-env\cloudflared.exe" tunnel --url http://localhost:8000

pause
