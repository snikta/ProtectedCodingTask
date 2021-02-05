<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require('databaseConnection.php');
require_once('user.php');
require_once('check_data_equality.php');

final class UserTest extends TestCase
{
    public $testInstance;

    /** @test */
    public function test_connect() {
        if (
            isset($this->testInstance) &&
            isset($this->testInstance->conn) &&
            !$this->testInstance->conn->connect_errno
        ) {
            return; // already connected
        }
        $host = 'localhost';
        $username = 'joshatkins';
        $password = 'protectedDotNet123!';
        $databaseName = 'protecteddotnet';
        $this->testInstance = new DatabaseConnection($host, $username, $password, $databaseName);
        $this->testInstance->setFieldTypes([
            'id' => 'INT',
            'firstName' => 'VARCHAR',
            'lastName' => 'VARCHAR',
            'userName' => 'VARCHAR',
            'dateCreated' => 'INT',
            'darkMode' => 'TINYINT'
        ]);
        $this->assertFalse((bool) $this->testInstance->conn->connect_errno);
    }

    /** @test */
    public function test_create_user() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $requestMethod = 'POST';
        $requestData = [
            'firstName' => 'Lisa Marie',
            'lastName' => 'Simpson',
            'userName' => 'L.Simpson',
            'darkMode' => 0,
            'dateCreated' => time()
        ];
        require_once('createUser.php');
        $insert_id = $this->testInstance->conn->insert_id;
        $this->assertTrue(check_data_equality($this, $insert_id, 'users', $requestData));
    }

    /** @test */
    public function test_update_user() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $id = 2;
        $requestMethod = 'PUT';
        $requestData = [
            'id' => $id,
            'firstName' => 'Barney',
            'lastName' => 'Gumble',
            'userName' => 'barney_g',
            'darkMode' => 1,
            'dateCreated' => time()
        ];
        require_once('updateUser.php');
        $this->assertTrue(check_data_equality($this, $id, 'users', $requestData));
    }
}