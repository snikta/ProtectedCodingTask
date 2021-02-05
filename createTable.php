<?php
require_once('databaseConnection.php');
$createTableQuery = 'CREATE TABLE users (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, firstName VARCHAR(50) NOT NULL, lastName VARCHAR(50) NOT NULL, userName VARCHAR(20) NOT NULL, dateCreated INT(11) NOT NULL, darkMode BOOL NOT NULL)';
$result = $dbConn->conn->query('SHOW TABLES LIKE \'users\'');
if ($result && $result->num_rows) {
    die(json_encode(['error_message' => 'The users table already exists']));
} else {
    $dbConn->conn->query($createTableQuery);
}
require_once('createUser.php');
$usersToCreate = [
    [
        'firstName' => 'Homer J.',
        'lastName' => 'Simpson',
        'userName' => 'H.J.Simpson',
        'darkMode' => 1,
        'dateCreated' => time()
    ],
    [
        'firstName' => 'Lisa Marie',
        'lastName' => 'Simpson',
        'userName' => 'L.Simpson',
        'darkMode' => 0,
        'dateCreated' => time()
    ],
    [
        'firstName' => 'Neil Alden',
        'lastName' => 'Armstrong',
        'userName' => 'NA_armstrong',
        'darkMode' => 0,
        'dateCreated' => time()
    ],
    [
        'firstName' => 'Edwin "Buzz"',
        'lastName' => 'Aldrin',
        'userName' => 'the_real_buzz',
        'darkMode' => 1,
        'dateCreated' => time()
    ],
    [
        'firstName' => 'Margaret "Maggie"',
        'lastName' => 'Simpson',
        'userName' => 'maggie__simpson',
        'darkMode' => 1,
        'dateCreated' => time()
    ]
];
foreach ($usersToCreate as $userData) {
    createUser($userData, $dbConn);
}
?>