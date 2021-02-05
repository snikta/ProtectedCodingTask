<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once('databaseConnection.php');

final class DatabaseConnectionTest extends TestCase
{
    private $testInstance;

    /** @test */
    public function test_construct() {
        $host = 'localhost';
        $username = 'joshatkins';
        $password = 'protectedDotNet123!';
        $databaseName = 'protecteddotnet';
        
        $this->assertIsString($host, '');
        $this->assertIsString($username);
        $this->assertIsString($password);
        $this->assertIsString($databaseName);

        $this->assertNotEmpty($host);
        $this->assertNotEmpty($username);
        $this->assertNotEmpty($password);
        $this->assertNotEmpty($databaseName);

        $dbConn = new DatabaseConnection($host, $username, $password, $databaseName);
        $dbConn->setFieldTypes([
            'id' => 'INT',
            'firstName' => 'VARCHAR',
            'lastName' => 'VARCHAR',
            'userName' => 'VARCHAR',
            'dateCreated' => 'INT',
            'darkMode' => 'TINYINT'
        ]);
        $this->assertInstanceOf(DatabaseConnection::class, $dbConn);

        $this->testInstance = $dbConn;
    }

    private function check_data_equality($row_id, $tableName, $oldData) {
        $sanitisedTableName = $this->testInstance->conn->real_escape_string($tableName);
        $sanitisedOldData = $this->testInstance->sanitiseData($oldData, true);
        $query = $this->testInstance->query('SELECT * FROM ' . $sanitisedTableName . ' WHERE id = ' . $row_id);
        if ($query && $query->num_rows) {
            $query = $query->fetch_object();
            $sanitisedNewData = $this->testInstance->sanitiseData($query, true);
            foreach ($sanitisedNewData as $fieldName => $fieldValue) {
                if (array_key_exists($fieldName, $sanitisedOldData)) {
                    $cmp = $fieldValue == $sanitisedOldData[$fieldName];
                    $cmp_strict = $fieldValue === $sanitisedOldData[$fieldName];
                    $this->assertEquals($sanitisedOldData[$fieldName], $fieldValue);
                    $this->assertTrue($cmp_strict);
                    if (!$cmp_strict) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /** @test */
    public function test_insert() {
        if (!isset($this->testInstance)) {
            $this->test_construct();
        }
        $tableName = 'users';
        $data = [
            'firstName' => 'Neil "Alden"',
            'lastName' => 'Armstrong',
            'userName' => 'neil.a.armstrong',
            'dateCreated' => time(),
            'darkMode' => 1
        ];
        $result = $this->testInstance->insert($tableName, $data);
        if ($result) {
            $insert_id = $this->testInstance->conn->insert_id;
            $this->check_data_equality($insert_id, $tableName, $data);
        } else {
            echo $this->testInstance->conn->error;
        }
    }

    /** @test */
    public function test_update() {
        if (!isset($this->testInstance)) {
            $this->test_construct();
        }
        $tableName = 'users';
        $data = [
            'firstName' => 'Woody',
            'lastName' => 'Pride',
            'userName' => 'sheriff_woody',
            'dateCreated' => time(),
            'darkMode' => 0
        ];
        $id = 4;
        $whereParams = ['id' => $id];
        $result = $this->testInstance->update($tableName, $data, $whereParams);
        if ($result) {
            $this->check_data_equality($id, $tableName, $data);
        } else {
            echo $this->testInstance->conn->error;
        }
    }
}
?>