#!/bin/bash
set -e

echo "================================================="
echo "   SGI-ABS — Inicializando contenedor de App     "
echo "================================================="

# Asegurar permisos en directorio de uploads
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

# Esperar a que MySQL responda
echo "[1/3] Verificando conexion con base de datos ($DB_HOST:$DB_PORT)..."
until php -r '
    $host = getenv("DB_HOST") ?: "db";
    $port = getenv("DB_PORT") ?: "3306";
    $user = getenv("DB_USER") ?: "root";
    $pass = getenv("DB_PASS") !== false ? getenv("DB_PASS") : "";
    try {
        $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        exit(0);
    } catch (\Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    echo "  -> Esperando a MySQL ($DB_HOST:$DB_PORT)... reintentando en 2 segundos."
    sleep 2
done
echo "[OK] Conexion con el servidor MySQL establecida."

# Verificar si la base de datos y la tabla usuarios ya existen
echo "[2/3] Verificando estado del esquema de la base de datos..."
INITIALIZED=$(php -r '
    $host = getenv("DB_HOST") ?: "db";
    $port = getenv("DB_PORT") ?: "3306";
    $user = getenv("DB_USER") ?: "root";
    $pass = getenv("DB_PASS") !== false ? getenv("DB_PASS") : "";
    $db   = getenv("DB_NAME") ?: "sgi_abs";
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [
            PDO::ATTR_TIMEOUT => 3
        ]);
        $stmt = $pdo->query("SHOW TABLES LIKE '\''usuarios'\''");
        echo ($stmt && $stmt->rowCount() > 0) ? "1" : "0";
    } catch (\Throwable $e) {
        echo "0";
    }
' 2>/dev/null || echo "0")

if [ "$INITIALIZED" != "1" ]; then
    echo "  -> Base de datos no encontrada o sin tablas. Ejecutando aprovisionamiento automatico (setup.php)..."
    php /var/www/html/sql/setup.php
    echo "[OK] Aprovisionamiento inicial completado con exito."
else
    echo "[OK] Base de datos ya configurada previamente. Omitiendo setup para preservar datos."
fi

# Instalar dependencias si vendor no existe (por ejemplo cuando se monta volumen en desarrollo)
if [ ! -d "/var/www/html/vendor" ] && [ -f "/var/www/html/composer.json" ]; then
    echo "[3/3] Instalando dependencias de Composer..."
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
else
    echo "[3/3] Dependencias listas."
fi

echo "================================================="
echo "   SGI-ABS listo y en ejecucion                  "
echo "================================================="

exec "$@"
