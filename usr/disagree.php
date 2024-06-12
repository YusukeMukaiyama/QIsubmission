<?php

require_once __DIR__ . "/../admin/setup.php"; // 絶対パスに変更

try {
    $db = Connection::connect(); // データベース接続
} catch (Exception $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}

$uid = htmlspecialchars($_GET['uid'], ENT_QUOTES, 'UTF-8'); // ユーザID取得とサニタイズ

$templateFile = __DIR__ . "/template_disagree.html"; // 絶対パスに変更

if (!file_exists($templateFile)) {
    echo "テンプレートファイルが見つかりません: " . htmlspecialchars($templateFile);
    exit;
}

$contents = file_get_contents($templateFile); // ファイル読み込みを簡素化

$contents = str_replace("<!-- CONTENTS -->", make_logout($db), $contents);

echo $contents;

function make_logout($db) {
    // ログアウト処理を関数内にコメント付きで追加
    $contents = <<<HTML
    ログアウト処理が完了しました。<br>ブラウザの閉じるボタンをクリックしてください。<br><br>
    HTML;
    return $contents;
}
?>
