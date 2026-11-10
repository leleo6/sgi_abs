@echo off
REM =====================================================================
REM SGI-ABS — Script para Detener Servicios en Windows
REM =====================================================================

title SGI-ABS - Deteniendo Servicios

echo [INFO] Deteniendo contenedores de SGI-ABS...
docker compose down

echo [OK] Servicios detenidos exitosamente.
pause
