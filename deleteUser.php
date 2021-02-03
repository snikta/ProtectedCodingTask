<?php
require_once('databaseConnection.php');
if (!isset($_POST, $_POST['id'])) {
    die(json_encode(['error_message' => 'No user id was supplied']));
}
if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
    $retval = $dbConn->query('DELETE FROM users WHERE id = ' . intval($_POST['id']));
    if ($retval) {
        die(json_encode(['success_message' => 'The user was deleted successfully']));
    } else {
        die(json_encode(['error_message' => mysqli_error($dbConn->conn)]));
    }
} else {
    die(json_encode(['error_message' => 'There was no confirmation provided for the deletion request']));
}
?>