<?php
declare(strict_types=1);

/**
 * =====================================================================
 * SGI-ABS — Semilla de 138 Usuarios con Roles Distribuidos
 * Propósito: Generar y poblar la base de datos con un total exacto de
 *            138 usuarios manteniendo un único Administrador.
 * =====================================================================
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/core/Audit.php';

$pdo = Database::getInstance();

echo "=== SGI-ABS: Generador de Población de Usuarios ===" . PHP_EOL;

// 1. Verificar estado actual
$countActual = (int)$pdo->query("SELECT COUNT(*) FROM `usuarios`")->fetchColumn();
echo "Total usuarios actuales en base de datos: {$countActual}" . PHP_EOL;

// Contraseña por defecto para los usuarios generados: Usuario2026!
$defaultPassword = 'Usuario2026!';
$passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 10]);

// Nombres y apellidos colombianos/hispanos para generar combinaciones realistas
$nombresMasc = [
    'Alejandro', 'Andrés', 'Camilo', 'Carlos', 'Cristian', 'Daniel', 'David', 'Diego',
    'Eduardo', 'Esteban', 'Felipe', 'Gabriel', 'Gustavo', 'Hernán', 'Javier', 'Jorge',
    'José', 'Juan', 'Julián', 'Leonardo', 'Lucas', 'Luis', 'Manuel', 'Mateo', 'Mauricio',
    'Miguel', 'Nicolás', 'Oscar', 'Pablo', 'Ricardo', 'Rodrigo', 'Santiago', 'Sebastian'
];

$nombresFem = [
    'Adriana', 'Alejandra', 'Ana', 'Andrea', 'Angélica', 'Camila', 'Carolina', 'Catalina',
    'Claudia', 'Daniela', 'Diana', 'Elena', 'Gabriela', 'Isabella', 'Jessica', 'Johanna',
    'Laura', 'Lucía', 'Luisa', 'Marcela', 'María', 'Mariana', 'Marta', 'Natalia', 'Paula',
    'Sandra', 'Sara', 'Silvia', 'Sofía', 'Tatiana', 'Valeria', 'Vanessa', 'Viviana'
];

$apellidosLista = [
    'Alvarez', 'Arango', 'Arias', 'Bermúdez', 'Betancur', 'Botero', 'Cárdenas', 'Cardona',
    'Castillo', 'Castro', 'Correa', 'Delgado', 'Díaz', 'Espinosa', 'Fernández', 'Franco',
    'Gallego', 'García', 'Giraldo', 'Gómez', 'González', 'Gutiérrez', 'Guzmán', 'Hernández',
    'Herrera', 'Jiménez', 'Londoño', 'López', 'Marín', 'Martínez', 'Mejía', 'Molina',
    'Moncada', 'Montoya', 'Mora', 'Morales', 'Moreno', 'Muñoz', 'Navarro', 'Ochoa',
    'Orozco', 'Ortiz', 'Osorio', 'Pérez', 'Pineda', 'Ramírez', 'Restrepo', 'Reyes',
    'Ríos', 'Rodríguez', 'Rojas', 'Ruiz', 'Salazar', 'Sánchez', 'Silva', 'Suárez',
    'Torres', 'Uribe', 'Valencia', 'Vargas', 'Vásquez', 'Vélez', 'Zapata'
];

// Distribución objetivo para tener exactamente 138 usuarios en total:
// ADMIN:              1 (ya existe admin@abs.edu.co)
// ESTUDIANTE:       100 (1 existente + 99 nuevos)
// DOCENTE:           20 (1 existente + 19 nuevos)
// COORDINACION:       6 (1 existente + 5 nuevos)
// REGISTRO_ACADEMICO: 6 (1 existente + 5 nuevos)
// RESP_PRACTICAS:     5 (1 existente + 4 nuevos)
// TOTAL:            138

$rolesNuevosRequeridos = [
    'ESTUDIANTE'         => 99,
    'DOCENTE'            => 19,
    'COORDINACION'       => 5,
    'REGISTRO_ACADEMICO' => 5,
    'RESP_PRACTICAS'     => 4,
];

// Obtener documentos y correos existentes para evitar colisiones
$correosExistentes = $pdo->query("SELECT `correo` FROM `usuarios`")->fetchAll(PDO::FETCH_COLUMN);
$correosExistentes = array_flip(array_map('strtolower', $correosExistentes));

$docsExistentes = $pdo->query("SELECT `documento` FROM `usuarios` WHERE `documento` IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$docsExistentes = array_flip($docsExistentes);

$docBase = 1010000000;
$creados = 0;

$stmtInsert = $pdo->prepare("
    INSERT INTO `usuarios` (
        `rol`, `correo`, `password_hash`, `nombres`, `apellidos`,
        `tipo_documento`, `documento`, `fecha_nacimiento`, `telefono`,
        `autorizacion_datos`, `fecha_autorizacion`, `mfa_habilitado`, `estado`, `ultimo_acceso`
    ) VALUES (
        :rol, :correo, :password_hash, :nombres, :apellidos,
        :tipo_documento, :documento, :fecha_nacimiento, :telefono,
        :autorizacion_datos, :fecha_autorizacion, :mfa_habilitado, :estado, NULL
    )
");

$pdo->beginTransaction();

try {
    foreach ($rolesNuevosRequeridos as $rol => $cantidad) {
        for ($i = 0; $i < $cantidad; $i++) {
            // Alternar género
            $esFem = (($creados + $i) % 2 === 0);
            $nombre1 = $esFem ? $nombresFem[array_rand($nombresFem)] : $nombresMasc[array_rand($nombresMasc)];
            $nombre2 = (rand(0, 1) === 1) ? ($esFem ? $nombresFem[array_rand($nombresFem)] : $nombresMasc[array_rand($nombresMasc)]) : '';
            $nombres = trim("{$nombre1} {$nombre2}");

            $apell1 = $apellidosLista[array_rand($apellidosLista)];
            $apell2 = $apellidosLista[array_rand($apellidosLista)];
            $apellidos = "{$apell1} {$apell2}";

            // Generar documento único
            do {
                $doc = (string)($docBase + rand(100000, 9999999));
            } while (isset($docsExistentes[$doc]));
            $docsExistentes[$doc] = true;

            // Generar correo único
            $cleanNombre = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $nombre1));
            $cleanNombre = preg_replace('/[^a-z]/', '', $cleanNombre);
            $cleanApell = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $apell1));
            $cleanApell = preg_replace('/[^a-z]/', '', $cleanApell);

            $baseMail = "{$cleanNombre}.{$cleanApell}";
            $correo = "{$baseMail}@abs.edu.co";
            $suffix = 1;
            while (isset($correosExistentes[$correo])) {
                $suffix++;
                $correo = "{$baseMail}{$suffix}@abs.edu.co";
            }
            $correosExistentes[$correo] = true;

            // Fecha de nacimiento coherente según el rol
            if ($rol === 'ESTUDIANTE') {
                $year = rand(1998, 2007);
            } else {
                $year = rand(1975, 1995);
            }
            $month = str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT);
            $day   = str_pad((string)rand(1, 28), 2, '0', STR_PAD_LEFT);
            $fechaNacimiento = "{$year}-{$month}-{$day}";

            $telefono = '3' . rand(10, 25) . rand(1000000, 9999999);
            $tipoDoc = (rand(0, 20) === 0) ? 'CE' : 'CC';

            // 95% activos, algunos pocos inactivos o bloqueados para simulación realista
            $randEstado = rand(1, 100);
            if ($randEstado <= 94) {
                $estado = 'ACTIVO';
            } elseif ($randEstado <= 97) {
                $estado = 'INACTIVO';
            } else {
                $estado = 'BLOQUEADO';
            }

            $stmtInsert->execute([
                ':rol'                => $rol,
                ':correo'             => $correo,
                ':password_hash'      => $passwordHash,
                ':nombres'            => $nombres,
                ':apellidos'          => $apellidos,
                ':tipo_documento'     => $tipoDoc,
                ':documento'          => $doc,
                ':fecha_nacimiento'   => $fechaNacimiento,
                ':telefono'           => $telefono,
                ':autorizacion_datos' => 1,
                ':fecha_autorizacion' => date('Y-m-d H:i:s'),
                ':mfa_habilitado'     => 0,
                ':estado'             => $estado,
            ]);

            $creados++;
        }
    }

    $pdo->commit();
    echo "¡Se insertaron exitosamente {$creados} nuevos usuarios!" . PHP_EOL;

} catch (Throwable $e) {
    $pdo->rollBack();
    echo "ERROR en la inserción: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// 2. Validación final de conteos
$totalFinal = (int)$pdo->query("SELECT COUNT(*) FROM `usuarios`")->fetchColumn();
echo "Total final de usuarios en la base de datos: {$totalFinal}" . PHP_EOL;

$resumenRoles = $pdo->query("SELECT `rol`, COUNT(*) AS total FROM `usuarios` GROUP BY `rol` ORDER BY total DESC")->fetchAll();
echo "Distribución por rol:" . PHP_EOL;
foreach ($resumenRoles as $r) {
    echo " - {$r['rol']}: {$r['total']}" . PHP_EOL;
}

// 3. Registrar auditoría del proceso masivo
$auditStmt = $pdo->prepare("
    INSERT INTO `auditoria` (`id_usuario`, `accion`, `entidad`, `id_registro`, `valor_nuevo`, `motivo`, `ip_origen`)
    VALUES (1, 'SEED_USERS_138', 'usuarios', '0', :json, 'Generación masiva de 138 usuarios en base de datos', '127.0.0.1')
");
$auditStmt->execute([
    ':json' => json_encode([
        'total_usuarios' => $totalFinal,
        'distribucion'   => array_column($resumenRoles, 'total', 'rol'),
        'timestamp'      => date('c')
    ])
]);

echo "Evento de auditoría registrado correctamente." . PHP_EOL;
