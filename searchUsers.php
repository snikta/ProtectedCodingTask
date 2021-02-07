<?php
require_once('databaseConnection.php');
function searchUsers($requestData, &$dbConn) {
    $output = '';
    $searchResultCount = 0;
    if (!isset($requestData, $requestData['searchField'])) {
        $output = json_encode(['error_message' => 'No search field was supplied']);
    } else if (!isset($requestData, $requestData['query'])) {
        $output = json_encode(['error_message' => 'No search query was provided']);
    } else {
        $searchField = $dbConn->conn->real_escape_string($requestData['searchField']);
        if ($searchField != 'firstName' && $searchField != 'lastName' && $searchField != 'userName') {
            // firstName, lastName, and userName
            // are the only valid search fields
            $output = json_encode(['error_message' => 'No valid search field was provided']);
        } else {
            $query = $dbConn->conn->real_escape_string($requestData['query']);
            // return rows of whom $query is a substring of $searchField
            $users = $dbConn->query('SELECT * FROM users WHERE ' . $searchField . ' LIKE \'%' . $query . '%\'');
            $results = [];
            if ($users && $users->num_rows) {
                $searchResultCount = $users->num_rows;
                while ($user = $users->fetch_object()) {
                    $results[] = $user;
                }
                $output = json_encode($results);
            }
        }
    }
    return [$output, $searchResultCount];
}
?>