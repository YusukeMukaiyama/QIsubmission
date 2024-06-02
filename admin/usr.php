<?php
// 必要なPHPファイルをインクルード
require_once("setup.php");

// データベースに接続
$db = Connection::connect();

// ユーザーの有効/無効更新を処理する
if (isset($_POST['update'])) {
    for ($i = 0; isset($_POST['uid' . $i]); $i++) {
        $uid = $_POST['uid' . $i];
        $sql = "UPDATE usr SET del='" . $_POST['enable' . $i] . "', comp='" . $_POST['comp' . $i] . "' WHERE uid='" . $uid . "'";
        $res = mysqli_query($db, $sql);

        // 削除が選択された場合、ユーザーを削除name='year'
        if (isset($_POST['ureg' . $i])) {
            $sql = "DELETE FROM usr WHERE uid='" . $uid . "'";
            $res = mysqli_query($db, $sql);
        }
    }
}

// 検索方法を設定
$enumtype = isset($_POST['enumtype']) ? $_POST['enumtype'] : 1;

// 検索条件に基づくユーザー情報を取得
function getUsers($db, $post) {
    $sql = "SELECT uid, pass, comp, del FROM usr WHERE uid LIKE '%" . ($post['UserID'] ?? '') . "%'";
    $res = mysqli_query($db, $sql);
    $users = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
    return $users;
}

// 年度データを取得
function getYears($db) {
    $sql = "SELECT DISTINCT LEFT(uid, 2) AS year FROM usr ORDER BY year DESC";
    $res = mysqli_query($db, $sql);
    $years = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $years[] = $row['year'];
    }
    return $years;
}

// 都道府県データを取得
function getPrefs($db, $post) {
    $sql = "SELECT DISTINCT SUBSTRING(uid, 4, 2) AS pref FROM usr WHERE uid LIKE '" . ($post['year'] ?? '') . "-%' ORDER BY pref ASC";
    $res = mysqli_query($db, $sql);
    $prefs = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $prefs[] = $row['pref'];
    }
    return $prefs;
}

// 病院データを取得
function getHospitals($db, $post) {
    $sql = "SELECT DISTINCT SUBSTRING(uid, 7, 4) AS hospital FROM usr WHERE uid LIKE '" . ($post['year'] ?? '') . "-" . ($post['pref'] ?? '') . "-%' ORDER BY hospital ASC";
    $res = mysqli_query($db, $sql);
    $hospitals = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $hospitals[] = $row['hospital'];
    }
    return $hospitals;
}

// 病棟データを取得
function getWards($db, $post) {
    $sql = "SELECT DISTINCT SUBSTRING(uid, 12, 2) AS ward FROM usr WHERE uid LIKE '" . ($post['year'] ?? '') . "-" . ($post['pref'] ?? '') . "-" . ($post['hosp'] ?? '') . "-%' ORDER BY ward ASC";
    $res = mysqli_query($db, $sql);
    $wards = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $wards[] = $row['ward'];
    }
    return $wards;
}

// 検索ボタンが押されたときにのみユーザー情報を取得
$users = [];
if (isset($_POST['search'])) {
    $users = getUsers($db, $_POST);
}

// 必要なデータ（年度、都道府県、病院、病棟）を取得
$years = getYears($db);
$prefs = getPrefs($db, $_POST);
$hospitals = getHospitals($db, $_POST);
$wards = getWards($db, $_POST);

// データベースから切断する関数は不要なため、特別な処理は行わない

// HTMLテンプレートファイルをインクルードし、データを渡す
include 'templates/usr_template.php';

