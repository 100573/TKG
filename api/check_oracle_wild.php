<?php
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new Database();
    $conn = $db->getOracleConnection();

    $patterns = [
        "%ORDER%",
        "%AMD%",
        "%PARTS%",
        "%VISTA%",
    ];

    $found = [];

    foreach ($patterns as $p) {
        $sql = "SELECT OWNER, OBJECT_NAME, OBJECT_TYPE FROM ALL_OBJECTS WHERE OBJECT_NAME LIKE :pat";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':pat', $p);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $found[] = array_map('strval', $row);
        }
        oci_free_statement($stmt);

        // synonyms
        $sql2 = "SELECT OWNER, SYNONYM_NAME, TABLE_OWNER, TABLE_NAME FROM ALL_SYNONYMS WHERE SYNONYM_NAME LIKE :pat";
        $stmt2 = oci_parse($conn, $sql2);
        oci_bind_by_name($stmt2, ':pat', $p);
        oci_execute($stmt2);
        while ($row = oci_fetch_assoc($stmt2)) {
            $found[] = array_map('strval', $row);
        }
        oci_free_statement($stmt2);
    }

    oci_close($conn);
    echo json_encode(['success' => true, 'patterns' => $patterns, 'found' => $found], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
