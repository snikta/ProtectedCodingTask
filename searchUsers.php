<?php
require_once('databaseConnection.php');
if (!isset($_GET, $_GET['searchField'])) {
    echo json_encode(['error_message' => 'No search field was supplied']);
} else if (!isset($_GET, $_GET['query'])) {
    echo json_encode(['error_message' => 'No search query was provided']);
} else {
    $searchField = $dbConn->conn->real_escape_string($_GET['searchField']);
    if ($searchField != 'firstName' && $searchField != 'lastName' && $searchField != 'userName') {
        echo json_encode(['error_message' => 'No valid search field was provided']);
    } else {
        $query = $dbConn->conn->real_escape_string($_GET['query']);
        $users = $dbConn->query('SELECT * FROM users WHERE ' . $searchField . ' LIKE \'%' . $query . '%\'');
        $results = [];
        $resultCount = $users->num_rows;
        if ($resultCount) {
            while ($user = $users->fetch_object()) {
                $results[] = $user;
            }
            echo json_encode($results);
        }
    }
}
?>