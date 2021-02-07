<?php
require_once('databaseConnection.php');
$createTableQuery = 'CREATE TABLE users (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, firstName VARCHAR(50) NOT NULL, lastName VARCHAR(50) NOT NULL, userName VARCHAR(20) NOT NULL, dateCreated INT(11) NOT NULL, darkMode BOOL NOT NULL)';
$result = $dbConn->conn->query('SHOW TABLES LIKE \'users\'');
if ($result && $result->num_rows) {
    echo json_encode(['error_message' => 'The users table already exists']);
} else {
    $dbConn->conn->query($createTableQuery);
}
require_once('createUser.php');
// get the prefilled user data from and
// populate the users table with these rows
$usersToCreate = json_decode(file_get_contents('prefilled.json'));
foreach ($usersToCreate as $userData) {
    createUser((array) $userData, $dbConn);
}
?>