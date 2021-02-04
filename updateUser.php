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
$permittedFields = ['firstName', 'lastName', 'userName', 'darkMode'];
$permittedFieldCount = count($permittedFields);
$dataToUpdate = [];
for ($i = 0; $i < $permittedFieldCount; $i++) {
    $fieldName = $permittedFields[$i];
    if (
        isset($put[$fieldName]) &&
        preg_replace('/\s+/', '', $put[$fieldName]) != ''
    ) {
        $dataToUpdate[$fieldName] = $put[$fieldName];
    }
}
if (!count($dataToUpdate)) {
    die(json_encode(['error_message' => 'No valid data was supplied']));
}
$retval = $dbConn->update('users', $dataToUpdate, ['id' => $put['id']]);
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