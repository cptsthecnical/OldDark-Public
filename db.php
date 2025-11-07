<?php
// db.php (o el nombre que uses para tu clase)

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

        // Desencripta la contraseña (manteniendo tu lógica original)
        $decryptPassword = tokenEncryption::decrypt($password, $key, $iv);

        // Configuración PDO
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Esencial para la seguridad
        ];

        try {
            $this->conn = new PDO($dsn, $username, $decryptPassword, $options);
        } catch (\PDOException $e) {
            die("Error de conexión PDO: " . $e->getMessage());
        }
    }

    // --- Método de Ejecución Central (Seguridad y Escape Automático) ---

    /**
     * Ejecuta cualquier consulta SQL de forma segura usando sentencias preparadas.
     * Los datos en $params son escapados automáticamente por PDO.
     * @param string $sql La sentencia SQL con marcadores (:nombre).
     * @param array $params Los parámetros asociativos (ej: [':id' => 1]).
     */
    private function executeStatement($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params); // PDO escapa y enlaza los datos aquí.

            $sql_upper = strtoupper(trim(substr($sql, 0, 6)));

            if ($sql_upper === 'SELECT') {
                return $stmt->fetchAll(); // Devuelve los resultados
            }
            
            if ($sql_upper === 'INSERT') {
                return (int)$this->conn->lastInsertId(); // Devuelve el ID insertado
            }

            return (int)$stmt->rowCount(); // Devuelve las filas afectadas

        } catch (\PDOException $e) {
            die("Error al ejecutar la consulta: " . $e->getMessage() . " en la consulta: " . $sql);
        }
    }

    // --- Métodos ORM (Sencillos de Usar) ---

    /**
     * 🔍 Obtiene registros de una tabla.
     * @param string $columns Columnas a seleccionar (ej: "id, nombre").
     * @param string $table Nombre de la tabla.
     * @param array $where_params Condición WHERE como array [columna => valor]. Por defecto: trae todo.
     * @return array
     */
    public function select($columns, $table, $where_params = []) {
        $sql = "SELECT $columns FROM $table";
        $params = [];
        $where_parts = [];
        
        // 🐍 Bucle (foreach) para construir automáticamente la cláusula WHERE
        foreach ($where_params as $column => $value) {
            $marker = ":w_{$column}"; // Marcador de posición con nombre único
            $where_parts[] = "$column = $marker";
            $params[$marker] = $value; // El valor se añade aquí, PDO lo escapa.
        }

        if (!empty($where_parts)) {
            $sql .= " WHERE " . implode(' AND ', $where_parts);
        }
        
        // Se pueden añadir aquí otras opciones como ORDER BY o LIMIT si es necesario.

        return $this->executeStatement($sql, $params);
    }
    # Uso SIN WHERE (trae todos): $rows = $this->select("col1, col2", "tabla");
    # Uso CON WHERE: $rows = $this->select("col1", "tabla", ['id' => $user_id, 'activo' => 1]);


    /**
     * ➕ Inserta un registro en una tabla.
     * @param string $table Nombre de la tabla.
     * @param array $data Array asociativo de [columna => valor].
     * @return int ID de la última fila insertada.
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = [];
        $params = [];
        
        // 🐍 Bucle (foreach) para generar automáticamente los marcadores y los parámetros.
        foreach ($data as $column => $value) {
            $marker = ":{$column}"; // Marcador con el nombre de la columna
            $placeholders[] = $marker;
            $params[$marker] = $value; // El valor se añade aquí, PDO lo escapa.
        }
        
        $placeholders_string = implode(', ', $placeholders);
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders_string)";
        
        return $this->executeStatement($query, $params);
    }
    # Uso: $id = $this->insert("tabla", ["nombre" => $name_input, "email" => $email_input]);


    /**
     * 🔄 Actualiza registros en una tabla.
     * @param string $table Nombre de la tabla.
     * @param array $data Array [columna => valor] a actualizar.
     * @param array $where_params Condición WHERE como array [columna => valor].
     * @return int Número de filas afectadas.
     */
    public function update($table, $data, $where_params) {
        $set_parts = [];
        $params = [];
        $where_parts = [];

        // 🐍 1. Bucle (foreach) para construir la cláusula SET
        foreach ($data as $column => $value) {
            $marker = ":set_{$column}"; // Marcador único para el SET
            $set_parts[] = "$column = $marker";
            $params[$marker] = $value;
        }
        $set_string = implode(', ', $set_parts);
        
        // 🐍 2. Bucle (foreach) para construir la cláusula WHERE
        foreach ($where_params as $column => $value) {
            $marker = ":w_{$column}"; // Marcador único para el WHERE
            $where_parts[] = "$column = $marker";
            $params[$marker] = $value;
        }
        $where_string = implode(' AND ', $where_parts);

        if (empty($where_string)) {
             die("Error de seguridad: Se requiere una condición WHERE para la actualización.");
        }

        $query = "UPDATE $table SET $set_string WHERE $where_string";
        return $this->executeStatement($query, $params);
    }
    # Uso: $this->update("tabla", ["nombre" => $new_name], ['id' => $user_id]);


    /**
     * ❌ Elimina registros de una tabla.
     * @param string $table Nombre de la tabla.
     * @param array $where_params Condición WHERE como array [columna => valor]. Por defecto: trae todo.
     * @return int Número de filas eliminadas.
     */
    public function destruction($table, $where_params = []) {
        $params = [];
        $where_parts = [];

        // 🐍 Bucle (foreach) para construir la cláusula WHERE
        foreach ($where_params as $column => $value) {
            $marker = ":w_{$column}";
            $where_parts[] = "$column = $marker";
            $params[$marker] = $value;
        }
        $where_string = implode(' AND ', $where_parts);
        
        $query = "DELETE FROM $table";
        if (!empty($where_string)) {
            $query .= " WHERE $where_string";
        } else {
             // ⚠️ Advertencia de seguridad antes de borrar toda la tabla
             if (count($where_params) === 0) {
                 // return 0; // Podrías devolver 0 para evitar el borrado masivo
                 // o forzar un error:
                 // die("Error de seguridad: La eliminación total de la tabla no está permitida sin WHERE.");
             }
        }
        
        return $this->executeStatement($query, $params);
    }
    # Uso SIN WHERE (trae todos): $this->destruction("tabla");
    # Uso CON WHERE: $this->destruction("tabla", ['id' => $user_id]);
}
