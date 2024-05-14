<?php
require_once __DIR__ . '/path/to/Connection.php';

try {
    $db = Connection::connect();
    echo "データベース接続成功！";
    Connection::disconnect($db);
} catch (Exception $e) {
    echo "データベース接続失敗: " . $e->getMessage();
}
?>
