<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: config.php
 * Propósito: Constantes globales, configuración de entorno y autoloader
 * =====================================================================
 */

// Rutas absolutas del sistema
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');

// Metadatos de la aplicación
define('APP_NAME', 'SGI-ABS');
define('APP_VERSION', '1.0.0');
define('APP_INST', 'Institución Educativa ABS');

// Roles del sistema
define('ROL_ADMIN', 'ADMIN');
define('ROL_REGISTRO', 'REGISTRO_ACADEMICO');
define('ROL_COORDINACION', 'COORDINACION');
define('ROL_DOCENTE', 'DOCENTE');
define('ROL_PRACTICAS', 'RESP_PRACTICAS');
define('ROL_ESTUDIANTE', 'ESTUDIANTE');

// Parámetros de carga de archivos
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xlsx']);
define('ALLOWED_MIMES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]);

// Configuración de sesiones y seguridad
define('SESSION_LIFETIME', 7200); // 2 horas (en segundos)
define('MAX_LOGIN_ATTEMPTS', 5);

// Parámetros académicos
define('NOTA_MINIMA_APROBACION', 3.00);

// Zona horaria institucional
date_default_timezone_set('America/Bogota');

// Autocargador PSR-4 simplificado para el proyecto SGI-ABS
spl_autoload_register(function (string $class): void {
    // Si viene con namespaces tipo App\Models\ o Models\, extraer nombre base
    $parts = explode('\\', $class);
    $className = end($parts);

    $directories = [
        BASE_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR,
        BASE_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR,
        BASE_PATH . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR,
        BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR,
    ];

    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
