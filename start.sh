#!/usr/bin/env bash
# =====================================================================
# SGI-ABS — Script de Inicio Rápido para Linux / macOS
# =====================================================================

set -e

echo "=========================================================="
echo "   Iniciando SGI-ABS — Entorno de Contenedores Docker     "
echo "=========================================================="

# 1. Verificar presencia de Docker
if ! command -v docker &> /dev/null; then
    echo "[ERROR] Docker no está instalado en este sistema."
    echo "        Por favor instale Docker Engine antes de continuar."
    exit 1
fi

# 2. Verificar Docker Compose
if ! docker compose version &> /dev/null; then
    echo "[ERROR] Docker Compose v2 no está disponible."
    echo "        Instale el plugin 'docker-compose-plugin'."
    exit 1
fi

# 3. Preparar archivo de entorno (.env) si no existe
if [ ! -f .env ]; then
    echo "[INFO] Archivo .env no encontrado. Creando a partir de .env.example..."
    cp .env.example .env
fi

# 4. Levantar contenedores en segundo plano
echo "[INFO] Construyendo y levantando contenedores..."
docker compose up -d

echo ""
echo "=========================================================="
echo "   ¡SGI-ABS se encuentra en ejecución con éxito!          "
echo "=========================================================="
echo " - Aplicación Web: http://localhost:8085"
echo " - phpMyAdmin:     http://localhost:8086"
echo " - MySQL Externo:  localhost:3308"
echo ""
echo " Credenciales de acceso de prueba:"
echo "   * Admin:       admin@abs.edu.co     / Admin2026!"
echo "   * Registro:    registro@abs.edu.co  / Registro2026!"
echo "   * Coordinador: coordinacion@abs.edu.co / Coord2026!"
echo "   * Docente:     docente@abs.edu.co   / Docente2026!"
echo "   * Prácticas:   practicas@abs.edu.co / Practicas2026!"
echo "   * Estudiante:  estudiante@abs.edu.co / Estudiante2026!"
echo "=========================================================="
