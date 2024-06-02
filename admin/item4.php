<?php
require_once("setup.php");

$db = Connection::connect();

$errstr = '';
$adderrstr = '';

$id = $_GET['id'] ?? $_POST['id'];
$id1 = $_GET['id1'] ?? $_POST['id1'];
$id2 = $_GET['id2'] ?? $_POST['id2'];
$id3 = $_GET['id3'] ?? $_POST['id3'];

// Null合体演算子を使用してデフォルト値を設定
$qtype = $_POST['qtype'] ?? '1';  // '1'が選択式のデフォルト値
$newitem = $_POST['newitem'] ?? '';  // 空文字がデフォルト値

// フォームから値を安全に取得する関数
function safePost($key, $default = '') {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

$res = mysqli_query($db, "SELECT pub FROM public");
if (!$res || mysqli_num_rows($res) == 0) die("公開状態の取得に失敗しました");
$public = mysqli_fetch_object($res)->pub;

$res = mysqli_query($db, "SELECT MAX(id4) AS maxid, MAX(no) AS maxno FROM item4 WHERE id='$id' AND id1='$id1' AND id2='$id2' AND id3='$id3'");
$maxid = 0; $maxno = 0;
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_object($res);
    $maxid = $row->maxid ? $row->maxid : 0; // Ensure default values are set if NULL
    $maxno = $row->maxno ? $row->maxno : 0;
}


$array_id = [];
$res = mysqli_query($db, "SELECT id4 FROM item4 WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 ORDER BY no");
while ($row = mysqli_fetch_object($res)) {
    $array_id[] = $row->id4;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $_POST['newitem'] = mb_convert_kana($_POST['newitem'], 'KV'); // Convert to full-width Katakana
        if (empty($_POST['newitem'])) $adderrstr = "質問が未入力です。";
        if (!$adderrstr) {
            $maxid++;
            $maxno++;
            $sql = "INSERT INTO item4(id, id1, id2, id3, id4, qtype, question, no) VALUES ($id, $id1, $id2, $id3, $maxid, ".$_POST['qtype'].", '".$_POST['newitem']."', $maxno)";
            mysqli_query($db, $sql);
        }
    }
    if (isset($_POST['edit'])) {
        foreach ($array_id as $id4) {
            if (isset($_POST['del' . $id4])) {
                mysqli_query($db, "DELETE FROM item4 WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4");
            }
            if (empty($errstr)) {
                $question = mysqli_real_escape_string($db, $_POST['name' . $id4]);
                mysqli_query($db, "UPDATE item4 SET question='$question' WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4");
            }
        }
    }
}

if (isset($_GET['up']) || isset($_GET['dwn'])) {
    $id4 = $_GET['id4'];
    $swpno = isset($_GET['up']) ? $_GET['up'] : $_GET['dwn'];
    $swapDir = isset($_GET['up']) ? -1 : 1;
    mysqli_query($db, "UPDATE item4 SET no=$swpno WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND no=($swpno + $swapDir)");
    mysqli_query($db, "UPDATE item4 SET no=($swpno + $swapDir) WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4");
}

if (isset($_POST['add']) || isset($_POST['edit'])) {
    $newno = 1;
    $res = mysqli_query($db, "SELECT id4 FROM item4 WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 ORDER BY no");
    while ($row = mysqli_fetch_object($res)) {
        mysqli_query($db, "UPDATE item4 SET no=$newno WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$row->id4");
        $newno++;
    }
}

// データ取得関数
function fetchData($db, $id, $id1, $id2, $id3) {
    $sql = "SELECT category.category AS category, item1.name AS item1_name, item1.no AS item1_no, 
            item2.name AS item2_name, item2.no AS item2_no,
            item3.name AS item3_name, item3.no AS item3_no
            FROM category
            JOIN item1 ON category.id = item1.id
            JOIN item2 ON item1.id1 = item2.id1
            JOIN item3 ON item2.id2 = item3.id2
            WHERE category.id=$id AND item1.id1=$id1 AND item2.id2=$id2 AND item3.id3=$id3
            ORDER BY item3.no";
    $res = mysqli_query($db, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}

// 関数を使用してデータを取得
$items = fetchData($db, $id, $id1, $id2, $id3);

// 変数の初期化
$category = $items[0]['category'] ?? 'カテゴリ未定';
$item1_no = $items[0]['item1_no'] ?? '番号未定';
$item1_name = $items[0]['item1_name'] ?? '名称未定';
$item2_no = $items[0]['item2_no'] ?? '番号未定';
$item2_name = $items[0]['item2_name'] ?? '名称未定';
$item3_no = $items[0]['item3_no'] ?? '番号未定';
$item3_name = $items[0]['item3_name'] ?? '名称未定';

Connection::disconnect($db);
include("templates/item4_template.php");
?>
