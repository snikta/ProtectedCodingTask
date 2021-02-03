<?php
require_once('databaseConnection.php');
$createTableQuery = 'CREATE TABLE Users (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, firstName VARCHAR(50) NOT NULL, lastName VARCHAR(50) NOT NULL, userName VARCHAR(20) NOT NULL, dateCreated INT(11) NOT NULL, darkMode BOOL NOT NULL)';
?>