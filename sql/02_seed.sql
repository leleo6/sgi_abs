-- =====================================================================
-- Sistema de Gestión Institucional y Académica (SGI-ABS)
-- Archivo: 02_seed.sql
-- Datos iniciales de prueba y parametrización de referencia
-- =====================================================================

USE `sgi_abs`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `auditoria`;
TRUNCATE TABLE `evidencias`;
TRUNCATE TABLE `procesos_grado`;
TRUNCATE TABLE `resultados`;
TRUNCATE TABLE `matriculas`;
TRUNCATE TABLE `materias`;
TRUNCATE TABLE `programas`;
TRUNCATE TABLE `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USUARIOS (6 roles del sistema)
-- Hash bcrypt precomputado: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi ('password')
-- Contraseñas reales asignadas (actualizadas dinámicamente por setup.php):
--   * admin@abs.edu.co         -> Admin2026!
--   * registro@abs.edu.co      -> Registro2026!
--   * coordinacion@abs.edu.co  -> Coord2026!
--   * docente@abs.edu.co       -> Docente2026!
--   * practicas@abs.edu.co     -> Practicas2026!
--   * estudiante@abs.edu.co    -> Estudiante2026!
-- ---------------------------------------------------------------------
INSERT INTO `usuarios` (
    `id_usuario`, `rol`, `correo`, `password_hash`, `nombres`, `apellidos`,
    `tipo_documento`, `documento`, `fecha_nacimiento`, `telefono`,
    `autorizacion_datos`, `fecha_autorizacion`, `mfa_habilitado`, `estado`, `ultimo_acceso`
) VALUES
(1, 'ADMIN', 'admin@abs.edu.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Principal', 'CC', '1000000001', '1985-05-10', '3001234567', 1, '2026-01-01 08:00:00', 0, 'ACTIVO', NULL),
(2, 'REGISTRO_ACADEMICO', 'registro@abs.edu.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Valeria', 'Gómez Registro', 'CC', '1000000002', '1990-03-12', '3109876543', 1, '2026-01-01 08:00:00', 0, 'ACTIVO', NULL),
(3, 'COORDINACION', 'coordinacion@abs.edu.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos', 'Herrera Coordinador', 'CC', '1000000003', '1982-11-20', '3157654321', 1, '2026-01-01 08:00:00', 0, 'ACTIVO', NULL),
(4, 'DOCENTE', 'docente@abs.edu.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marta', 'Rodríguez Docente', 'CC', '1000000004', '1988-07-15', '3206549871', 1, '2026-01-01 08:00:00', 0, 'ACTIVO', NULL),
(5, 'RESP_PRACTICAS', 'practicas@abs.edu.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Javier', 'Silva Prácticas', 'CC', '1000000005', '1986-09-30', '3183456789', 1, '2026-01-01 08:00:00', 0, 'ACTIVO', NULL),
(6, 'ESTUDIANTE', 'estudiante@abs.edu.co', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Santiago', 'López Aprendiz', 'CC', '1234567890', '2000-01-15', '3142223344', 1, '2026-01-15 09:30:00', 0, 'ACTIVO', NULL);

-- ---------------------------------------------------------------------
-- 2. PROGRAMAS
-- ---------------------------------------------------------------------
INSERT INTO `programas` (
    `id_programa`, `codigo`, `version`, `nombre`, `regimen`,
    `unidad_medida`, `total_periodos`, `nota_minima`, `vigente_desde`, `vigente_hasta`, `estado`
) VALUES
(1, 'ADSO', '2026-A', 'Análisis y Desarrollo de Software', 'SENA', 'MATERIAS', 6, 3.00, '2026-01-01', NULL, 'PUBLICADA');

-- ---------------------------------------------------------------------
-- 3. MATERIAS (6 obligatorias distribuidas en 3 periodos/bloques)
-- ---------------------------------------------------------------------
INSERT INTO `materias` (
    `id_materia`, `id_programa`, `id_materia_requisito`, `codigo`, `nombre`, `periodo_bloque`, `tipo`, `intensidad`
) VALUES
-- Periodo 1
(1, 1, NULL, 'ADSO-101', 'Algoritmia y Lógica de Programación', 1, 'OBLIGATORIA', 80.00),
(2, 1, NULL, 'ADSO-102', 'Bases de Datos Relacionales', 1, 'OBLIGATORIA', 80.00),
-- Periodo 2
(3, 1, 1,    'ADSO-201', 'Programación Orientada a Objetos', 2, 'OBLIGATORIA', 90.00),
(4, 1, 2,    'ADSO-202', 'Desarrollo Web Backend', 2, 'OBLIGATORIA', 90.00),
-- Periodo 3
(5, 1, 3,    'ADSO-301', 'Arquitectura de Software y APIs', 3, 'OBLIGATORIA', 100.00),
(6, 1, 4,    'ADSO-302', 'Pruebas y Calidad de Software', 3, 'OBLIGATORIA', 80.00);

-- ---------------------------------------------------------------------
-- 4. MATRICULAS (Estudiante en programa ADSO)
-- ---------------------------------------------------------------------
INSERT INTO `matriculas` (
    `id_matricula`, `id_usuario`, `id_programa`, `periodo_ingreso`, `fecha_ingreso`, `estado`
) VALUES
(1, 6, 1, '2026-1', '2026-01-15', 'ACTIVA');

-- ---------------------------------------------------------------------
-- 5. RESULTADOS (2 Aprobadas, 1 Reprobada)
-- ---------------------------------------------------------------------
INSERT INTO `resultados` (
    `id_resultado`, `id_matricula`, `id_materia`, `registrado_por`, `periodo`,
    `numero_intento`, `tipo_evaluacion`, `calificacion`, `estado_resultado`,
    `soporte_documental`, `vigente`, `motivo_anulacion`, `registrado_en`, `version_fila`
) VALUES
-- Materia 1 aprobada
(1, 1, 1, 4, '2026-1', 1, 'ORDINARIA', 4.20, 'APROBADO', 'ACTA-ORD-2026-01-101', 1, NULL, '2026-05-20 10:00:00', 1),
-- Materia 2 aprobada
(2, 1, 2, 4, '2026-1', 1, 'ORDINARIA', 3.80, 'APROBADO', 'ACTA-ORD-2026-01-102', 1, NULL, '2026-05-22 11:30:00', 1),
-- Materia 3 reprobada (requiere habilitación o repetición)
(3, 1, 3, 4, '2026-2', 1, 'ORDINARIA', 2.20, 'REPROBADO', 'ACTA-ORD-2026-02-201', 1, NULL, '2026-10-15 15:45:00', 1);

-- ---------------------------------------------------------------------
-- 6. REGISTRO INICIAL EN AUDITORIA
-- ---------------------------------------------------------------------
INSERT INTO `auditoria` (
    `id_usuario`, `accion`, `entidad`, `id_registro`,
    `valor_anterior`, `valor_nuevo`, `motivo`, `ip_origen`
) VALUES
(1, 'SEED_INIT', 'sistema', '0', NULL, JSON_OBJECT('status', 'inicializado', 'version', '3.0'), 'Carga de datos semilla de prueba', '127.0.0.1');
