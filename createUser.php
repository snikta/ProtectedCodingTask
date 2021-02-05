<?php
require_once('databaseConnection.php');
if (!isset($requestData)) {
    die(json_encode(['error_message' => 'No data supplied']));
}
$supplied = [
    'firstName' => isset($requestData['firstName']),
    'lastName' => isset($requestData['lastName']),
    'userName' => isset($requestData['userName']),
    'darkMode' => isset($requestData['darkMode'])
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
    'firstName' => $requestData['firstName'],
    'lastName' => $requestData['lastName'],
    'userName' => $requestData['userName'],
    'darkMode' => $requestData['darkMode']
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
$firstNameLength = strlen($requestData['firstName']);
$lastNameLength = strlen($requestData['lastName']);
$userNameLength = strlen($requestData['userName']);
$errors = [];
if (!($firstNameLength >= 1 && $firstNameLength <= 50)) {
    $errors[] = 'First name length was out of range (length must be greater or equal to 1 and less than or equal 50 characters)';
}
if (!($lastNameLength >= 1 && $lastNameLength <= 50)) {
    $errors[] = 'Last name length was out of range (length must be greater or equal to 1 and less than or equal to 50 characters)';
}
if (!($userNameLength >= 1 && $userNameLength <= 50)) {
    $errors[] = 'Username length was out of range (length must be greater than or equal to 6 and less than or equal to 50 characters)';
}
if (count($errors)) {
    die(json_encode([
        'error_message' => 'There were errors creating the user account',
        'list_of_errors' => $errors
    ]));
}
$retval = $dbConn->insert('users', [
    'firstName' => $requestData['firstName'],
    'lastName' => $requestData['lastName'],
    'userName' => $requestData['userName'],
    'dateCreated' => time(),
    'darkMode' => $requestData['darkMode']
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