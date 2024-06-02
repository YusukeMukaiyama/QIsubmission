<?php
require_once("setup.php");

// データベース接続
$db = Connection::connect();

// エラーメッセージ用の変数を初期化
$errstr = '';
$adderrstr = '';
$imgerrstr = '';

// GETまたはPOSTからid, id1, id2を取得
$id = $_GET['id'] ?? $_POST['id'];
$id1 = $_GET['id1'] ?? $_POST['id1'];
$id2 = $_GET['id2'] ?? $_POST['id2'];



// 公開/非公開状態をデータベースから取得
$res = mysqli_query($db, "SELECT pub FROM public");
$public = $res && $res->num_rows ? mysqli_fetch_object($res)->pub : exit("公開状態の取得に失敗しました。");

// 項目情報の取得 // データ取得関数
function fetchItems($db, $id, $id1, $id2) {
    $sql = "SELECT category.category AS category, item1.name AS item1_name, item2.name AS item2_name, item3.id3, item3.name AS item3_name, item3.point, item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no
            FROM category
            JOIN item1 ON category.id = item1.id
            JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
            JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2
            WHERE category.id = $id AND item1.id1 = $id1 AND item2.id2 = $id2
            ORDER BY item3.no";
    $res = mysqli_query($db, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

// 大項目、中項目、小項目のデータを取得
$itemDetails = fetchItems($db, $id, $id1, $id2);
$category = $itemDetails[0]['category'] ?? 'カテゴリ未定';
$item1_no = $itemDetails[0]['item1_no'] ?? '番号未定';
$item1_name = $itemDetails[0]['item1_name'] ?? '名称未定';
$item2_no = $itemDetails[0]['item2_no'] ?? '番号未定';
$item2_name = $itemDetails[0]['item2_name'] ?? '名称未定';

// 小項目の一覧を取得
$subItems = [];
$sql = "SELECT id3, name, point, no FROM item3 WHERE id = $id AND id1 = $id1 AND id2 = $id2 ORDER BY no";
$result = mysqli_query($db, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $subItems[] = $row;
}

// 最大IDとnoを取得
$sql = "SELECT MAX(id3) AS maxid, MAX(no) AS maxno FROM item3 WHERE id=$id AND id1=$id1 AND id2=$id2";
$res = mysqli_query($db, $sql);
$maxid = 0; $maxno = 0;
if ($res && mysqli_num_rows($res) > 0) {
    $fld = mysqli_fetch_object($res);
    $maxid = $fld->maxid;
    $maxno = $fld->maxno;
}

// 小項目のIDを配列に格納
$array_id = [];
$sql = "SELECT id3 FROM item3 WHERE id=$id AND id1=$id1 AND id2=$id2 ORDER BY no";
$res = mysqli_query($db, $sql);
while ($fld = mysqli_fetch_object($res)) {
    $array_id[] = $fld->id3;
}

// フォームの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        // 新規追加処理
        $_POST['newitem'] = half2full($_POST['newitem']); // 全角に変換
        if (empty($_POST['newitem'])) $adderrstr = "項目が未入力です。";
        if (!is_numeric($_POST['newpoint']) || $_POST['newpoint'] <= 0) $adderrstr .= "配点は0より大きな値をご入力ください。";
        if (!$adderrstr) {
            $maxid++;
            $maxno++;
            $sql = "INSERT INTO item3(id, id1, id2, id3, name, point, no) VALUES ($id, $id1, $id2, $maxid, '".$_POST['newitem']."', ".$_POST['newpoint'].", $maxno)";
            mysqli_query($db, $sql);
        }
    }

    // 既存データの編集と削除
    if (isset($_POST['edit'])) {
        foreach ($array_id as $id3) {
            if (isset($_POST['del' . $id3])) {
                // 関連データの削除
                $sql = "DELETE FROM item3 WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3";
                mysqli_query($db, $sql);
            }
            // データ更新
            // データ更新
            if (empty($errstr)) {
                $name = mysqli_real_escape_string($db, $_POST['name' . $id3]);
                $point = mysqli_real_escape_string($db, $_POST['point' . $id3]);
                $sql = "UPDATE item3 SET name='$name', point=$point WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3";
                mysqli_query($db, $sql);
            }
        }
    }

    // 番号の整合性を保つ
    if (isset($_POST['add']) || isset($_POST['edit'])) {
        $newno = 1;
        $sql = "SELECT id3 FROM item3 WHERE id=$id AND id1=$id1 AND id2=$id2 ORDER BY no";
        $res = mysqli_query($db, $sql);
        while ($fld = mysqli_fetch_object($res)) {
            $sql = "UPDATE item3 SET no=$newno WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$fld->id3";
            mysqli_query($db, $sql);
            $newno++;
        }
    }
}

// 画像のアップロードおよび削除機能を追加
if (isset($_POST['upload_image'])) {
    foreach ($_FILES as $fileKey => $fileArray) {
        if (preg_match('/img(\d+)/', $fileKey, $matches)) {
            $id3 = $matches[1];
            if ($fileArray['error'] == UPLOAD_ERR_OK) {
                $imgPath = "path/to/images/{$id}_{$id1}_{$id2}_{$id3}.jpg";
                move_uploaded_file($fileArray['tmp_name'], $imgPath);
            }
        }
    }
}

if (isset($_POST['delete_image'])) {
    foreach ($array_id as $id3) {
        if (isset($_POST['imgdel' . $id3])) {
            $imgPath = "path/to/images/{$id}_{$id1}_{$id2}_{$id3}.jpg";
            if (file_exists($imgPath)) {
                unlink($imgPath);
            }
        }
    }
}

// HTMLテンプレートファイルを含める
include("templates/item3_template.php");

// データベース接続の切断
Connection::disconnect($db);

?>
