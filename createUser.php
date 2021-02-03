<?php
require_once('databaseConnection.php');
if (!isset($_POST)) {
    die(json_encode(['error_message' => 'No data supplied']));
}
$supplied = [
    'firstName' => isset($_POST['firstName']),
    'lastName' => isset($_POST['lastName']),
    'userName' => isset($_POST['userName']),
    'darkMode' => isset($_POST['darkMode'])
];
$absentFields = [];
foreach ($supplied as $fieldName => $wasSupplied) {
    if (!$wasSupplied) {
        $absentFields[] = $fieldName;
    }
}
if (count($absentFields)) {
    die(json_encode([
        'error_message' => 'The following fields were not supplied: ' . implode(', ', $absentFields)
    ]));
}
$values = [
    'firstName' => $_POST['firstName'],
    'lastName' => $_POST['lastName'],
    'userName' => $_POST['userName'],
    'darkMode' => $_POST['darkMode']
];
$blankFields = [];
foreach ($values as $fieldName => $fieldValue) {
    $fieldName = $dbConn->conn->real_escape_string($fieldName);
    $fieldType = $dbConn->getFieldType($fieldName);
    if ($fieldType == 'VARCHAR') {
        if (preg_replace('/\s+/', '', $fieldValue) == '') {
            $blankFields[] = $fieldName;
        }
    }
}
if (count($blankFields)) {
    die(json_encode([
        'error_message' => 'The following fields were left blank: ' . implode(', ', $blankFields)
    ]));
}
$retval = $dbConn->insert('users', [
    'firstName' => $_POST['firstName'],
    'lastName' => $_POST['lastName'],
    'userName' => $_POST['userName'],
    'dateCreated' => time(),
    'darkMode' => $_POST['darkMode']
]);
if ($retval) {
    echo json_encode([
        'success_message' => 'The new user record was created successfully'
    ]);
} else {
    echo json_encode([
        'error_message' => mysqli_error($dbConn->conn)
    ]);
}
?>