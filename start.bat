@echo off
REM =====================================================================
REM SGI-ABS — Script de Inicio Rápido para Windows (Docker Desktop)
REM =====================================================================

title SGI-ABS - Iniciando Plataforma

echo ==========================================================
echo    Iniciando SGI-ABS en Windows con Docker Desktop
echo ==========================================================

REM 1. Verificar si Docker esta instalado
docker --version >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Docker no esta instalado o no se encuentra en el PATH.
    echo         Instale Docker Desktop desde: https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

REM 2. Verificar si Docker daemon esta corriendo
docker info >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Docker Desktop no se encuentra en ejecucion.
    echo         Por favor inicie Docker Desktop y vuelva a ejecutar este script.
    pause
    exit /b 1
)

REM 3. Crear .env si no existe
if not exist ".env" (
    echo [INFO] Archivo .env no encontrado. Copiando desde .env.example...
    copy .env.example .env >nul
)

REM 4. Levantar contenedores
echo [INFO] Levantando contenedores en segundo plano...
docker compose up -d

echo ==========================================================
echo    SGI-ABS esta listo y en ejecucion
echo ==========================================================
echo  - Aplicacion Web: http://localhost:8085
echo  - phpMyAdmin:     http://localhost:8086
echo  - MySQL Externo:  localhost:3308
echo.
echo  Credenciales iniciales: admin@abs.edu.co / Admin2026!
echo ==========================================================

REM Abrir navegador automaticamente
timeout /t 3 >nul
start http://localhost:8085

pause
