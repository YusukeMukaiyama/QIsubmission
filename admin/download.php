<?php
// 必要なPHPファイルをインクルード
require_once("setup.php");

// データベースに接続
$db = Connection::connect();

/**
 * getPrefectures - 指定された年度に使用されている都道府県 <option> の生成
 * @param string $year 選択されている年度
 * @param string $pref 選択されている都道府県
 */
function getPrefectures($year, $pref)
{
    global $prefName;
    $sql = "SELECT DISTINCT SUBSTRING(uid FROM 4 FOR 2) AS pid FROM usr WHERE uid LIKE '".$year."-%' AND comp='1' ORDER BY pid ASC";
    $db = Connection::connect();
    $res = mysqli_query($db, $sql);
    $prefs = [];
    while ($row = mysqli_fetch_object($res)) {
        $prefs[] = $row->pid;
    }
    echo "<option value=''>--選択してください--</option>\n";
    foreach ($prefs as $pid) {
        $sel = ($pref == (int)$pid) ? "selected" : "";
        echo sprintf("\t<option value='%02d' %s>%s</option>\n", $pid, $sel, $prefName[(int)$pid]);
    }
}

/**
 * getHospital - 指定された都道府県の病院 <option> の生成
 * @param string $year 選択されている年度
 * @param string $pref 選択されている都道府県
 * @param string $hosp 選択されている病院
 */
function getHospital($year, $pref, $hosp)
{
    echo "<option value=''>--選択してください--</option>\n";
    if (!$year || !$pref) return;
    $sql = sprintf("SELECT DISTINCT SUBSTRING(uid FROM 7 FOR 4) AS id FROM usr WHERE uid LIKE '%s-%s%%' AND comp='1' ORDER BY 1 ASC", $year, $pref);
    $db = Connection::connect();
    $res = mysqli_query($db, $sql);
    while ($row = mysqli_fetch_object($res)) {
        $sel = ($hosp == $row->id) ? "selected" : "";
        echo sprintf("\t<option value='%s' %s>%s</option>\n", $row->id, $sel, $row->id);
    }
}

/**
 * getWard - 指定された病院の病棟 <option> の生成
 * @param string $year 選択されている年度
 * @param string $pref 選択されている都道府県
 * @param string $hosp 選択されている病院
 * @param string $ward 選択されている病棟
 */
function getWard($year, $pref, $hosp, $ward)
{
    echo "<option value=''>--選択してください--\n";
    if (!$year || !$pref || !$hosp) return;
    $sql = sprintf("SELECT DISTINCT SUBSTRING(uid FROM 12 FOR 2) AS id FROM usr WHERE uid LIKE '%s-%s-%s%%' AND comp='1' ORDER BY 1 ASC", $year, $pref, $hosp);
    $db = Connection::connect();
    $res = mysqli_query($db, $sql);
    while ($row = mysqli_fetch_object($res)) {
        $sel = ($ward == $row->id) ? "selected" : "";
        echo sprintf("\t<option value='%s' %s>%s</option>\n", $row->id, $sel, $row->id);
    }
}

/**
 * check_uid - uid の形式が正しいかチェックする
 * @param string $uid ユーザID文字列
 * @return string|null 正常終了: $uid, エラー: NULL
 */
function check_uid($uid)
{
    if (!preg_match("([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})", $uid)) {
        return NULL;
    }
    if (strlen($uid) != 17) return NULL;
    $tmp = explode("-", $uid);
    if (count($tmp) != 5 || strlen($tmp[0]) != 2 || strlen($tmp[1]) != 2 || strlen($tmp[2]) != 4 || strlen($tmp[3]) != 2 || strlen($tmp[4]) != 3) {
        return NULL;
    }
    $pref = (int)$tmp[1];
    return ($pref >= 1 && $pref <= 47) ? $uid : NULL;
}

/**
 * getYear - 現在の年度を取得
 * @return int 現在の年度
 */
function getYear()
{
    global $db;
    $sql = "SELECT year FROM year";
    $res = mysqli_query($db, $sql);
    $fld = mysqli_fetch_object($res);
    return $fld->year;
}

// 年度を取得
$year = getYear();

// POST データを取得
$postData = $_POST;
$pref1 = $postData['pref1'] ?? '';
$hos1 = $postData['hos1'] ?? '';
$pref2 = $postData['pref2'] ?? '';
$hos2 = $postData['hos2'] ?? '';
$ward = $postData['ward'] ?? '';
$uid = $postData['uid'] ?? '';
$errorMessage = "";

// ここでダウンロード処理やエラーメッセージの生成を行います
if (isset($postData['pdf_u']) && $uid != "") {
    if (check_uid($uid) === NULL) {
        $errorMessage = "入力したIDの形式に誤りがあります。";
    } else {
        $sql = "SELECT comp FROM usr WHERE uid='$uid'";
        $res = mysqli_query($db, $sql);
        if (!mysqli_num_rows($res)) {
            $errorMessage = "指定したユーザは存在していません。";
        } else {
            $fld = mysqli_fetch_object($res);
            if ($fld->comp != "1") {
                $errorMessage = "指定したユーザは未回答です。";
            } else {
                $PDF_ID = $uid;
                // ダウンロード処理のためのコードをここに挿入
            }
        }
    }
}

// その他のダウンロードボタンに対する処理もここに追加

// HTML のページに対するレンダリング
require_once('download_view.php');
