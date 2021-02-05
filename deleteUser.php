<?php
require_once('databaseConnection.php');
if (!isset($requestData, $requestData['id'])) {
    die(json_encode(['error_message' => 'No user id was supplied']));
}
if (isset($requestData['confirm']) && $requestData['confirm'] == 'yes') {
    $retval = $dbConn->query('DELETE FROM users WHERE id = ' . intval($requestData['id']));
    if ($retval) {
        die(json_encode(['success_message' => 'The user was deleted successfully']));
    } else {
        die(json_encode(['error_message' => mysqli_error($dbConn->conn)]));
    }
} else {
    die(json_encode(['error_message' => 'There was no confirmation provided for the deletion request']));
}
?>