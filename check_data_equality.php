<?php
function check_data_equality(&$that, $row_id, $tableName, $oldData) {
    $sanitisedTableName = $that->testInstance->conn->real_escape_string($tableName);
    $sanitisedOldData = $that->testInstance->sanitiseData($oldData, true);
    $query = $that->testInstance->query('SELECT * FROM ' . $sanitisedTableName . ' WHERE id = ' . $row_id);
    if ($query && $query->num_rows) {
        $query = $query->fetch_object();
        $sanitisedNewData = $that->testInstance->sanitiseData($query, true);
        foreach ($sanitisedNewData as $fieldName => $fieldValue) {
            if (array_key_exists($fieldName, $sanitisedOldData)) {
                $cmp = $fieldValue == $sanitisedOldData[$fieldName];
                $cmp_strict = $fieldValue === $sanitisedOldData[$fieldName];
                $that->assertEquals($sanitisedOldData[$fieldName], $fieldValue);
                $that->assertTrue($cmp_strict);
                if (!$cmp_strict) {
                    return false;
                }
            }
        }
    }
    return true;
}
?>