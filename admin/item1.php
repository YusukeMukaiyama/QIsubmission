<?php
/*******************************************************************
item1.php
    大項目メンテナンス
*******************************************************************/
require_once("setup.php");

// データベース接続
$db = Connection::connect();

$errstr = ''; // エラーメッセージ用変数を初期化

// 公開ステータスとカテゴリ詳細の取得
$public = getPublicationStatus($db);
$categories = fetchCategories($db);

list($idcnt, $itemcnt) = getCategoryItemStats($db);
processFormInput($db, $public, $errstr);

if (!$errstr) {
    updateDatabaseEntries($db, $public);
}

// データベースから公開ステータスを取得する
function getPublicationStatus($db) {
    $sql = "SELECT pub FROM public";
    $result = mysqli_query($db, $sql);
    if ($result) {
        $fld = mysqli_fetch_object($result);
        if ($fld) {
            return $fld->pub;
        } else {
            throw new Exception("No publication status found.");
        }
    } else {
        throw new Exception("Publication status fetch failed.");
    }
}


// データベースからカテゴリごとの統計情報（IDの数とアイテムの最大数）を取得する
function getCategoryItemStats($db) {
    $sql = "SELECT COUNT(category.id) AS idcnt, MAX(item) AS itemcnt FROM category";
    if ($res = mysqli_query($db, $sql)) {
        $fld = mysqli_fetch_object($res);
        return array($fld->idcnt, $fld->itemcnt);
    }
    die("Category stats fetch failed.");
}

// フォームからの入力を処理し、有効なデータのみを後続の処理で使うために検証・保存する
function processFormInput($db, $public, &$errstr) {
    $sql = "SELECT id, id1 FROM item1 ORDER BY id ASC, id1 ASC";
    $res = mysqli_query($db, $sql);
    while ($fld = mysqli_fetch_object($res)) {
        if (isset($_POST['regist' . $fld->id])) {
            validateAndSetPostData($fld->id, $fld->id1, $public, $errstr);
        }
    }
}

// 入力されたポストデータを検証し、有効でない場合はエラー文字列を設定する
function validateAndSetPostData($id, $id1, $public, &$errstr) {
    $_POST['name' . $id . $id1] = half2full($_POST['name' . $id . $id1]);
    if (!$_POST['name' . $id . $id1]) $errstr = "項目名が未入力です。";

    if (!is_numeric($_POST['recommendation' . $id . $id1])) $errstr = "低得点基準は0より大きな値をご入力ください。";
    if (!is_numeric($_POST['up_recommendation' . $id . $id1])) $errstr = "高得点基準は0より大きな値をご入力ください。";

    if ($public == 'CLOSE') {
        $_POST['point' . $id . $id1] = str2int($_POST['point' . $id . $id1]);
        if ($_POST['point' . $id . $id1] < 1) $errstr = "配点は0より大きな値をご入力ください。";
    }
}

// 有効なフォーム入力に基づいてデータベースのエントリを更新する、既存のクエリをプリペアドステートメントに変更
function updateDatabaseEntries($db, $public) {
    $sql = "SELECT id, id1 FROM item1 ORDER BY id ASC, id1 ASC";
    $result = mysqli_query($db, $sql);
    while ($fld = mysqli_fetch_object($result)) {
        if (isset($_POST['regist' . $fld->id])) {
            list($updateQuery, $params) = buildUpdateQuery($fld, $public, $db);
            $stmt = mysqli_prepare($db, $updateQuery);
            mysqli_stmt_bind_param($stmt, 'siii', ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                die("Update failed: " . mysqli_error($db));
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// 与えられたフィールド情報と公開ステータスに基づき、データベースの更新クエリを構築する
function buildUpdateQuery($fld, $public, $db) {
    $id = $fld->id;
    $id1 = $fld->id1;
    $name = mysqli_real_escape_string($db, $_POST['name' . $id . $id1]);
    $recommendation = intval($_POST['recommendation' . $id . $id1]);
    $up_recommendation = intval($_POST['up_recommendation' . $id . $id1]);

    if ($public == 'CLOSE') {
        $point = intval($_POST['point' . $id . $id1]);
        return ["UPDATE item1 SET name=?, point=?, recommendation=?, up_recommendation=? WHERE id=? AND id1=?", [$name, $point, $recommendation, $up_recommendation, $id, $id1]];
    } else {
        return ["UPDATE item1 SET name=?, recommendation=?, up_recommendation=? WHERE id=? AND id1=?", [$name, $recommendation, $up_recommendation, $id, $id1]];
    }
}

// カテゴリデータを取得する関数
function fetchCategories($db) {
    $sql = "SELECT category.item, category.id AS cat_id, category.category, item1.id1, item1.name, item1.point, item1.no, item1.recommendation, item1.up_recommendation 
            FROM item1 
            INNER JOIN category ON item1.id = category.id 
            ORDER BY category.id, item1.no";
    $result = mysqli_query($db, $sql);
    $categories = [];
    while ($row = mysqli_fetch_object($result)) {
        $categories[$row->cat_id]['category'] = $row->category;
        $categories[$row->cat_id]['items'][] = $row;
    }
    return $categories;
}


// HTMLコンテンツを別のテンプレートファイルから読み込む
include("templates/item1_template.php");

// データベース接続の切断をHTMLの読み込み後に
Connection::disconnect($db);

?>
