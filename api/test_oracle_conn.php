<?php
// テストスクリプト: 複数の接続文字列で oci_connect を試行します
$user = 'vista';
$pass = 'vision';
$tests = [
    '//10.1.2.70:1521/vista_asy',
    '//10.1.2.70:1521/vaio',
    '//10.1.2.70:1521/VISTA_ASY',
    '10.1.2.70:1521/vista_asy',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=10.1.2.70)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=vista_asy)))',
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=10.1.2.70)(PORT=1521))(CONNECT_DATA=(SID=vaio)))'
];

$results = [];
foreach ($tests as $t) {
    $start = microtime(true);
    $conn = @oci_connect($user, $pass, $t);
    $elapsed = round((microtime(true) - $start) * 1000, 1);

    if ($conn) {
        $results[] = [
            'connect_string' => $t,
            'success' => true,
            'message' => 'connected',
            'elapsed_ms' => $elapsed
        ];
        oci_close($conn);
    } else {
        $e = oci_error();
        $results[] = [
            'connect_string' => $t,
            'success' => false,
            'message' => isset($e['message']) ? $e['message'] : 'unknown error',
            'code' => isset($e['code']) ? $e['code'] : null,
            'elapsed_ms' => $elapsed
        ];
    }
}

header('Content-Type: application/json');
echo json_encode(['tested' => count($tests), 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
