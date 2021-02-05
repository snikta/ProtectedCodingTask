<?php
require_once('databaseConnection.php');
if (!isset($requestData, $requestData['id'])) {
    echo json_encode(['error_message' => 'No user id was supplied']);
} else if (isset($requestData['confirm']) && $requestData['confirm'] == 'yes') {
    $retval = $dbConn->query('DELETE FROM users WHERE id = ' . intval($requestData['id']));
    if ($retval) {
        echo json_encode(['success_message' => 'The user was deleted successfully']);
    } else {
        echo json_encode(['error_message' => mysqli_error($dbConn->conn)]);
    }
} else {
    echo json_encode(['error_message' => 'There was no confirmation provided for the deletion request']);
}
?>