<?php
require_once "setup.php";

// データベース接続
$db = Connection::connect();

// 現在の公開/非公開のステータスを取得するSQLクエリ
$sql = "SELECT pub FROM public";  // 仮のテーブル名とカラム名を使用
$res = $db->query($sql);

$status = "データ取得失敗";  // 初期状態

if ($res && $row = $res->fetch_object()) {
    $status = ($row->pub == Config::OPEN) ? "公開中" : "非公開";// ステータスの確認と設定
} else {
    $status = "データ取得失敗";
}

// データベース切断
Connection::disconnect($db);

// HTMLテンプレートファイルを読み込み
require 'templates/main_template.php';
?>