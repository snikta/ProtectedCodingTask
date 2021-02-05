<?php
require_once('databaseConnection.php');
if (!isset($requestData)) {
    die(json_encode(['error_message' => 'No data supplied;']));
}
if (!isset($requestData['id'])) {
    die(json_encode(['error_message' => 'A user id was not supplied;']));
}
$userId = intval($requestData['id']);
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