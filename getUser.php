<?php
require_once('databaseConnection.php');
if (!isset($_GET, $_GET['id'])) {
    echo json_encode(['error_message' => 'No user id was supplied']);
} else {
    $userId = intval($_GET['id']);
    $user = $dbConn->query('SELECT * FROM users WHERE id = ' . $userId);
    if ($user && ($user = $user->fetch_object())) {
        echo json_encode($user);
    } else {
        echo json_encode(['error_message' => 'Could not find user in database']);
    }
}
?>