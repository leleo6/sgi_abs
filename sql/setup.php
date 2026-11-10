<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: setup.php
 * Propósito: Inicializador del esquema de base de datos y semillas
 * Compatible con: Ejecución CLI (terminal) o Web (navegador)
 * =====================================================================
 */

// Intentar cargar configuración si existe
$configFile = dirname(__DIR__) . '/config/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    define('BASE_PATH', dirname(__DIR__));
    define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
}

// Configuración de conexión por defecto
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbName = getenv('DB_NAME') ?: 'sgi_abs';

$isCli = (php_sapi_name() === 'cli');
$logs = [];

function logMsg(string $msg, string $type = 'info'): void {
    global $logs, $isCli;
    $logs[] = ['type' => $type, 'text' => $msg, 'time' => date('H:i:s')];
    if ($isCli) {
        $prefix = match($type) {
            'success' => '[OK] ',
            'error'   => '[ERROR] ',
            'warning' => '[WARN] ',
            default   => '[INFO] '
        };
        echo $prefix . $msg . PHP_EOL;
    }
}

try {
    logMsg("Iniciando aprovisionamiento de SGI-ABS...", 'info');

    // 1. Asegurar directorio de uploads
    if (!is_dir(UPLOAD_PATH)) {
        if (mkdir(UPLOAD_PATH, 0755, true)) {
            logMsg("Directorio de uploads creado en: " . UPLOAD_PATH, 'success');
        } else {
            logMsg("No se pudo crear el directorio de uploads en: " . UPLOAD_PATH, 'warning');
        }
    } else {
        logMsg("Directorio de uploads verificado: " . UPLOAD_PATH, 'info');
    }

    // 2. Conexión al servidor MySQL sin base de datos seleccionada
    $dsnServer = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    logMsg("Conectando al servidor MySQL en {$dbHost}:{$dbPort} con usuario '{$dbUser}'...", 'info');
    $pdo = new PDO($dsnServer, $dbUser, $dbPass, $pdoOptions);
    logMsg("Conexión con el servidor MySQL establecida.", 'success');

    // 3. Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");
    logMsg("Base de datos `{$dbName}` seleccionada.", 'success');

    // 4. Cargar y ejecutar 01_schema.sql
    $schemaFile = __DIR__ . '/01_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("No se encontró el archivo de esquema: {$schemaFile}");
    }

    logMsg("Leyendo archivo de esquema: 01_schema.sql...", 'info');
    $schemaSql = file_get_contents($schemaFile);

    // Separar bloques por DELIMITER para manejar disparadores correctamente
    if (preg_match('/DELIMITER \$\$\s*(.*?)\s*DELIMITER\s*;/s', $schemaSql, $matches)) {
        $triggersSql = trim($matches[1]);
        $mainSchemaSql = preg_replace('/DELIMITER \$\$.*?DELIMITER\s*;/s', '', $schemaSql);

        // Ejecutar DDL principal
        $pdo->exec($mainSchemaSql);
        logMsg("Tablas e índices creados exitosamente.", 'success');

        // Ejecutar disparadores individualmente
        $triggerBlocks = preg_split('/END\s*\$\$/i', $triggersSql);
        foreach ($triggerBlocks as $trig) {
            $trig = trim($trig);
            if (!empty($trig)) {
                $pdo->exec($trig . ' END');
            }
        }
        logMsg("Disparadores de integridad de materias registrados.", 'success');
    } else {
        $pdo->exec($schemaSql);
        logMsg("Esquema ejecutado completamente.", 'success');
    }

    // 5. Cargar y ejecutar 02_seed.sql
    $seedFile = __DIR__ . '/02_seed.sql';
    if (!file_exists($seedFile)) {
        throw new RuntimeException("No se encontró el archivo de datos semilla: {$seedFile}");
    }

    logMsg("Leyendo archivo de datos semilla: 02_seed.sql...", 'info');
    $seedSql = file_get_contents($seedFile);
    $pdo->exec($seedSql);
    logMsg("Datos iniciales insertados en la base de datos.", 'success');

    // 6. Actualizar contraseñas con password_hash() real (PASSWORD_BCRYPT)
    logMsg("Generando hashes Bcrypt reales para los 6 usuarios del sistema...", 'info');

    $usuariosPasswords = [
        'admin@abs.edu.co'        => 'Admin2026!',
        'registro@abs.edu.co'     => 'Registro2026!',
        'coordinacion@abs.edu.co' => 'Coord2026!',
        'docente@abs.edu.co'      => 'Docente2026!',
        'practicas@abs.edu.co'    => 'Practicas2026!',
        'estudiante@abs.edu.co'   => 'Estudiante2026!'
    ];

    $updateStmt = $pdo->prepare("UPDATE `usuarios` SET `password_hash` = :hash WHERE `correo` = :correo");

    foreach ($usuariosPasswords as $correo => $passwordPlano) {
        $hashBcrypt = password_hash($passwordPlano, PASSWORD_BCRYPT, ['cost' => 10]);
        $updateStmt->execute([
            ':hash'   => $hashBcrypt,
            ':correo' => $correo
        ]);
        logMsg("Contraseña actualizada para {$correo} [{$passwordPlano}]", 'info');
    }

    // 7. Auditoría de instalación
    $auditStmt = $pdo->prepare("
        INSERT INTO `auditoria` (`id_usuario`, `accion`, `entidad`, `id_registro`, `valor_nuevo`, `motivo`, `ip_origen`)
        VALUES (1, 'SETUP_COMPLETED', 'sistema', '0', :json, 'Instalación y aprovisionamiento completado', '127.0.0.1')
    ");
    $auditStmt->execute([
        ':json' => json_encode([
            'status' => 'OK',
            'tablas_creadas' => 8,
            'usuarios_iniciales' => count($usuariosPasswords),
            'timestamp' => date('c')
        ])
    ]);

    logMsg("¡Instalación completada con éxito!", 'success');
    $success = true;

} catch (Throwable $e) {
    logMsg("Error en aprovisionamiento: " . $e->getMessage(), 'error');
    $success = false;
}

if ($isCli) {
    exit($success ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador SGI-ABS - Aprovisionamiento</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f8fafc;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --accent-amber: #f59e0b;
            --border-color: #334155;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            width: 100%;
            max-width: 850px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        .header {
            padding: 24px 30px;
            border-bottom: 1px solid var(--border-color);
            background: rgba(15, 23, 42, 0.4);
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 1.6rem;
            color: #fff;
        }
        .header p {
            margin: 0;
            color: #94a3b8;
            font-size: 0.95rem;
        }
        .status-banner {
            padding: 16px 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .status-banner.success {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--accent-green);
            border-bottom: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-banner.error {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--accent-red);
            border-bottom: 1px solid rgba(239, 68, 68, 0.3);
        }
        .content {
            padding: 30px;
        }
        .credentials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }
        .credentials-table th, .credentials-table td {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            text-align: left;
        }
        .credentials-table th {
            background-color: rgba(15, 23, 42, 0.6);
            color: #cbd5e1;
            font-weight: 600;
        }
        .credentials-table tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.02);
        }
        .badge-role {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #3b82f6;
            color: #fff;
        }
        .log-box {
            background-color: #0b1120;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
            font-family: Consolas, monospace;
            font-size: 0.85rem;
            max-height: 280px;
            overflow-y: auto;
        }
        .log-entry {
            margin: 4px 0;
            line-height: 1.4;
        }
        .log-entry.info { color: #94a3b8; }
        .log-entry.success { color: var(--accent-green); }
        .log-entry.warning { color: var(--accent-amber); }
        .log-entry.error { color: var(--accent-red); }
        .footer {
            padding: 18px 30px;
            background: rgba(15, 23, 42, 0.5);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
        }
        .btn {
            background-color: var(--accent-blue);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistema de Gestión Institucional (SGI-ABS)</h1>
            <p>Aprovisionamiento de Base de Datos y Semillas Iniciales</p>
        </div>

        <div class="status-banner <?= $success ? 'success' : 'error' ?>">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <?php if ($success): ?>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                <?php else: ?>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                <?php endif; ?>
            </svg>
            <span><?= $success ? 'Instalación y Configuración Completada Satisfactoriamente' : 'Se presentaron errores durante la instalación' ?></span>
        </div>

        <div class="content">
            <?php if ($success): ?>
                <h3 style="margin-top: 0; color: #e2e8f0;">Credenciales de Acceso Inicial</h3>
                <p style="color: #94a3b8; font-size: 0.85rem;">Todas las contraseñas han sido encriptadas con algoritmos seguros Bcrypt.</p>
                <table class="credentials-table">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Correo Electrónico</th>
                            <th>Contraseña Inicial</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge-role" style="background:#dc2626;">ADMIN</span></td>
                            <td>admin@abs.edu.co</td>
                            <td><code>Admin2026!</code></td>
                            <td>Administrador Principal</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role" style="background:#ea580c;">REGISTRO</span></td>
                            <td>registro@abs.edu.co</td>
                            <td><code>Registro2026!</code></td>
                            <td>Valeria Gómez Registro</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role" style="background:#7c3aed;">COORDINACION</span></td>
                            <td>coordinacion@abs.edu.co</td>
                            <td><code>Coord2026!</code></td>
                            <td>Carlos Herrera Coordinador</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role" style="background:#0284c7;">DOCENTE</span></td>
                            <td>docente@abs.edu.co</td>
                            <td><code>Docente2026!</code></td>
                            <td>Marta Rodríguez Docente</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role" style="background:#059669;">RESP_PRACTICAS</span></td>
                            <td>practicas@abs.edu.co</td>
                            <td><code>Practicas2026!</code></td>
                            <td>Javier Silva Prácticas</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role" style="background:#4b5563;">ESTUDIANTE</span></td>
                            <td>estudiante@abs.edu.co</td>
                            <td><code>Estudiante2026!</code></td>
                            <td>Santiago López Aprendiz</td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>

            <h4 style="color: #cbd5e1; margin-bottom: 8px;">Bitácora de Instalación</h4>
            <div class="log-box">
                <?php foreach ($logs as $log): ?>
                    <div class="log-entry <?= htmlspecialchars($log['type']) ?>">
                        [<?= htmlspecialchars($log['time']) ?>] <?= htmlspecialchars($log['text']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="footer">
            <a href="../public/index.php" class="btn">Ir al Sistema SGI-ABS &rarr;</a>
        </div>
    </div>
</body>
</html>
