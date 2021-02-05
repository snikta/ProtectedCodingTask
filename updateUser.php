<?php
require_once('databaseConnection.php');
if (!isset($requestData)) {
    echo json_encode(['error_message' => 'No data supplied;']);
} else if (!isset($requestData['id'])) {
    echo json_encode(['error_message' => 'A user id was not supplied;']);
} else {
    $permittedFields = ['firstName', 'lastName', 'userName', 'darkMode', 'dateCreated'];
    $permittedFieldCount = count($permittedFields);
    $dataToUpdate = [];
    for ($i = 0; $i < $permittedFieldCount; $i++) {
        $fieldName = $permittedFields[$i];
        if (
            isset($requestData[$fieldName]) &&
            preg_replace('/\s+/', '', $requestData[$fieldName]) != ''
        ) {
            $dataToUpdate[$fieldName] = $requestData[$fieldName];
        }
    }
    if (!count($dataToUpdate)) {
        echo json_encode(['error_message' => 'No valid data was supplied']);
    } else {
        $retval = $dbConn->update('users', $dataToUpdate, ['id' => $requestData['id']]);
        if ($retval) {
            echo json_encode([
                'success_message' => 'The user record was updated successfully'
            ]);
        } else {
            echo json_encode([
                'error_message' => mysqli_error($dbConn->conn)
            ]);
        }
    }
}
?>