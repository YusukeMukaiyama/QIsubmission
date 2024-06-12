<?php
header('Content-Type: text/html; charset=UTF-8');
require_once "../admin/setup.php";

// データベース接続関数
function getDatabaseConnection() {
    $db = Connection::connect(); // データベース接続
    if (!$db) {
        throw new Exception('データベース接続に失敗しました');
    }
    return $db;
}

// データベース更新関数
function updateUserCompletionStatus($db, $uid) {
    $sql = "UPDATE usr SET comp=?, lastupdate=? WHERE uid=?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL準備に失敗しました: ' . $db->error);
    }
    $comp = Config::COMPLETE;
    $lastupdate = date('Y-m-d');
    $stmt->bind_param('sss', $comp, $lastupdate, $uid);
    if (!$stmt->execute()) {
        throw new Exception('SQL実行に失敗しました: ' . $stmt->error);
    }
    $stmt->close();
}

// ファイル読み込み関数
function readTemplateFile($filename) {
    if (!file_exists($filename)) {
        throw new Exception('ファイルが見つかりません: ' . $filename);
    }
    return file_get_contents($filename);
}

// コンテンツ生成関数
function makeFinishContent() {
    return "ご協力有難うございました。<br>ブラウザの閉じるボタンをクリックしてください。<br><br><a href='#' onClick='javascript:window.close();'>閉じる</a>";
}

try {
    $db = getDatabaseConnection();  // データベース接続
    $uid = $_REQUEST['uid'];  // ユーザID取得

    updateUserCompletionStatus($db, $uid);  // 完了フラグ更新

    $contents = readTemplateFile("template_close.html");  // テンプレートファイル読み込み
    $contents = str_replace("<!-- CONTENTS -->", makeFinishContent(), $contents);  // コンテンツ置換

    echo $contents;  // コンテンツ出力
} catch (Exception $e) {
    echo 'エラーが発生しました: ' . $e->getMessage();
}
?>
