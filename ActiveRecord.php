<?php
const NUMERIC_TYPES = [
    'integer',
    'int',
    'tinyint',
    'smallint',
    'mediumint',
    'bigint',
    'decimal',
    'numeric',
    'dec',
    'fixed',
    'float',
    'real',
    'double precision',
    'double'
];

class DatabaseConnection {
    public $conn;
    private $host;
    private $username;
    private $password;

    function __construct($host, $username, $password) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    public function connect() {
        $this->conn = mysqli_connect($this->host, $this->username, $this->password);
    }
}

class Table {
    private $databaseInstance;
    private $table_name;
    public $column_types = [];
    public $column_is_numeric = [];

    function __construct($databaseInstance, $table_name) {
        $this->databaseInstance = $databaseInstance;
        $this->table_name = $this->databaseInstance->escape($table_name);

        $results = mysqli_query($this->databaseInstance->conn, 'SELECT COLUMN_NAME, data_type FROM information_schema.COLUMNS WHERE table_schema = \'' . $this->databaseInstance->name . '\' AND table_name = \'' . $this->table_name . '\'');
        if ($results && mysqli_num_rows($results)) {
            foreach ($results as $result) {
                $this->column_types[$result['COLUMN_NAME']] = $result['data_type'];
                $this->column_is_numeric[$result['COLUMN_NAME']] = array_search($result['data_type'], NUMERIC_TYPES) !== false;
            }
        }
    }

    public function find($id) {
        $result = mysqli_query($this->databaseInstance->conn, 'SELECT * FROM ' . $this->table_name . ' WHERE id = ' . intval($id) . ' LIMIT 1');
        if ($result && mysqli_num_rows($result)) {
            $record = new Record($this->databaseInstance, $this->table_name, mysqli_fetch_object($result));
            return $record;
        }
        return false;
    }

    public function delete($where_params) {
        return mysqli_query($this->databaseInstance->conn, 'DELETE FROM ' . $this->table_name . ' WHERE ' . $this->get_where_params_sql($where_params));
    }

    private function get_where_params_sql($params) {
        $set = [];
        foreach ($params as $field_name => $value) {
            $sanitized_field_name = $this->databaseInstance->escape($field_name);
            if ($value === 'null' || $value === null) {
                $set[] = $sanitized_field_name . ' IS NULL';
            } else {
                if ($this->databaseInstance->tables[$this->table_name]->column_is_numeric[$field_name]) {
                    $set[] = $sanitized_field_name . ' = ' . $this->databaseInstance->escape($value) . '';
                } else {
                    $set[] = $sanitized_field_name . ' = \'' . $this->databaseInstance->escape(utf8_encode($value)) . '\'';
                }
            }
        }
        return implode(' AND ', $set);
    }

    public function where($params, $order_by = null, $count = false) {
        $result = mysqli_query($this->databaseInstance->conn, 'SELECT ' . ($count ? 'COUNT(1) AS count' : '*') . ' FROM ' . $this->table_name . ' WHERE ' . $this->get_where_params_sql($params) . ($order_by ? ' ORDER BY ' . $order_by : ''));
        if ($result && ($result_count = mysqli_num_rows($result))) {
            $results = [];
            for ($i = 0, $len = $result_count; $i < $len; $i++) {
                $results[] = new Record($this->databaseInstance, $this->table_name, mysqli_fetch_object($result));
            }
            return $results;
        }
        return false;
    }

    public function getNextInsertId() {
		$result = mysqli_query($this->databaseInstance->conn, 'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_NAME = \'' . $this->table_name . '\' AND TABLE_SCHEMA = \'' . $this->databaseInstance->name . '\'');
		if ($result && mysqli_num_rows($result)) {
            if ($result = mysqli_fetch_object($result)) {
                return $result->AUTO_INCREMENT;
            }
        }
        return false;
	}
}

class Record {
    private $databaseInstance;
    private $table_name;
    public $id = null;
    public $fields = [];
    public $data = [];

    function __construct($databaseInstance, $table_name, $data = null) {
        $this->databaseInstance = $databaseInstance;
        $this->table_name = $this->databaseInstance->escape($table_name);
        if (isset($data)) {
            $this->data = $data;
            if (isset($data->id)) {
                $this->id = $data->id;
                unset($this->data->id);
            }
        } else {
            $this->data = (object)[];
        }
    }

    public function save() {
        if (!isset($this->data)) {
            return false;
        }
        if (isset($this->id)) {
            $set = [];
            foreach ($this->data as $field_name => $value) {
                $sanitized_field_name = $this->databaseInstance->escape($field_name);
                if ($value === 'null' || $value === null) {
                    $set[] = $sanitized_field_name . ' = null';
                } else {
                    if ($this->databaseInstance->tables[$this->table_name]->column_is_numeric[$field_name]) {
                        $set[] = $sanitized_field_name . ' = ' . $this->databaseInstance->escape($value) . '';
                    } else {
                        $set[] = $sanitized_field_name . ' = \'' . $this->databaseInstance->escape(utf8_encode($value)) . '\'';
                    }
                }
            }
            return mysqli_query($this->databaseInstance->conn, 'UPDATE ' . $this->table_name . ' SET ' . implode(', ', $set) . ' WHERE id = ' . intval($this->id));
        } else {
            $field_names = ['id'];
            $values = [$this->id === null ? 'null' : intval($this->id)];
            foreach ($this->data as $field_name => $value) {
                $field_names[] = $this->databaseInstance->escape($field_name);
                if ($value === null) {
                    $values[] = 'null';
                } else {
                    $sanitized_value = $this->databaseInstance->escape($value);
                    if ($this->databaseInstance->tables[$this->table_name]->column_is_numeric[$field_name]) {
                        $values[] = $sanitized_value;
                    } else {
                        $values[] = '\'' . $sanitized_value . '\'';
                    }
                }
            }
            return mysqli_query($this->databaseInstance->conn, 'INSERT INTO ' . $this->table_name . ' (' . implode(', ', $field_names) . ') VALUES(' . implode(', ', $values) . ')'); 
        }
    }
}

class Database {
    public $conn;
    public $name;
    public $tables = [];

    function __construct($conn, $database_name) {
        $this->conn = $conn;
        $this->name = $this->escape($database_name);
        mysqli_select_db($this->conn, $this->name);
        $this->init_tables();
    }
    
    public function escape($str) {
        return mysqli_real_escape_string($this->conn, $str);
    }

    public function init_tables() {
        $results = mysqli_query($this->conn, 'SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $this->name . '\'');
        if ($results && mysqli_num_rows($results)) {
            foreach ($results as $result) {
                $table_name = $result['TABLE_NAME'];
                $this->tables[$table_name] = new Table($this, $table_name);
            }
        }
    }
}
?>