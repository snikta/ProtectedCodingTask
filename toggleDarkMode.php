<?php
require_once('databaseConnection.php');
if ($_SERVER['REQUEST_METHOD'] === 'PUT') { 
    $myEntireBody = file_get_contents('php://input'); //Be aware that the stream can only be read once
    parse_str($myEntireBody, $put);
}
if (!isset($put)) {
    die(json_encode(['error_message' => 'No data supplied;']));
}
if (!isset($put['id'])) {
    die(json_encode(['error_message' => 'A user id was not supplied;']));
}
$userId = intval($put['id']);
$user = $dbConn->query('SELECT * FROM users WHERE id = ' . $userId);
if ($user && ($user = $user->fetch_object())) {
    $retval = $dbConn->query('UPDATE users SET darkMode = NOT darkMode WHERE id = ' . $userId);
    if ($retval) {
        echo json_encode(['success_message' => 'The darkMode value was successfully toggled ' . ($user->darkMode == 1 ? 'OFF' : 'ON')]); // $user->darkMode is old value
    } else {
        echo json_encode(['error_message' => 'Found user but was unable to toggle darkMode']);
    }
} else {
    echo json_encode(['error_message' => 'Error fetching user from database']);
}
?>