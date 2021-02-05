<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once('databaseConnection.php');
require_once('check_data_equality.php');

final class DatabaseConnectionTest extends TestCase
{
    public $testInstance;

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
        $this->assertFalse((bool) $dbConn->conn->connect_errno);

        $this->testInstance = $dbConn;
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
            check_data_equality($this, $insert_id, $tableName, $data);
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
            $this->assertTrue(check_data_equality($this, $id, $tableName, $data));
        } else {
            echo $this->testInstance->conn->error;
        }
    }
}
?>