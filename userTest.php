<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require('databaseConnection.php');
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
    public function test_list_users() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $expectedUsers = json_decode(file_get_contents('modified.json'));
        require_once('listUsers.php');
        $decodedOutput = json_decode($output);
        $i = 0;
        foreach($decodedOutput as $decodedUser) {
            $decodedUser = (array) $decodedUser;
            $expectedUser = (array) $expectedUsers[$i];
            foreach($decodedUser as $key => $value) {
                if (array_key_exists($key, $expectedUser)) {
                    $this->assertTrue($value === $expectedUser[$key]);
                }
            }
            $i++;
        }
    }

    /** @test */
    public function test_search_by_lastname() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        require_once('searchUsers.php');

        $requestData = [
            'searchField' => 'lastName',
            'query' => 'Simpson'
        ];
        list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
        $this->assertTrue($searchResultCount > 0);

        $requestData = [
            'searchField' => 'lastName',
            'query' => 'Duke'
        ];
        list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
        $this->assertFalse($searchResultCount > 0);
    }

    /** @test */
    public function test_search_by_firstname() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        require_once('searchUsers.php');

        $requestData = [
            'searchField' => 'firstName',
            'query' => 'Buzz'
        ];
        list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
        $this->assertTrue($searchResultCount > 0);

        $requestData = [
            'searchField' => 'firstName',
            'query' => 'Harrison'
        ];
        list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
        $this->assertFalse($searchResultCount > 0);
    }

    /** @test */
    public function test_search_by_username() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        require_once('searchUsers.php');

        $requestData = [
            'searchField' => 'userName',
            'query' => 'MR.Burns'
        ];
        list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
        $this->assertTrue($searchResultCount > 0);
        
        $requestData = [
            'searchField' => 'userName',
            'query' => 'gene.cernan'
        ];
        list($output, $searchResultCount) = searchUsers($requestData, $dbConn);
        $this->assertFalse($searchResultCount > 0);
    }
    
    /** @test */
    public function test_create_user() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $requestMethod = 'POST';
        $requestData = [
            'firstName' => 'Bartholomew J',
            'lastName' => 'Simpson',
            'userName' => 'BART_simpson',
            'darkMode' => 0,
            'dateCreated' => time()
        ];
        require_once('createUser.php');
        createUser($requestData, $dbConn);
        $insert_id = $this->testInstance->conn->insert_id;
        $this->assertTrue(check_data_equality($this, $insert_id, 'users', $requestData));
    }

    /** @test */
    public function test_update_user() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $id = 5;
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

    /** @test */
    public function test_delete_user() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $id = 2;
        $requestMethod = 'DELETE';
        $requestData = [
            'id' => $id,
            'confirm' => 'yes'
        ];
        require_once('deleteUser.php');
        $result = $dbConn->conn->query('SELECT * FROM users WHERE id = ' . intval($id));
        $this->assertFalse($result && $result->num_rows > 0);
    }

    /** @test */
    public function test_toggle_dark_mode() {
        $this->test_connect();
        $dbConn = &$this->testInstance;
        $id = 3;
        $result = $dbConn->conn->query('SELECT * FROM users WHERE id = ' . intval($id));
        $this->assertTrue($result && $result->num_rows > 0);
        $resultObj = $result->fetch_object();
        $prevValue = (bool) $resultObj->darkMode;
        $requestMethod = 'PUT';
        $requestData = [
            'id' => $id
        ];
        require_once('toggleDarkMode.php');
        $result = $dbConn->conn->query('SELECT * FROM users WHERE id = ' . intval($id));
        $this->assertTrue($result && $result->num_rows > 0);
        $resultObj = $result->fetch_object();
        $newValue = (bool) $resultObj->darkMode;
        $this->assertTrue($prevValue === !$newValue);
    }
}