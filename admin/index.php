<?php
/*******************************************************************
index.php
  管理者メニュー
*******************************************************************/

require_once("setup.php");// 必要なPHPファイルをインクルード

$db = Connection::connect();// データベースに接続

function fetchStatus($db) {
  $sql = "SELECT pub FROM public";
  $stmt = mysqli_prepare($db, $sql);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  if ($result) {
      $row = mysqli_fetch_assoc($result);
      return ($row['pub'] == 1) ? "公開中" : "非公開"; // OPEN 定数は 1 と定義されている(/config/Config.php)
  }
  return "データ取得失敗";
}

$db = Connection::connect();
$status = fetchStatus($db);
Connection::disconnect($db);

?>


<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin.css" media="all"> 
<title>管理者メニュー</title>
<body>
  <div align='center'>
    <h1>QIシステム</h1>
      <table cellspacing='1' cellpadding='5'>
      <tr><th>メニュー</th></tr>
      <tr><td><a href='item1.php'>質問・回答編集</a></td></tr>
      <tr><td><a href='outcome_qa_order.php'>アウトカム質問順序設定</a></td></tr>
      <tr><td><a href='enquete.php'>質問前後のアンケート設定</a></td></tr>
      <tr><td><a href='usr.php'>ユーザ一覧</a></td></tr>
      <tr><td><a href='usr_reg.php'>ユーザ登録</a></td></tr>
      <tr><td><a href='download.php'>PDF・CSVダウンロード</a></td></tr>
      <tr><td><a href='outcome_csv_import.php'>アウトカムCSVデータインポート</a></td></tr>
      <tr><td><a href='avg_csv_import.php'>平均点CSVデータインポート</a></td></tr>
      <tr><td><a href='public.php'>公開設定</a></td></tr>
      <tr><td><a href='year.php'>年度変更</a></td></tr>
      <tr><th>ステータス：<?= $status ?></th></tr>
      </table>
  </div>
</body>
</html>