<?php
/*******************************************************************
item2.php
    中項目メンテナンス
*******************************************************************/

require_once("setup.php");

// データベース接続
$db = Connection::connect();

// エラーメッセージ用変数の初期化
$errstr = '';
$adderrstr = '';

// IDの取得（null合体演算子を使用）
$id = $_GET['id'] ?? $_POST['id'];
$id1 = $_GET['id1'] ?? $_POST['id1'];

// 公開/非公開ステータスの取得
$res = mysqli_query($db, "SELECT pub FROM public");
$public = $res && $res->num_rows ? mysqli_fetch_object($res)->pub : die("データの取得に失敗しました。");

//$public = 2;

// 最大IDとNoの取得
$sql = "SELECT MAX(id2) AS maxid, MAX(no) AS maxno FROM item2 WHERE id=$id AND id1=$id1";
$res = mysqli_query($db, $sql);
$maxid = 0;
$maxno = 0;
if ($res && $res->num_rows) {
    $fld = mysqli_fetch_object($res);
    $maxid = $fld->maxid;
    $maxno = $fld->maxno;
}

// 全てのIDを配列に格納
$array_id = [];
$res = mysqli_query($db, "SELECT id2 FROM item2 WHERE id=$id AND id1=$id1 ORDER BY no");
while ($fld = mysqli_fetch_object($res)) {
    $array_id[] = $fld->id2;
}

// フォームからの入力を処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 新規追加の処理
    if (isset($_POST['add'])) {
        // 全角に変換し、入力チェックを行う
        $_POST['newitem'] = half2full($_POST['newitem']);  
        if (!$_POST['newitem']) $adderrstr = "項目が未入力です。";
        if (!is_numeric($_POST['newpoint']) || $_POST['newpoint'] <= 0) $adderrstr = "配点は0より大きな値をご入力ください。";
        if (!is_numeric($_POST['recommendation']) || $_POST['recommendation'] <= 0) $errstr = "基準点は0より大きな値をご入力ください。";
        if (!is_numeric($_POST['up_recommendation']) || $_POST['up_recommendation'] <= 0) $errstr = "基準点は0より大きな値をご入力ください。";
        
        // エラーがなければデータベースに新規追加
        if (empty($adderrstr) && empty($errstr)) {
            $maxid++;
            $maxno++;
            $sql = "INSERT INTO item2 (id, id1, id2, name, point, no, recommendation, up_recommendation) VALUES ($id, $id1, $maxid, '".$_POST['newitem']."', ".$_POST['newpoint'].", $maxno, ".$_POST['recommendation'].", ".$_POST['up_recommendation'].")";
            mysqli_query($db, $sql);
        }
    }

    // 編集および削除の処理
    if (isset($_POST['edit'])) {
        foreach ($array_id as $id2) {
            if (isset($_POST['del' . $id2])) {
                $sql = "DELETE FROM item2 WHERE id=$id AND id1=$id1 AND id2=$id2";
                mysqli_query($db, $sql);
            }
            if (empty($errstr)) {
                $sql = "UPDATE item2 SET name='".$_POST['name'.$id2]."', point=".$_POST['point'.$id2].", recommendation=".$_POST['recommendation'.$id2].", up_recommendation=".$_POST['up_recommendation'.$id2]." WHERE id=$id AND id1=$id1 AND id2=$id2";
                mysqli_query($db, $sql);
            }
        }
    }

    // 番号整合性の維持
    if (isset($_POST['add']) || isset($_POST['edit'])) {
        $sql = "SELECT id2 FROM item2 WHERE id=$id AND id1=$id1 ORDER BY no";
        $res = mysqli_query($db, $sql);
        $newno = 1;
        while ($fld = mysqli_fetch_object($res)) {
            $sql = "UPDATE item2 SET no=$newno WHERE id=$id AND id1=$id1 AND id2=$fld->id2";
            mysqli_query($db, $sql);
            $newno++;
        }
    }
}

// データ取得関数
function fetchItems($db, $id, $id1) {
    $sql = "SELECT category.category AS category, item1.name AS item1_name, item2.id2 AS id2, 
            item2.name AS item2_name, item2.point, item1.no AS item1_no, item2.no AS item2_no,
            item2.up_recommendation, item2.recommendation
            FROM category
            JOIN item1 ON category.id = item1.id
            JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
            WHERE category.id=$id AND item1.id1=$id1
            ORDER BY item2.no";
    $res = mysqli_query($db, $sql);
    return mysqli_fetch_all($res, MYSQLI_ASSOC);
}


// 関数を使用してデータを取得
$items = fetchItems($db, $id, $id1);

// 変数の初期化
$category = $items[0]['category'] ?? 'カテゴリ未定';
$item1_no = $items[0]['item1_no'] ?? '番号未定';
$item1_name = $items[0]['item1_name'] ?? '名称未定';

include("templates/item2_template.php");
?>
