@echo off
cd /d %~dp0
echo Iniciando MessageFlow Server...
start /MIN php server/server.php
timeout /t 2 /nobreak > nul
echo.
echo ========================================
echo   ✅ Servidor Rodando!
echo   🌐 http://localhost/pcd/Public/
echo ========================================
echo.