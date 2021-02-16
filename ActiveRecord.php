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
    private $tableName;
    public $columnTypes = [];
    public $columnIsNumeric = [];

    function __construct($databaseInstance, $tableName) {
        $this->databaseInstance = $databaseInstance;
        $this->tableName = $this->databaseInstance->escape($tableName);

        $results = mysqli_query($this->databaseInstance->conn, 'SELECT COLUMN_NAME, data_type FROM information_schema.COLUMNS WHERE table_schema = \'' . $this->databaseInstance->name . '\' AND table_name = \'' . $this->tableName . '\'');
        if ($results && mysqli_num_rows($results)) {
            foreach ($results as $result) {
                $this->columnTypes[$result['COLUMN_NAME']] = $result['data_type'];
                $this->columnIsNumeric[$result['COLUMN_NAME']] = array_search($result['data_type'], NUMERIC_TYPES) !== false;
            }
        }
    }

    public function find($id) {
        $result = mysqli_query($this->databaseInstance->conn, 'SELECT * FROM ' . $this->tableName . ' WHERE id = ' . intval($id) . ' LIMIT 1');
        if ($result && mysqli_num_rows($result)) {
            $record = new Record($this->databaseInstance, $this->tableName, mysqli_fetch_object($result));
            return $record;
        }
        return false;
    }

    public function delete($where_params) {
        return mysqli_query($this->databaseInstance->conn, 'DELETE FROM ' . $this->tableName . ' WHERE ' . $this->getWhereParamsSql($where_params));
    }

    private function getWhereParamsSql($params) {
        $set = [];
        foreach ($params as $field_name => $value) {
            $sanitizedFieldName = $this->databaseInstance->escape($field_name);
            if ($value === 'null' || $value === null) {
                $set[] = $sanitizedFieldName . ' IS NULL';
            } else {
                if ($this->databaseInstance->tables[$this->tableName]->columnIsNumeric[$field_name]) {
                    $set[] = $sanitizedFieldName . ' = ' . $this->databaseInstance->escape($value) . '';
                } else {
                    $set[] = $sanitizedFieldName . ' = \'' . $this->databaseInstance->escape(utf8_encode($value)) . '\'';
                }
            }
        }
        return implode(' AND ', $set);
    }

    public function where($params, $orderBy = null, $count = false) {
        $result = mysqli_query($this->databaseInstance->conn, 'SELECT ' . ($count ? 'COUNT(1) AS count' : '*') . ' FROM ' . $this->tableName . ' WHERE ' . $this->getWhereParamsSql($params) . ($orderBy ? ' ORDER BY ' . $orderBy : ''));
        if ($result && ($resultCount = mysqli_num_rows($result))) {
            $results = [];
            for ($i = 0, $len = $resultCount; $i < $len; $i++) {
                $results[] = new Record($this->databaseInstance, $this->tableName, mysqli_fetch_object($result));
            }
            return $results;
        }
        return false;
    }

    public function getNextInsertId() {
		$result = mysqli_query($this->databaseInstance->conn, 'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE table_name = \'' . $this->tableName . '\' AND TABLE_SCHEMA = \'' . $this->databaseInstance->name . '\'');
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
            foreach ($this->data as $field_name => $value) {
                $sanitizedFieldName = $this->databaseInstance->escape($field_name);
                if ($value === 'null' || $value === null) {
                    $set[] = $sanitizedFieldName . ' = null';
                } else {
                    if ($this->databaseInstance->tables[$this->tableName]->columnIsNumeric[$field_name]) {
                        $set[] = $sanitizedFieldName . ' = ' . $this->databaseInstance->escape($value) . '';
                    } else {
                        $set[] = $sanitizedFieldName . ' = \'' . $this->databaseInstance->escape(utf8_encode($value)) . '\'';
                    }
                }
            }
            return mysqli_query($this->databaseInstance->conn, 'UPDATE ' . $this->tableName . ' SET ' . implode(', ', $set) . ' WHERE id = ' . intval($this->id));
        } else {
            $field_names = ['id'];
            $values = [$this->id === null ? 'null' : intval($this->id)];
            foreach ($this->data as $field_name => $value) {
                $field_names[] = $this->databaseInstance->escape($field_name);
                if ($value === null) {
                    $values[] = 'null';
                } else {
                    $sanitized_value = $this->databaseInstance->escape($value);
                    if ($this->databaseInstance->tables[$this->tableName]->columnIsNumeric[$field_name]) {
                        $values[] = $sanitized_value;
                    } else {
                        $values[] = '\'' . $sanitized_value . '\'';
                    }
                }
            }
            return mysqli_query($this->databaseInstance->conn, 'INSERT INTO ' . $this->tableName . ' (' . implode(', ', $field_names) . ') VALUES(' . implode(', ', $values) . ')'); 
        }
    }
}

class Database {
    public $conn;
    public $name;
    public $tables = [];

    function __construct($conn, $databaseName) {
        $this->conn = $conn;
        $this->name = $this->escape($databaseName);
        mysqli_select_db($this->conn, $this->name);
        $this->initTables();
    }
    
    public function escape($str) {
        return mysqli_real_escape_string($this->conn, $str);
    }

    public function initTables() {
        $results = mysqli_query($this->conn, 'SELECT table_name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = \'' . $this->name . '\'');
        if ($results && mysqli_num_rows($results)) {
            foreach ($results as $result) {
                $tableName = $result['tableName'];
                $this->tables[$tableName] = new Table($this, $tableName);
            }
        }
    }
}
?>