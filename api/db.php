<?php
class Database
{
    // MySQL接続情報（picking DB）
    private $mysql_host = '10.2.13.100';
    private $mysql_user = 'ubuntu';
    private $mysql_pass = 'vaio';
    private $mysql_db = 'picking';

    // MySQL接続情報（aimond_n DB）
    private $aimond_host = '10.1.2.28';
    private $aimond_user = 'root';
    private $aimond_pass = 'aimond';
    private $aimond_db = 'aimond_n';
    private $aimond_port = 3306;

    // Oracle接続情報（VISTA_ASY）
    private $oracle_host = '10.1.2.70';
    private $oracle_port = '1521';
    private $oracle_user = 'vista';
    private $oracle_pass = 'vision';
    private $oracle_service = 'vaio';

    /**
     * MySQL (picking) 接続取得
     */
    public function getMySQLConnection()
    {
     try {
            $dsn = "mysql:host={$this->mysql_host};dbname={$this->mysql_db};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->mysql_user, $this->mysql_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("MySQL接続エラー: " . $e->getMessage());
        }
    }

    /**
     * MySQL (aimond_n) 接続取得
     */
    public function getAImondConnection()
    {
        try {
            $dsn = "mysql:host={$this->aimond_host};port={$this->aimond_port};dbname={$this->aimond_db};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->aimond_user, $this->aimond_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception("AIMonD接続エラー: " . $e->getMessage());
        }
    }

    /**
     * Oracle (VISTA_ASY) 接続取得
     */
    public function getOracleConnection()
    {
        try {
            // OCI8拡張を使用
            $dsn = "//{$this->oracle_host}:{$this->oracle_port}/{$this->oracle_service}";
            $conn = oci_connect(
                $this->oracle_user,
                $this->oracle_pass,
                $dsn
            );

            if (!$conn) {
                $e = oci_error();
                throw new Exception("Oracle接続エラー: " . $e['message']);
            }

            return $conn;
        } catch (Exception $e) {
            throw new Exception("Oracle接続エラー: " . $e->getMessage());
        }
    }

    /**
     * Oracle クエリ実行（SELECT）
     */
    public function oracleQuery($conn, $sql, $params = [])
    {
        $stmt = oci_parse($conn, $sql);

        if (!$stmt) {
            $e = oci_error($conn);
            throw new Exception("SQL解析エラー: " . $e['message']);
        }

        // パラメータバインド
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $params[$key]);
        }

        // 実行
        $result = oci_execute($stmt);
        if (!$result) {
            $e = oci_error($stmt);
            throw new Exception("SQL実行エラー: " . $e['message']);
        }

        // 結果取得
        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $row;
        }

        oci_free_statement($stmt);
        return $rows;
    }
}
