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
    private $tableName;
    public $columnTypes = [];
    public $columnIsNumeric = [];

    function __construct($databaseInstance, $tableName) {
        $this->databaseInstance = $databaseInstance;
        $this->tableName = $this->databaseInstance->escape($tableName);

        $results = pg_query($this->databaseInstance->pdo, 'SELECT COLUMN_NAME, data_type FROM information_schema.COLUMNS WHERE table_schema = \'' . $this->databaseInstance->name . '\' AND TABLE_NAME = \'' . $this->tableName . '\'');
        if ($results) {
            while ($result = pg_fetch_object($results)) {
                $this->columnTypes[$result->column_name] = $result->data_type;
                $this->columnIsNumeric[$result->column_name] = array_search($result->data_type, NUMERIC_TYPES) !== false;
            }
        }
    }

    public function find($id) {
        $result = pg_query($this->databaseInstance->pdo, 'SELECT * FROM ' . $this->databaseInstance->name. '.' . $this->tableName . ' WHERE id = ' . intval($id) . ' LIMIT 1');
        if ($result && ($result = pg_fetch_object($results))) {
            $record = new Record($this->databaseInstance, $this->tableName, mysqli_fetch_object($result));
            return $record;
        }
        return false;
    }

    public function delete($whereParams) {
        return pg_query($this->databaseInstance->pdo, 'DELETE FROM ' . $this->databaseInstance->name . '.' . $this->tableName . ' WHERE ' . $this->getWhereParamsSql($whereParams));
    }

    private function getWhereParamsSql($params) {
        $set = [];
        foreach ($params as $fieldName => $value) {
            $sanitizedFieldName = $this->databaseInstance->escape($fieldName);
            if ($value === 'null' || $value === null) {
                $set[] = $sanitizedFieldName . ' IS NULL';
            } else {
                if ($this->databaseInstance->tables[$this->tableName]->columnIsNumeric[$fieldName]) {
                    $set[] = $sanitizedFieldName . ' = ' . $this->databaseInstance->escape($value) . '';
                } else {
                    $set[] = $sanitizedFieldName . ' = \'' . $this->databaseInstance->escape(utf8_encode($value)) . '\'';
                }
            }
        }
        return implode(' AND ', $set);
    }

    public function where($params, $orderBy = null, $count = false) {
        $result = pg_query($this->databaseInstance->pdo, 'SELECT ' . ($count ? 'COUNT(1) AS count' : '*') . ' FROM ' . $this->databaseInstance->name. '.' . $this->tableName . ' WHERE ' . $this->getWhereParamsSql($params) . ($orderBy ? ' ORDER BY ' . $orderBy : ''));
        if ($result) {
            $results = [];
            while ($row = pg_fetch_object($result)) {
                $results[] = new Record($this->databaseInstance, $this->tableName, $row);
            }
            return $results;
        }
        return false;
    }

    public function getNextInsertId() {
        $result = pg_query($this->databaseInstance->pdo, 'select nextval(pg_get_serial_sequence(\'' . $this->tableName . '\', \'id\'))');
        if ($result) {
            if ($result = pg_fetch_object($result)) {
                return $result->nextval;
            }
        }
        return false;
    }
    
    public function getInsertId() {
        $result = pg_query($this->databaseInstance->pdo, 'select currval(pg_get_serial_sequence(\'' . $this->tableName . '\', \'id\'))');
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
    private $tableName;
    public $id = null;
    public $fields = [];
    public $data = [];

    function __construct($databaseInstance, $tableName, $data = null) {
        $this->databaseInstance = $databaseInstance;
        $this->tableName = $this->databaseInstance->escape($tableName);
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
            foreach ($this->data as $fieldName => $value) {
                $sanitizedFieldName = $this->databaseInstance->escape($fieldName);
                if ($value === 'null' || $value === null) {
                    $set[] = $sanitizedFieldName . ' = null';
                } else {
                    if ($this->databaseInstance->tables[$this->tableName]->columnIsNumeric[$fieldName]) {
                        $set[] = $sanitizedFieldName . ' = ' . $this->databaseInstance->escape($value) . '';
                    } else {
                        $set[] = $sanitizedFieldName . ' = \'' . $this->databaseInstance->escape(utf8_encode($value)) . '\'';
                    }
                }
            }
            return pg_query($this->databaseInstance->pdo, 'UPDATE ' . $this->databaseInstance->name. '.' . $this->tableName . ' SET ' . implode(', ', $set) . ' WHERE id = ' . intval($this->id));
        } else {
            $fieldNames = [];
            $values = [];
            foreach ($this->data as $fieldName => $value) {
                $fieldNames[] = $this->databaseInstance->escape($fieldName);
                if ($value === null) {
                    $values[] = 'null';
                } else {
                    $sanitized_value = $this->databaseInstance->escape($value);
                    if ($this->databaseInstance->tables[$this->tableName]->columnIsNumeric[$fieldName]) {
                        $values[] = $sanitized_value;
                    } else {
                        $values[] = '\'' . $sanitized_value . '\'';
                    }
                }
            }
            return pg_query($this->databaseInstance->pdo, 'INSERT INTO ' . $this->databaseInstance->name. '.' . $this->tableName . ' (' . implode(', ', $fieldNames) . ') VALUES(' . implode(', ', $values) . ')');
        }
    }
}

class Database {
    public $pdo;
    public $name;
    public $tables = [];

    function __construct($pdo, $databaseName) {
        $this->pdo = $pdo;
        $this->name = $this->escape($databaseName);
        $this->initTables();
    }

    public function escape($str) {
        return pg_escape_string($this->pdo, $str);
    }

    public function initTables() {
        $results = pg_query($this->pdo, 'SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $this->name . '\' GROUP BY TABLE_NAME');
        if ($results) {
            while ($result = pg_fetch_object($results)) {
                $tableName = $result->tableName;
                $this->tables[$tableName] = new Table($this, $tableName);
            }
        }
    }
}
?>