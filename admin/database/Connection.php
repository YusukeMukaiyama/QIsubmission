<?php

//require_once __DIR__ . '/../admin/database/Connection.php';

class Connection {
    public static function connect() {
        $db = mysqli_connect(Config::DBHOST, Config::DBUSER, Config::DBPASS, Config::DBNAME);
        if (!$db) {
            die('データベース接続失敗: ' . mysqli_connect_error());
        }
        mysqli_set_charset($db, "utf8");
        return $db;
    }

    public static function disconnect($db) {
        mysqli_close($db);
    }
}
?>