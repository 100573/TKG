<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new Database();
    $conn = $db->getOracleConnection();

    $targets = [
        ['owner' => 'VISTA', 'name' => 'AMD_PARTS_V'],
        ['owner' => 'VISTA', 'name' => 'AMD_PARTS'],
        ['owner' => 'VISTA', 'name' => 'T_F_ORDER'],
        ['owner' => 'RO_VISTA', 'name' => 'AMD_PARTS_V'],
        ['owner' => 'RO_VISTA', 'name' => 'T_F_ORDER']
    ];

    $result = [];
    foreach ($targets as $t) {
        $sql = "SELECT COLUMN_NAME, DATA_TYPE FROM ALL_TAB_COLUMNS WHERE OWNER = :owner AND TABLE_NAME = :name ORDER BY COLUMN_ID";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':owner', $t['owner']);
        oci_bind_by_name($stmt, ':name', $t['name']);
        $ok = @oci_execute($stmt);
        $cols = [];
        if ($ok) {
            while ($row = oci_fetch_assoc($stmt)) {
                $cols[] = $row;
            }
        }
        oci_free_statement($stmt);
        $result[] = ['owner' => $t['owner'], 'name' => $t['name'], 'columns' => $cols];
    }

    oci_close($conn);
    echo json_encode(['success' => true, 'list' => $result], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
