<?php
require_once('databaseConnection.php');
if (!isset($requestData, $requestData['id'])) {
    // need a primary key to know which row to delete
    echo json_encode(['error_message' => 'No user id was supplied']);
} else if (isset($requestData['confirm']) && $requestData['confirm'] == 'yes') {
    // we require confirmation before proceeding with deletion
    $retval = $dbConn->query('DELETE FROM users WHERE id = ' . intval($requestData['id']));
    if ($retval) {
        echo json_encode(['success_message' => 'The user was deleted successfully']);
    } else {
        // report an error
        echo json_encode(['error_message' => mysqli_error($dbConn->conn)]);
    }
} else {
    // confirm field was not provided, or was invalid
    echo json_encode(['error_message' => 'There was no confirmation provided for the deletion request']);
}
?>