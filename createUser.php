<?php
require_once('databaseConnection.php');
/* this function accepts input in an associative
 * array called $requestData, and a reference to
 * an active database connection called $dbConn
 */
function createUser($requestData, &$dbConn) {
    /* at each point of failure, we either output
     * an error message or continue with the next
     * step */
    $ok_to_continue = false;
    if (!isset($requestData)) {
        echo json_encode(['error_message' => 'No data supplied']);
    } else {
        /* check which fields were set */
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
            echo json_encode([
                'error_message' => 'The following fields were not supplied: ' . implode(', ', $absentFields)
            ]);
        } else {
            $ok_to_continue = true;
        }
    }
    if ($ok_to_continue) {
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
                // only consider a field blank if it is
                // of type VARCHAR and is either empty or
                // only consists of whitespace
                if (preg_replace('/\s+/', '', $fieldValue) == '') {
                    $blankFields[] = $fieldName;
                }
            }
        }
        if (count($blankFields)) {
            echo json_encode([
                'error_message' => 'The following fields were left blank: ' . implode(', ', $blankFields)
            ]);
        } else {
            // enforce length requirements for firstName,
            // lastName, and userName
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
            if (!($userNameLength >= 6 && $userNameLength <= 20)) {
                $errors[] = 'Username length was out of range (length must be greater than or equal to 6 and less than or equal to 20 characters)';
            }
            if (count($errors)) {
                echo json_encode([
                    'error_message' => 'There were errors creating the user account',
                    'list_of_errors' => $errors
                ]);
            } else {
                // use a user-supplied timestamp if one exists;
                // otherwise use the current system time (UNIX time)
                $retval = $dbConn->insert('users', [
                    'firstName' => $requestData['firstName'],
                    'lastName' => $requestData['lastName'],
                    'userName' => $requestData['userName'],
                    'dateCreated' => isset($requestData['dateCreated']) ?
                        $requestData['dateCreated'] : time(),
                    'darkMode' => $requestData['darkMode']
                ]);
                if ($retval) {
                    echo json_encode([
                        'success_message' => 'The new user record was created successfully'
                    ]);
                } else {
                    // report an error from mysqli
                    echo json_encode([
                        'error_message' => mysqli_error($dbConn->conn)
                    ]);
                }
            }
        }
    }
}
?>