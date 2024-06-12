<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin.css" media="all">
<title>平均点CSVデータインポート</title>
</head>
<body>
<div align='center'>
    <h1>QIシステム</h1>
    <table cellspacing='1' cellpadding='5'>
    <tr><th><a href='index.php'>メニュー</a> ≫ 平均点CSVデータインポート</th></tr>
    <tr><td>
<?php
require_once 'setup.php';

/*
-- 平均点
CREATE TABLE dat_avg (
    id          INT(11)     NOT NULL,
    id1         INT(11)     NOT NULL,
    avg         FLOAT       NOT NULL,
    PRIMARY KEY (id, id1)
);
*/

/**
 * バッファの強制フラッシュ
 * 
 * @return void
 */
function buffer_flush(): void
{
    flush();
    ob_flush();
}

/**
 * データインポート
 * 
 * @param mysqli $db データベース接続オブジェクト
 * @return void
 */
function import_csv(mysqli $db): void
{
    $hFile = fopen($_FILES['userfile']['tmp_name'], 'r');
    if (!$hFile) {
        die("ERROR FILE: " . __FILE__ . " LINE: " . __LINE__);
    }

    // ヘッダラインを処理
    $data = fgetcsv($hFile, 256, ",");
    if (count($data) != 3) {
        die("データの形式が不正です。");
    }

    // テーブル初期化
    $sql = "TRUNCATE TABLE dat_avg";
    if (!$db->query($sql)) {
        die("ERROR FILE: " . __FILE__ . " LINE: " . __LINE__);
    }

    // データインポート
    $cnt = 0;
    while (($data = fgetcsv($hFile, 256, ",")) !== FALSE) {
        $stmt = $db->prepare("INSERT INTO dat_avg (id, id1, avg) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iid", $data[0], $data[1], $data[2]);
            $stmt->execute();
            $stmt->close();
        } else {
            die("ERROR FILE: " . __FILE__ . " LINE: " . __LINE__);
        }

        echo htmlspecialchars($data[0] . "-" . $data[1]) . " データをインポートしました。<br>\n";
        buffer_flush();
        $cnt++;
    }

    fclose($hFile);

    // 読み込み件数表示
    echo htmlspecialchars($cnt . "件のデータを読み込みました。");
}

$db = Connection::connect(); // データベース接続

if (isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    echo "処理が完了するまで、そのままお待ちください。<br>";
    echo "※）ブラウザのリロードを行うとデータが重複登録されますので決して行わないでください。<br>";
    echo htmlspecialchars($_FILES['userfile']['name']) . "がアップロードされました。<br>";
    buffer_flush();

    echo "インポート処理を行っています。<br>";
    buffer_flush();

    import_csv($db);
} else {
?>
    インポートできるデータの形式は以下の通りです（1行目のタイトル行も必要です）。<br>
    <p style='margin : 2px;padding : 5px;background : #dddddd;'>
    ID(構造:1/過程:2/アウトカム:3),ID1(大項目NO.),AVG(平均点)<br>
    ex)<br>
    ID,ID1,AVG<br>
    1,1,6.02<br>
    1,2,8.27<br>
    1,3,8.23<br>
    1,4,18.73<br>
    1,5,17.08<br>
    1,6,13.02<br>
    2,1,6.02<br>
    2,2,8.27<br>
    :</br>
    </p>
    <form method='POST' action='./avg_csv_import.php' enctype='multipart/form-data'>
        <input type='hidden' name='MAX_FILE_SIZE' value='10485760'>
        <input type='file' name='userfile'>　<input type='submit' value='アップロード'>
    </form>
<?php
}
?>
    </td></tr>
    </table>
</div>
</body>
</html>
