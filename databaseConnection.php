<?php
class DatabaseConnection {
    public $conn;
    private $host;
    private $username;
    private $password;
    private $databaseName;
    private $fieldTypes;

    function __construct($host, $username, $password, $databaseName) {
        // set the private variables to the values
        // passed to this constructor function
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->databaseName = $databaseName;
        // attempt to connect
        $this->connect();
    }

    public function setFieldTypes($fieldTypes) {
        foreach($fieldTypes as $fieldName => $fieldType) {
            $fieldType = strtoupper($fieldType);
            if ($fieldType != 'INT' && $fieldType != 'TINYINT' && $fieldType != 'VARCHAR') {
                continue; // unrecognized field type
            }
            $fieldName = $this->conn->real_escape_string($fieldName);
            $this->fieldTypes[$fieldName] = $fieldType;
        }
    }

    public function getFieldType($fieldName) {
        $fieldName = $this->conn->real_escape_string($fieldName);
        return $this->fieldTypes[$fieldName];
    }

    public function connect() {
        // attempt to connect to the database using mysqli
        // with the values provided to the constructor
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->databaseName);
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function sanitiseData($data, $returnAssocArray = false) {
        $assocArray = [];
        $fieldNames = [];
        $fieldValues = [];
        /* iterate through the $data array,
         * using real_escape_string to prevent
         * SQL injection, quoting string
         * values and coercing number values
         * to ints */
        foreach ($data as $fieldName => $fieldValue) {
            $fieldType = $this->getFieldType($fieldName);
            $fieldName = $this->conn->real_escape_string($fieldName);
            $fieldNames[] = $fieldName;
            if ($fieldType == 'INT') {
                $fieldValue = (int) $this->conn->real_escape_string($fieldValue);
            } else if ($fieldType == 'VARCHAR') {
                $fieldValue = '\'' . $this->conn->real_escape_string($fieldValue) . '\'';
            } else if ($fieldType == 'TINYINT') {
                /* first coerce to bool, then int */
                $fieldValue = (int) (bool) $fieldValue;
            }
            $fieldValues[] = $fieldValue;
            $assocArray[$fieldName] = $fieldValue;
        }
        return $returnAssocArray ? $assocArray : [$fieldNames, $fieldValues];
    }

    public function insert($tableName, $data) {
        // $data is an associative array mapping field names to values
        // all values will be sanitised before the query is executed
        $tableName = $this->conn->real_escape_string($tableName);
        list($fieldNames, $fieldValues) = $this->sanitiseData($data);
        $sql = 'INSERT INTO ' . $tableName . ' (' . implode(',', $fieldNames) . ') VALUES(';
        $sql .= implode(',', $fieldValues);
        $sql .= ')';
        return $this->conn->query($sql);
    }

    public function update($tableName, $data, $whereParams) {
        // $data is an associative array mapping field names to values
        // all values will be sanitised before the query is executed
        $tableName = $this->conn->real_escape_string($tableName);
        list($fieldNames, $fieldValues) = $this->sanitiseData($data);
        $set = [];
        $fieldCount = count($fieldNames);
        for ($i = 0; $i < $fieldCount; $i++) {
            $set[] = $fieldNames[$i] . ' = ' . $fieldValues[$i];
        }
        list($whereFieldNames, $whereFieldValues) = $this->sanitiseData($whereParams);
        $where = [];
        $whereFieldCount = count($whereFieldNames);
        for ($i = 0; $i < $whereFieldCount; $i++) {
            $where[] = $whereFieldNames[$i] . ' = ' . $whereFieldValues[$i];
        }
        $sql = 'UPDATE ' . $tableName . ' SET ' . implode($set, ',') . ' WHERE ' . implode($where, ' AND ');
        return $this->conn->query($sql);
    }
}
/* these values are hardcoded for
 * the purposes of this exercise;
 * in production we would store them
 * in environment variables and retrieve
 * them with PHP's getenv function
 */
$dbConn = new DatabaseConnection('localhost', 'joshatkins', 'protectedDotNet123!', 'protecteddotnet');
/* hardcoded again. alternatively,
 * we could run a SQL query against
 * information_schema.COLUMNS */
$dbConn->setFieldTypes([
    'id' => 'INT',
    'firstName' => 'VARCHAR',
    'lastName' => 'VARCHAR',
    'userName' => 'VARCHAR',
    'dateCreated' => 'INT',
    'darkMode' => 'TINYINT'
]);
?>