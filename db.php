<?php

require_once('./iSNF/helpers/tokenEncryption.php');
require_once('./iSNF/kernel.php');

class db extends kernel {
    /**
     * @var PDO
     */
    private $conn;

    public function __construct() {
        $this->env();

        $host = getenv('DB_HOST');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');
        $database = getenv('DB_NAME');
        $key = getenv('KEY');
        $iv = getenv('IV');

        // Desencripta la contraseña que has tenido que dejar encriptada en el archivo .env
        $decryptPassword = tokenEncryption::decrypt($password, $key, $iv);

        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $username, $decryptPassword, $options);
        } catch (\PDOException $e) {
            die("Error de conexión PDO: " . $e->getMessage());
        }
    }

    // --- Métodos de ejecución internos ---

    /**
     * Ejecuta una consulta SELECT y devuelve los resultados.
     * @param string $sql La sentencia SQL.
     * @param array $params Los parámetros asociativos (ej: [':id' => 1]).
     * @return array
     */
    private function executeQuery(string $sql, array $params = []): array {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            die("Error al ejecutar la consulta: " . $e->getMessage() . " en la consulta: " . $sql);
        }
    }

    /**
     * Ejecuta una consulta que no devuelve resultados (INSERT, UPDATE, DELETE).
     * @param string $sql La sentencia SQL.
     * @param array $params Los parámetros asociativos (ej: [':nombre' => 'Elliot']).
     * @return int El número de filas afectadas o el ID de la última inserción.
     */
    private function executeNoQuery(string $sql, array $params = []): int {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            // Si es un INSERT, devuelve el ID de la última inserción
            if (strtoupper(substr(trim($sql), 0, 6)) === 'INSERT') {
                return (int)$this->conn->lastInsertId();
            }

            // En otro caso (UPDATE/DELETE), devuelve el número de filas afectadas
            return (int)$stmt->rowCount();

        } catch (\PDOException $e) {
            die("Error al ejecutar la consulta (NoQuery): " . $e->getMessage() . " en la consulta: " . $sql);
        }
    }

    // --- Métodos públicos de la base de datos ---

    /**
     * Realiza una consulta SELECT.
     * @param string $columns Columnas a seleccionar (ej: "col1, col2").
     * @param string $table Nombre de la tabla.
     * @param string $where_options Opciones WHERE/ORDER BY/LIMIT (ej: "WHERE col = :val ORDER BY id DESC").
     * @param array $params Parámetros asociativos para los marcadores de posición (ej: [':val' => 1]).
     * @return array
     */
    public function select(string $columns, string $table, string $where_options = '', array $params = []): array {
        $query = "SELECT $columns FROM $table $where_options";
        return $this->executeQuery($query, $params);
    }
    # Uso: $rows = $this->select("col1", "tabla", "WHERE id = :id AND nombre = :name", [':id' => 1, ':name' => 'Elliot']);


    /**
     * Realiza una consulta INSERT.
     * @param string $table Nombre de la tabla.
     * @param array $data Array asociativo de [columna => valor].
     * @return int El ID de la última fila insertada.
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        // Crea los marcadores de posición con nombre :columna1, :columna2, etc.
        $placeholders = ':' . implode(', :', array_keys($data));
        
        // Prepara los parámetros para la ejecución con el formato [':columna' => valor]
        $params = [];
        foreach ($data as $key => $value) {
            $params[":$key"] = $value;
        }

        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return $this->executeNoQuery($query, $params);
    }
    # Uso: $id = $this->insert("tabla", ["columna1" => 'valor1', "columna2" => 'valor2']);


    /**
     * Realiza una consulta UPDATE.
     * @param string $table Nombre de la tabla.
     * @param array $data Array asociativo de [columna => valor] a actualizar.
     * @param string $where La condición WHERE (ej: "id = :id_val AND nombre = :name_val").
     * @param array $where_params Parámetros asociativos para la condición WHERE.
     * @return int El número de filas afectadas.
     */
    public function update(string $table, array $data, string $where, array $where_params = []): int {
        $set_parts = [];
        $data_params = [];
        
        // Construye la parte SET y los parámetros de los datos
        foreach ($data as $column => $value) {
            $placeholder = ":set_$column";
            $set_parts[] = "$column = $placeholder";
            $data_params[$placeholder] = $value;
        }
        $set_string = implode(', ', $set_parts);

        // Combina los parámetros de SET y WHERE
        $params = array_merge($data_params, $where_params);

        $query = "UPDATE $table SET $set_string WHERE $where";
        return $this->executeNoQuery($query, $params);
    }
    # Uso: $rows_affected = $this->update("tabla", 
    #   ["columna" => 'nuevo_valor', "columna2" => 'otro_valor'], 
    #   "id = :cond_id", 
    #   [':cond_id' => 5]
    # );


    /**
     * Realiza una consulta DELETE.
     * @param string $table Nombre de la tabla.
     * @param string $where La condición WHERE (ej: "id = :id_val").
     * @param array $params Parámetros asociativos para la sentencia preparada.
     * @return int El número de filas eliminadas.
     */
    public function destruction(string $table, string $where = '1', array $params = []): int {
        $query = "DELETE FROM $table WHERE $where";
        return $this->executeNoQuery($query, $params);
    }
    # Uso: $rows_affected = $this->destruction("tabla", "columna = :val", [':val' => 'valor']); 
}
