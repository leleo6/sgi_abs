-- =====================================================================
-- Sistema de Gestión Institucional y Académica (SGI-ABS)
-- Archivo: 01_schema.sql
-- Fecha de creación: 2026-11-04
-- Versión del esquema: 3.0 (8 tablas normalizadas)
-- Motor: MySQL 8.0+ / MariaDB 10.6+ (InnoDB, utf8mb4)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `sgi_abs` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sgi_abs`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS `v_estado_academico`;
DROP VIEW IF EXISTS `v_materias_pendientes`;
DROP TABLE IF EXISTS `auditoria`;
DROP TABLE IF EXISTS `evidencias`;
DROP TABLE IF EXISTS `procesos_grado`;
DROP TABLE IF EXISTS `resultados`;
DROP TABLE IF EXISTS `matriculas`;
DROP TABLE IF EXISTS `materias`;
DROP TABLE IF EXISTS `programas`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. TABLA: usuarios
-- Gestión centralizada de credenciales, roles y perfil de estudiantes
-- ---------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rol` ENUM('ADMIN','REGISTRO_ACADEMICO','COORDINACION','DOCENTE','RESP_PRACTICAS','ESTUDIANTE') NOT NULL DEFAULT 'ESTUDIANTE',
  `correo` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombres` VARCHAR(80) NOT NULL,
  `apellidos` VARCHAR(80) NOT NULL,
  `tipo_documento` ENUM('CC','TI','CE','PASAPORTE','OTRO') NULL,
  `documento` VARCHAR(20) NULL,
  `fecha_nacimiento` DATE NULL,
  `telefono` VARCHAR(20) NULL,
  `autorizacion_datos` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_autorizacion` DATETIME NULL,
  `mfa_habilitado` TINYINT(1) NOT NULL DEFAULT 0,
  `estado` ENUM('ACTIVO','BLOQUEADO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `ultimo_acceso` DATETIME NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_usuarios_correo` (`correo`),
  KEY `idx_usuarios_rol` (`rol`),
  KEY `idx_usuarios_estado` (`estado`),
  KEY `idx_usuarios_documento` (`tipo_documento`, `documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. TABLA: programas
-- Programas académicos y versiones curriculares vigentes
-- ---------------------------------------------------------------------
CREATE TABLE `programas` (
  `id_programa` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `regimen` ENUM('SENA','IES','ETDH','OTRO') NOT NULL,
  `unidad_medida` ENUM('MATERIAS','CREDITOS','HORAS') NOT NULL DEFAULT 'MATERIAS',
  `total_periodos` TINYINT UNSIGNED NOT NULL,
  `nota_minima` DECIMAL(4,2) NOT NULL DEFAULT 3.00,
  `vigente_desde` DATE NOT NULL,
  `vigente_hasta` DATE NULL,
  `estado` ENUM('BORRADOR','PUBLICADA','CERRADA') NOT NULL DEFAULT 'BORRADOR',
  PRIMARY KEY (`id_programa`),
  UNIQUE KEY `uk_programa_version` (`codigo`, `version`),
  KEY `idx_programas_codigo` (`codigo`),
  KEY `idx_programas_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. TABLA: materias
-- Asignaturas de la malla con prerrequisito autoreferenciado
-- ---------------------------------------------------------------------
CREATE TABLE `materias` (
  `id_materia` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_programa` INT UNSIGNED NOT NULL,
  `id_materia_requisito` INT UNSIGNED NULL,
  `codigo` VARCHAR(30) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `periodo_bloque` TINYINT UNSIGNED NOT NULL,
  `tipo` ENUM('OBLIGATORIA','ELECTIVA') NOT NULL DEFAULT 'OBLIGATORIA',
  `intensidad` DECIMAL(6,2) NULL,
  PRIMARY KEY (`id_materia`),
  UNIQUE KEY `uk_materia_programa` (`id_programa`, `codigo`),
  KEY `idx_materias_programa` (`id_programa`),
  KEY `idx_materias_requisito` (`id_materia_requisito`),
  CONSTRAINT `fk_materias_programa` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id_programa`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_materias_requisito` FOREIGN KEY (`id_materia_requisito`) REFERENCES `materias` (`id_materia`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. TABLA: matriculas
-- Vinculación de un estudiante a un programa/versión académica
-- ---------------------------------------------------------------------
CREATE TABLE `matriculas` (
  `id_matricula` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NOT NULL,
  `id_programa` INT UNSIGNED NOT NULL,
  `periodo_ingreso` VARCHAR(10) NOT NULL,
  `fecha_ingreso` DATE NOT NULL,
  `estado` ENUM('ACTIVA','SUSPENDIDA','FINALIZADA','RETIRADA') NOT NULL DEFAULT 'ACTIVA',
  PRIMARY KEY (`id_matricula`),
  UNIQUE KEY `uk_matricula` (`id_usuario`, `id_programa`),
  KEY `idx_matriculas_usuario` (`id_usuario`),
  KEY `idx_matriculas_programa` (`id_programa`),
  KEY `idx_matriculas_estado` (`estado`),
  CONSTRAINT `fk_matriculas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_matriculas_programa` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id_programa`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. TABLA: resultados
-- Registro de calificaciones ordinarias, habilitaciones y homologaciones
-- ---------------------------------------------------------------------
CREATE TABLE `resultados` (
  `id_resultado` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_matricula` INT UNSIGNED NOT NULL,
  `id_materia` INT UNSIGNED NOT NULL,
  `registrado_por` INT UNSIGNED NOT NULL,
  `periodo` VARCHAR(10) NOT NULL,
  `numero_intento` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `tipo_evaluacion` ENUM('ORDINARIA','HABILITACION','HOMOLOGACION') NOT NULL DEFAULT 'ORDINARIA',
  `calificacion` DECIMAL(4,2) NULL,
  `estado_resultado` ENUM('EN_CURSO','APROBADO','REPROBADO','HABILITACION_PROGRAMADA','APROBADO_HABILITACION','REPROBADO_HABILITACION','HOMOLOGADO') NOT NULL DEFAULT 'EN_CURSO',
  `soporte_documental` VARCHAR(255) NULL,
  `vigente` TINYINT(1) NULL DEFAULT 1,
  `motivo_anulacion` VARCHAR(255) NULL,
  `registrado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `version_fila` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_resultado`),
  KEY `idx_resultados_matricula` (`id_matricula`),
  KEY `idx_resultados_materia` (`id_materia`),
  KEY `idx_resultados_registrado_por` (`registrado_por`),
  KEY `idx_resultados_estado` (`estado_resultado`),
  KEY `idx_resultados_vigente` (`vigente`),
  CONSTRAINT `fk_resultados_matricula` FOREIGN KEY (`id_matricula`) REFERENCES `matriculas` (`id_matricula`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_resultados_materia` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_resultados_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. TABLA: procesos_grado
-- Prácticas empresariales, pasantías, proyectos y paz y salvo final
-- ---------------------------------------------------------------------
CREATE TABLE `procesos_grado` (
  `id_proceso` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_matricula` INT UNSIGNED NOT NULL,
  `modalidad` ENUM('PRACTICA','PASANTIA','PROYECTO') NOT NULL,
  `entidad_nit` VARCHAR(20) NULL,
  `entidad_razon_social` VARCHAR(150) NULL,
  `convenio_numero` VARCHAR(40) NULL,
  `convenio_inicio` DATE NULL,
  `convenio_fin` DATE NULL,
  `titulo_proyecto` VARCHAR(200) NULL,
  `supervisor_nombre` VARCHAR(100) NULL,
  `supervisor_correo` VARCHAR(120) NULL,
  `fecha_inicio` DATE NULL,
  `fecha_fin` DATE NULL,
  `horas_cumplidas` DECIMAL(6,1) NOT NULL DEFAULT 0,
  `estado` ENUM('ELEGIBLE','AVALADO','EN_EJECUCION','FINALIZADO','INTERRUMPIDO') NOT NULL DEFAULT 'ELEGIBLE',
  `activo` TINYINT(1) NULL DEFAULT 1,
  `excepcion_acta` VARCHAR(80) NULL,
  `excepcion_motivo` VARCHAR(500) NULL,
  `excepcion_autorizada_por` INT UNSIGNED NULL,
  `excepcion_vigente_hasta` DATE NULL,
  `codigo_verificacion` VARCHAR(40) NULL,
  `paz_salvo_emitido_en` DATETIME NULL,
  `paz_salvo_emitido_por` INT UNSIGNED NULL,
  `paz_salvo_estado` ENUM('EMITIDO','REVOCADO') NULL,
  `paz_salvo_motivo_revocatoria` VARCHAR(300) NULL,
  PRIMARY KEY (`id_proceso`),
  UNIQUE KEY `uk_proceso_activo` (`id_matricula`, `activo`),
  KEY `idx_procesos_matricula` (`id_matricula`),
  KEY `idx_procesos_estado` (`estado`),
  KEY `idx_procesos_codigo_verificacion` (`codigo_verificacion`),
  CONSTRAINT `fk_procesos_matricula` FOREIGN KEY (`id_matricula`) REFERENCES `matriculas` (`id_matricula`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_procesos_excepcion_por` FOREIGN KEY (`excepcion_autorizada_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_procesos_paz_salvo_por` FOREIGN KEY (`paz_salvo_emitido_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. TABLA: evidencias
-- Documentos y entregables de práctica/proyecto con trazabilidad hash
-- ---------------------------------------------------------------------
CREATE TABLE `evidencias` (
  `id_evidencia` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_proceso` INT UNSIGNED NOT NULL,
  `tipo_evidencia` VARCHAR(60) NOT NULL,
  `version_entrega` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `nombre_archivo` VARCHAR(255) NULL,
  `ruta_almacenamiento` VARCHAR(400) NULL,
  `mime_type` VARCHAR(100) NULL,
  `tamano_bytes` BIGINT UNSIGNED NULL,
  `hash_sha256` CHAR(64) NULL,
  `subido_por` INT UNSIGNED NULL,
  `fecha_entrega` DATETIME NULL,
  `estado` ENUM('PENDIENTE','ENTREGADA','EN_REVISION','APROBADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
  `id_revisor` INT UNSIGNED NULL,
  `fecha_revision` DATETIME NULL,
  `observaciones` VARCHAR(500) NULL,
  PRIMARY KEY (`id_evidencia`),
  KEY `idx_evidencias_proceso` (`id_proceso`),
  KEY `idx_evidencias_estado` (`estado`),
  KEY `idx_evidencias_subido_por` (`subido_por`),
  KEY `idx_evidencias_revisor` (`id_revisor`),
  CONSTRAINT `fk_evidencias_proceso` FOREIGN KEY (`id_proceso`) REFERENCES `procesos_grado` (`id_proceso`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_evidencias_subido_por` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_evidencias_revisor` FOREIGN KEY (`id_revisor`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. TABLA: auditoria
-- Bitácora inmutable de eventos, modificaciones y seguridad
-- ---------------------------------------------------------------------
CREATE TABLE `auditoria` (
  `id_auditoria` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED NULL,
  `accion` VARCHAR(40) NOT NULL,
  `entidad` VARCHAR(60) NOT NULL,
  `id_registro` VARCHAR(40) NOT NULL,
  `valor_anterior` JSON NULL,
  `valor_nuevo` JSON NULL,
  `motivo` VARCHAR(300) NULL,
  `ip_origen` VARCHAR(45) NULL,
  `fecha_evento` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_usuario` (`id_usuario`),
  KEY `idx_auditoria_entidad_reg` (`entidad`, `id_registro`),
  KEY `idx_auditoria_fecha` (`fecha_evento`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- VISTAS DE ELEGIBILIDAD ACADÉMICA
-- ---------------------------------------------------------------------

-- Vista: v_materias_pendientes
-- Lista materias obligatorias sin resultado APROBADO, APROBADO_HABILITACION o HOMOLOGADO vigente
CREATE OR REPLACE VIEW `v_materias_pendientes` AS
SELECT 
    mt.id_matricula,
    mt.id_usuario,
    m.id_materia,
    m.codigo AS codigo_materia,
    m.nombre AS nombre_materia,
    m.periodo_bloque,
    m.intensidad
FROM matriculas mt
JOIN materias m ON m.id_programa = mt.id_programa AND m.tipo = 'OBLIGATORIA'
WHERE NOT EXISTS (
    SELECT 1 
    FROM resultados r
    WHERE r.id_matricula = mt.id_matricula
      AND r.id_materia = m.id_materia
      AND (r.vigente = 1 OR r.vigente IS NULL)
      AND r.estado_resultado IN ('APROBADO', 'APROBADO_HABILITACION', 'HOMOLOGADO')
);

-- Vista: v_estado_academico
-- Determina si el estudiante está 'LIMPIO' o 'CON_DEUDA_ACADEMICA'
CREATE OR REPLACE VIEW `v_estado_academico` AS
SELECT 
    x.id_matricula,
    x.id_usuario,
    x.materias_pendientes,
    x.habilitaciones_en_curso,
    CASE 
        WHEN x.materias_pendientes = 0 AND x.habilitaciones_en_curso = 0 THEN 'LIMPIO'
        ELSE 'CON_DEUDA_ACADEMICA'
    END AS estado_academico
FROM (
    SELECT 
        mt.id_matricula,
        mt.id_usuario,
        (
            SELECT COUNT(*) 
            FROM v_materias_pendientes p 
            WHERE p.id_matricula = mt.id_matricula
        ) AS materias_pendientes,
        (
            SELECT COUNT(*) 
            FROM resultados r
            WHERE r.id_matricula = mt.id_matricula 
              AND (r.vigente = 1 OR r.vigente IS NULL)
              AND r.estado_resultado = 'HABILITACION_PROGRAMADA'
        ) AS habilitaciones_en_curso
    FROM matriculas mt
) x;

-- ---------------------------------------------------------------------
-- DISPARADORES (TRIGGERS)
-- Evita ciclos donde una materia sea su propio prerrequisito
-- ---------------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER `trg_materias_no_autorequisito_ins`
BEFORE INSERT ON `materias`
FOR EACH ROW
BEGIN
    IF NEW.id_materia_requisito IS NOT NULL AND NEW.id_materia IS NOT NULL AND NEW.id_materia_requisito = NEW.id_materia THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Error SGI-ABS: Una materia no puede ser prerrequisito de si misma.';
    END IF;
END$$

CREATE TRIGGER `trg_materias_no_autorequisito_upd`
BEFORE UPDATE ON `materias`
FOR EACH ROW
BEGIN
    IF NEW.id_materia_requisito IS NOT NULL AND NEW.id_materia_requisito = NEW.id_materia THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Error SGI-ABS: Una materia no puede ser prerrequisito de si misma.';
    END IF;
END$$

DELIMITER ;
