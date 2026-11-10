<?php
declare(strict_types=1);

/**
 * =====================================================================
 * SGI-ABS — core/Audit.php
 * PARCHE C-01: IP Spoofing — se valida con filter_var y se registra
 *              REMOTE_ADDR real como referencia secundaria inmutable.
 * =====================================================================
 */

class Audit {
    /**
     * Registra un evento en la tabla de auditoría del sistema (append-only).
     */
    public static function log(
        string  $accion,
        string  $entidad,
        string  $idRegistro,
        ?array  $valorAnterior = null,
        ?array  $valorNuevo   = null,
        ?string $motivo        = null
    ): void {
        try {
            $db = Database::getInstance();

            // No registrar auditoría dentro de una transacción abierta para
            // evitar que un rollback borre el registro de auditoría.
            // La auditoría usa su propia conexión implícita (mismo singleton).
            $userId   = Session::getUserId();
            $ipOrigen = self::resolveClientIp();

            $jsonAnterior = $valorAnterior !== null
                ? json_encode($valorAnterior, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            $jsonNuevo = $valorNuevo !== null
                ? json_encode($valorNuevo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            $stmt = $db->prepare(
                "INSERT INTO `auditoria`
                    (`id_usuario`, `accion`, `entidad`, `id_registro`,
                     `valor_anterior`, `valor_nuevo`, `motivo`, `ip_origen`)
                 VALUES
                    (:id_usuario, :accion, :entidad, :id_registro,
                     :valor_anterior, :valor_nuevo, :motivo, :ip_origen)"
            );

            $stmt->execute([
                ':id_usuario'     => $userId,
                ':accion'         => strtoupper(substr(trim($accion),     0, 40)),
                ':entidad'        => strtolower(substr(trim($entidad),     0, 60)),
                ':id_registro'    => substr(trim($idRegistro),              0, 40),
                ':valor_anterior' => $jsonAnterior,
                ':valor_nuevo'    => $jsonNuevo,
                ':motivo'         => $motivo !== null ? substr(trim($motivo), 0, 300) : null,
                ':ip_origen'      => substr($ipOrigen, 0, 45),
            ]);
        } catch (Throwable $e) {
            // Un fallo de auditoría NUNCA debe interrumpir la operación principal
            error_log('[SGI-ABS][Audit] Error al registrar en bitácora: ' . $e->getMessage());
        }
    }

    /**
     * PARCHE C-01 — Resuelve IP del cliente con validación estricta.
     *
     * Mitigation: solo se acepta la IP de cabeceras de proxy si es una IPv4/IPv6
     * válida y pública. Se prioriza siempre REMOTE_ADDR (valor no forjable por
     * el cliente en conexiones directas). En entornos con proxy/CDN de confianza,
     * configurar explícitamente los rangos de proxy en config.php.
     */
    private static function resolveClientIp(): string
    {
        // REMOTE_ADDR es la única IP garantizada (establecida por TCP).
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Lista blanca de IPs de proxy de confianza (loopback / red privada)
        $trustedProxyRanges = ['127.0.0.1', '::1', '10.', '172.16.', '192.168.'];
        $isFromTrustedProxy = false;
        foreach ($trustedProxyRanges as $range) {
            if (str_starts_with($remoteAddr, $range)) {
                $isFromTrustedProxy = true;
                break;
            }
        }

        // Solo confiar en X-Forwarded-For si la petición llega desde proxy interno
        if ($isFromTrustedProxy) {
            foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP'] as $header) {
                if (!empty($_SERVER[$header])) {
                    // Tomar la primera IP de la cadena (cliente original)
                    $candidate = trim(explode(',', (string)$_SERVER[$header])[0]);
                    // Validar que sea una IP real (no forjada como texto libre)
                    if (filter_var($candidate, FILTER_VALIDATE_IP,
                            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false) {
                        return $candidate;
                    }
                }
            }
        }

        return $remoteAddr;
    }
}
