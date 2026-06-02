@echo off
title MessageFlow - Todos os Servidores
cd /d C:\xampp\htdocs\pcd\server

echo ========================================
echo   Iniciando MessageFlow
echo ========================================
echo.

:: Fechar servidores antigos
taskkill /F /IM php.exe > nul 2>&1
timeout /t 2 /nobreak > nul

:: Iniciar WebSocket (porta 8080)
start "WebSocket Server" /MIN cmd /c "C:\xampp\php\php.exe websocket_server.php"

:: Aguardar
timeout /t 2 /nobreak > nul

:: Iniciar gRPC (porta 50052)
start "gRPC Server" /MIN cmd /c "C:\xampp\php\php.exe -S localhost:50052 grpc_server.php"

echo.
echo ========================================
echo   Servidores Iniciados!
echo   WebSocket: ws://localhost:8080
echo   gRPC: http://localhost:50052/grpc
echo   Interface: http://localhost/pcd/Public/
echo ========================================
echo.
echo Pressione qualquer tecla para PARAR...
pause > nul

:: Parar servidores
echo Parando servidores...
taskkill /F /IM php.exe > nul 2>&1
echo Servidores parados.