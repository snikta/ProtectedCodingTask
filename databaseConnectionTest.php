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
        $sanitisedTableName = $this->testInstance->conn->real_escape_string($tableName);
        $result = $this->testInstance->insert($tableName, $data);
        if ($result) {
            $sanitisedOldData = $this->testInstance->sanitiseData($data, true);
            $insert_id = $this->testInstance->conn->insert_id;
            $query = $this->testInstance->query('SELECT * FROM ' . $sanitisedTableName . ' WHERE id = ' . $insert_id);
            if ($query && $query->num_rows) {
                $query = $query->fetch_object();
                $sanitisedNewData = $this->testInstance->sanitiseData($query, true);
                $allAreEqual = true;
                foreach ($sanitisedNewData as $fieldName => $fieldValue) {
                    if (array_key_exists($fieldName, $sanitisedOldData)) {
                        $cmp = $fieldValue == $sanitisedOldData[$fieldName];
                        $cmp_strict = $fieldValue === $sanitisedOldData[$fieldName];
                        $this->assertEquals($sanitisedOldData[$fieldName], $fieldValue);
                        $this->assertTrue($cmp_strict);
                    }
                }
            }
        } else {
            echo $this->testInstance->conn->error;
        }
    }
}
?>