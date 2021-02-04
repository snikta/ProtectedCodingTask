<?php
require_once('databaseConnection.php');
$put = [];
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') { 
    $myEntireBody = file_get_contents('php://input'); //Be aware that the stream can only be read once
    parse_str($myEntireBody, $put);
}
if (!isset($_REQUEST, $_REQUEST['id'])) {
    die(json_encode(['error_message' => 'No user id was supplied']));
}
if (isset($put['confirm']) && $put['confirm'] == 'yes') {
    $retval = $dbConn->query('DELETE FROM users WHERE id = ' . intval($_REQUEST['id']));
    if ($retval) {
        die(json_encode(['success_message' => 'The user was deleted successfully']));
    } else {
        die(json_encode(['error_message' => mysqli_error($dbConn->conn)]));
    }
} else {
    die(json_encode(['error_message' => 'There was no confirmation provided for the deletion request']));
}
?>