<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new Database();
    $conn = $db->getOracleConnection();

    $names = ['VISTA_T_F_ORDER', 'VISTA_AMD_PARTS_V'];
    $placeholders = implode(',', array_map(function ($n) {
        return "'" . strtoupper($n) . "'";
    }, $names));

    $sql = "SELECT OWNER, OBJECT_NAME, OBJECT_TYPE FROM ALL_OBJECTS WHERE OBJECT_NAME IN ($placeholders)";

    $stmt = oci_parse($conn, $sql);
    if (!$stmt) throw new Exception(json_encode(oci_error($conn)));
    oci_execute($stmt);
    $found = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $found[] = $row;
    }
    oci_free_statement($stmt);
    oci_close($conn);

    echo json_encode(['success' => true, 'checked' => $names, 'found' => $found], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
