<?php
require_once('databaseConnection.php');
$users = $dbConn->query('SELECT * FROM users WHERE id > 0');
$results = [];
$resultCount = $users->num_rows;
if ($resultCount) {
    while ($user = $users->fetch_object()) {
        $userData = [];
        foreach ($user as $fieldName => $fieldValue) {
            $fieldType = $dbConn->getFieldType($fieldName);
            switch ($fieldType) {
                case 'VARCHAR':
                    $userData[$fieldName] = (string) $fieldValue;
                    break;
                case 'INT':
                    $userData[$fieldName] = (int) $fieldValue;
                    break;
                case 'TINYINT':
                    $userData[$fieldName] = ((bool) $fieldValue) ? true : false;
                    break;
            }
        }
        $results[] = $userData;
    }
    $output = json_encode($results);
} else {
    $output = json_encode([
        'error_message' => 'There are no users in the table'
    ]);
}
?>