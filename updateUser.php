<?php
require_once('databaseConnection.php');
if (!isset($_POST)) {
    die(json_encode(['error_message' => 'No data supplied;']));
}
if (!isset($_POST['id'])) {
    die(json_encode(['error_message' => 'A user id was not supplied;']));
}
$permittedFields = ['firstName', 'lastName', 'userName', 'darkMode'];
$permittedFieldCount = count($permittedFields);
$dataToUpdate = [];
for ($i = 0; $i < $permittedFieldCount; $i++) {
    $fieldName = $permittedFields[$i];
    if (
        isset($_POST[$fieldName]) &&
        preg_replace('/\s+/', '', $_POST[$fieldName]) != ''
    ) {
        $dataToUpdate[$fieldName] = $_POST[$fieldName];
    }
}
if (!count($dataToUpdate)) {
    die(json_encode(['error_message' => 'No valid data was supplied']));
}
$retval = $dbConn->update('users', $dataToUpdate, ['id' => $_POST['id']]);
if ($retval) {
    echo json_encode([
        'success_message' => 'The user record was updated successfully'
    ]);
} else {
    echo json_encode([
        'error_message' => mysqli_error($dbConn->conn)
    ]);
}
?>