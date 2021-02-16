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
    public $pdo;

    function __construct() {
        $db = parse_url(getenv("DATABASE_URL"));
        $this->pdo = pg_connect("host=".$db["host"]." port=".$db["port"]." dbname=".ltrim($db["path"], "/")." user=".$db["user"]." password=".$db["pass"]." sslmode=require");
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

        $results = pg_query($this->databaseInstance->pdo, 'SELECT COLUMN_NAME, data_type FROM information_schema.COLUMNS WHERE table_schema = \'' . $this->databaseInstance->name . '\' AND table_name = \'' . $this->table_name . '\'');
        if ($results) {
            while ($result = pg_fetch_object($results)) {
                $this->column_types[$result->column_name] = $result->data_type;
                $this->column_is_numeric[$result->column_name] = array_search($result->data_type, NUMERIC_TYPES) !== false;
            }
        }
    }

    public function find($id) {
        $result = pg_query($this->databaseInstance->pdo, 'SELECT * FROM ' . $this->databaseInstance->name. '.' . $this->table_name . ' WHERE id = ' . intval($id) . ' LIMIT 1');
        if ($result && ($result = pg_fetch_object($results))) {
            $record = new Record($this->databaseInstance, $this->table_name, mysqli_fetch_object($result));
            return $record;
        }
        return false;
    }

    public function delete($where_params) {
        return pg_query($this->databaseInstance->pdo, 'DELETE FROM ' . $this->databaseInstance->name . '.' . $this->table_name . ' WHERE ' . $this->get_where_params_sql($where_params));
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
        $result = pg_query($this->databaseInstance->pdo, 'SELECT ' . ($count ? 'COUNT(1) AS count' : '*') . ' FROM ' . $this->databaseInstance->name. '.' . $this->table_name . ' WHERE ' . $this->get_where_params_sql($params) . ($order_by ? ' ORDER BY ' . $order_by : ''));
        if ($result) {
            $results = [];
            while ($row = pg_fetch_object($result)) {
                $results[] = new Record($this->databaseInstance, $this->table_name, $row);
            }
            return $results;
        }
        return false;
    }

    public function getNextInsertId() {
        $result = pg_query($this->databaseInstance->pdo, 'select nextval(pg_get_serial_sequence(\'' . $this->table_name . '\', \'id\'))');
        if ($result) {
            if ($result = pg_fetch_object($result)) {
                return $result->nextval;
            }
        }
        return false;
    }
    
    public function getInsertId() {
        $result = pg_query($this->databaseInstance->pdo, 'select currval(pg_get_serial_sequence(\'' . $this->table_name . '\', \'id\'))');
        if ($result) {
            if ($result = pg_fetch_object($result)) {
                return $result->currval;
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
            return pg_query($this->databaseInstance->pdo, 'UPDATE ' . $this->databaseInstance->name. '.' . $this->table_name . ' SET ' . implode(', ', $set) . ' WHERE id = ' . intval($this->id));
        } else {
            $field_names = [];
            $values = [];
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
            return pg_query($this->databaseInstance->pdo, 'INSERT INTO ' . $this->databaseInstance->name. '.' . $this->table_name . ' (' . implode(', ', $field_names) . ') VALUES(' . implode(', ', $values) . ')');
        }
    }
}

class Database {
    public $pdo;
    public $name;
    public $tables = [];

    function __construct($pdo, $database_name = 'clnmg') {
        $this->pdo = $pdo;
        $this->name = $this->escape($database_name);
        $this->init_tables();
    }

    public function escape($str) {
        return pg_escape_string($this->pdo, $str);
    }

    public function init_tables() {
        $results = pg_query($this->pdo, 'SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $this->name . '\' GROUP BY TABLE_NAME');
        if ($results) {
            while ($result = pg_fetch_object($results)) {
                $table_name = $result->table_name;
                $this->tables[$table_name] = new Table($this, $table_name);
            }
        }
    }
}
?>