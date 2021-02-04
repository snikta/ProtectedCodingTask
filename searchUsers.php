<?php
require_once('databaseConnection.php');
if (!isset($_GET, $_GET['searchField'])) {
    die(json_encode(['error_message' => 'No search field was supplied']));
}
if (!isset($_GET, $_GET['query'])) {
    die(json_encode(['error_message' => 'No search query was provided']));
}
$searchField = $dbConn->conn->real_escape_string($_GET['searchField']);
if ($searchField != 'firstName' && $searchField != 'lastName' && $searchField != 'userName') {
    die(json_encode(['error_message' => 'No valid search field was provided']));
}
$query = $dbConn->conn->real_escape_string($_GET['query']);
$users = $dbConn->query('SELECT * FROM users WHERE ' . $searchField . ' LIKE \'%' . $query . '%\'');
$results = [];
$resultCount = mysqli_num_rows($users);
if ($resultCount) {
    while ($user = mysqli_fetch_object($users)) {
        $results[] = $user;
    }
    echo json_encode($results);
}
?>