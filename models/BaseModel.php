<?php
declare(strict_types=1);

/**
 * =====================================================================
 * Sistema de Gestión Institucional y Académica (SGI-ABS)
 * Archivo: BaseModel.php (en models/)
 * Propósito: Clase base para Data Access Objects (DAO) con auditoría
 *            integrada, abstracción CRUD segura y PDO preparado
 * =====================================================================
 */

abstract class BaseModel {
    protected PDO $db;
    protected string $table = '';
    protected string $pk = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca un registro por su clave primaria
     */
    public function findById(int $id): ?array {
        try {
            $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->pk}` = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en {$this->table}::findById({$id}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene todos los registros que cumplan con las condiciones especificadas
     */
    public function findAll(array $where = [], string $orderBy = ''): array {
        try {
            [$whereSql, $params] = $this->buildWhere($where);
            $sql = "SELECT * FROM `{$this->table}`" . $whereSql;

            if (!empty($orderBy)) {
                // Sanitizar orderBy para prevenir inyección directa
                $sanitizedOrder = preg_replace('/[^a-zA-Z0-9_,\. ]/', '', $orderBy);
                $sql .= " ORDER BY {$sanitizedOrder}";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en {$this->table}::findAll(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna el primer registro que cumpla con los filtros
     */
    public function findOne(array $where): ?array {
        try {
            [$whereSql, $params] = $this->buildWhere($where);
            $sql = "SELECT * FROM `{$this->table}`" . $whereSql . " LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            error_log("Error en {$this->table}::findOne(): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Inserta un nuevo registro y registra el evento en auditoría
     *
     * @return int|false ID del registro creado o false en caso de error
     */
    public function create(array $data): int|false {
        try {
            if (empty($data)) {
                return false;
            }

            $columns = array_keys($data);
            $colList = implode('`, `', $columns);
            $paramKeys = array_map(fn($col) => ':' . $col, $columns);
            $valList = implode(', ', $paramKeys);

            $sql = "INSERT INTO `{$this->table}` (`{$colList}`) VALUES ({$valList})";
            $stmt = $this->db->prepare($sql);

            $params = [];
            foreach ($data as $col => $val) {
                $params[':' . $col] = $val;
            }

            $stmt->execute($params);
            $insertId = (int)$this->db->lastInsertId();

            // Auditoría automática de creación
            Audit::log(
                'CREATE',
                $this->table,
                (string)$insertId,
                null,
                $data,
                "Creación de registro en {$this->table}"
            );

            return $insertId;
        } catch (PDOException $e) {
            error_log("Error en {$this->table}::create(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un registro por su clave primaria y audita el cambio de estado
     */
    public function update(int $id, array $data): bool {
        try {
            if (empty($data)) {
                return false;
            }

            // Obtener estado anterior para trazabilidad inmutable
            $oldData = $this->findById($id);
            if ($oldData === null) {
                return false;
            }

            $setClauses = [];
            $params = [':_pk_id' => $id];

            foreach ($data as $col => $val) {
                $paramName = ':u_' . $col;
                $setClauses[] = "`{$col}` = {$paramName}";
                $params[$paramName] = $val;
            }

            $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setClauses) . " WHERE `{$this->pk}` = :_pk_id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            if ($success) {
                // Auditoría automática de actualización
                Audit::log(
                    'UPDATE',
                    $this->table,
                    (string)$id,
                    $oldData,
                    $data,
                    "Actualización de registro en {$this->table}"
                );
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error en {$this->table}::update({$id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminación física (Hard Delete) de registros
     */
    public function delete(int $id): bool {
        try {
            $oldData = $this->findById($id);
            if ($oldData === null) {
                return false;
            }

            $sql = "DELETE FROM `{$this->table}` WHERE `{$this->pk}` = :id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([':id' => $id]);

            if ($success) {
                Audit::log(
                    'DELETE',
                    $this->table,
                    (string)$id,
                    $oldData,
                    null,
                    "Eliminación física de registro en {$this->table}"
                );
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error en {$this->table}::delete({$id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construye cláusula WHERE y parámetros seguros asociados
     */
    protected function buildWhere(array $conditions): array {
        if (empty($conditions)) {
            return ['', []];
        }

        $clauses = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            $paramName = ':w_' . $safeCol;

            if ($value === null) {
                $clauses[] = "`{$safeCol}` IS NULL";
            } else {
                $clauses[] = "`{$safeCol}` = {$paramName}";
                $params[$paramName] = $value;
            }
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }
}
