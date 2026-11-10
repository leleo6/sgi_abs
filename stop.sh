#!/usr/bin/env bash
# =====================================================================
# SGI-ABS — Script para Detener Contenedores (Linux / macOS)
# =====================================================================

set -e

echo "[INFO] Deteniendo servicios de SGI-ABS..."
docker compose down

echo "[OK] Todos los servicios han sido detenidos correctamente."
