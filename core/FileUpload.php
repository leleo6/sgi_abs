<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: FileUpload.php (en core/)
 * Propósito: Manejo seguro de subida, almacenamiento fuera de raíz web,
 *            descarga controlada y cálculo de integridad hash SHA-256
 * =====================================================================
 */

class FileUpload {
    /**
     * Valida, procesa y almacena un archivo subido en el sistema
     *
     * @param array $file Arreglo $_FILES['campo']
     * @param string $subdir Subcarpeta opcional dentro de UPLOAD_PATH
     * @return array [ok, path, name, mime, size, hash, error]
     */
    public static function upload(array $file, string $subdir = ''): array {
        $defaultResult = [
            'ok'    => false,
            'path'  => '',
            'name'  => $file['name'] ?? '',
            'mime'  => '',
            'size'  => (int)($file['size'] ?? 0),
            'hash'  => '',
            'error' => '',
        ];

        // 1. Validar estado de carga de PHP
        if (!isset($file['error']) || is_array($file['error'])) {
            $defaultResult['error'] = 'Parámetros de carga de archivo inválidos.';
            return $defaultResult;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $defaultResult['error'] = self::phpUploadErrorMessage($file['error']);
            return $defaultResult;
        }

        // 2. Validar tamaño máximo
        $maxBytes = defined('MAX_UPLOAD_BYTES') ? MAX_UPLOAD_BYTES : (10 * 1024 * 1024);
        if ($file['size'] > $maxBytes || $file['size'] <= 0) {
            $mb = round($maxBytes / (1024 * 1024), 1);
            $defaultResult['error'] = "El archivo excede el tamaño máximo permitido ({$mb} MB) o está vacío.";
            return $defaultResult;
        }

        // 3. Validar extensión permitida
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = defined('ALLOWED_EXTENSIONS')
            ? ALLOWED_EXTENSIONS
            : ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xlsx'];

        if (!in_array($ext, $allowedExtensions, true)) {
            $defaultResult['error'] = "Extensión no permitida: .{$ext}. Permitidas: " . implode(', ', $allowedExtensions);
            return $defaultResult;
        }

        // 4. Validar tipo MIME real mediante finfo (inspección de cabeceras binarias)
        $tmpPath = $file['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            $defaultResult['error'] = 'El archivo no fue subido mediante una petición HTTP POST válida.';
            return $defaultResult;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpPath);

        $allowedMimes = defined('ALLOWED_MIMES')
            ? ALLOWED_MIMES
            : [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];

        if (!in_array($realMime, $allowedMimes, true)) {
            $defaultResult['error'] = "El tipo MIME ({$realMime}) no es válido o no coincide con el contenido real del archivo.";
            return $defaultResult;
        }

        // 5. Calcular Hash SHA-256 para integridad y trazabilidad
        $hashSha256 = hash_file('sha256', $tmpPath);
        if ($hashSha256 === false) {
            $defaultResult['error'] = 'No fue posible calcular el hash de integridad del archivo.';
            return $defaultResult;
        }

        // 6. Preparar directorio de destino
        $baseUploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
        // PARCHE M-04: Limitar longitud del subdir y sanitizar path traversal
        $cleanSubdir = trim(str_replace(['..', '/', '\\'], DIRECTORY_SEPARATOR, $subdir), DIRECTORY_SEPARATOR);
        $cleanSubdir = substr($cleanSubdir, 0, 100); // Prevenir rutas excesivamente largas
        $targetDir = !empty($cleanSubdir) ? $baseUploadDir . DIRECTORY_SEPARATOR . $cleanSubdir : $baseUploadDir;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                $defaultResult['error'] = 'No fue posible crear la carpeta de almacenamiento de destino.';
                return $defaultResult;
            }
        }

        // 7. Nombre seguro único basado en hash y timestamp
        $storedFileName = $hashSha256 . '_' . time() . '.' . $ext;
        $destinationPath = $targetDir . DIRECTORY_SEPARATOR . $storedFileName;

        // 8. Mover archivo a destino seguro fuera de web root
        if (!move_uploaded_file($tmpPath, $destinationPath)) {
            $defaultResult['error'] = 'Fallo al mover el archivo al almacenamiento seguro.';
            return $defaultResult;
        }

        // Éxito: Retornar metadatos completos
        return [
            'ok'    => true,
            'path'  => $destinationPath,
            'name'  => $file['name'],
            'mime'  => $realMime,
            'size'  => (int)$file['size'],
            'hash'  => $hashSha256,
            'error' => '',
        ];
    }

    /**
     * Envía un archivo fuera de la raíz web al cliente HTTP de forma controlada
     */
    public static function download(string $ruta, string $nombreDescarga): void {
        if (!file_exists($ruta) || !is_readable($ruta)) {
            http_response_code(404);
            die("Error 404: El archivo solicitado no existe o no tiene permisos de lectura.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($ruta) ?: 'application/octet-stream';
        $size = filesize($ruta);

        // Limpiar cualquier buffer previo para evitar archivos corruptos
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Sanitizar nombre de descarga
        $safeDownloadName = basename(str_replace(['"', "'", ';'], '', $nombreDescarga));

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $safeDownloadName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $size);

        readfile($ruta);
        exit;
    }

    /**
     * Elimina un archivo físico del almacenamiento si existe
     */
    public static function delete(string $ruta): bool {
        if (file_exists($ruta) && is_file($ruta)) {
            return unlink($ruta);
        }
        return false;
    }

    /**
     * Mapea códigos de error de subida nativos de PHP
     */
    private static function phpUploadErrorMessage(int $code): string {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede la directiva upload_max_filesize del servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo excede la directiva MAX_FILE_SIZE especificada en el formulario.',
            UPLOAD_ERR_PARTIAL    => 'El archivo fue subido solo parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo para subir.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco del servidor.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida del archivo.',
            default               => 'Error desconocido durante la carga del archivo.',
        };
    }
}
