<?php
/*******************************************************************
usr_reg.php
  ユーザ登録メンテナンス
*******************************************************************/
// 必要なPHPファイルをインクルード
require_once("setup.php");

// データベースに接続
$db = Connection::connect();

// ランダムな6桁の数字のパスワードを生成
function simplepasswd() {
    $passwd = "";
    $numbers = range(0, 9);
    for ($i = 0; $i < 6; $i++) {
        mt_srand((double)microtime() * 1000000);
        $passwd .= $numbers[mt_rand(0, 9)];
    }
    return $passwd;
}

// ランダムな1文字を生成
function random_char() {
    $p = rand(0, 99);
    if ($p < 20) {
        return chr(rand(48, 57)); // 数値 0-9
    } elseif ($p < 60) {
        return chr(rand(65, 90)); // 大文字 A-Z
    } else {
        return chr(rand(97, 122)); // 小文字 a-z
    }
}

// ランダムなパスワード文字列を生成
function random_str($minSize, $maxSize, $no) {
    srand(time() / $no);
    $result = "";
    $length = rand($minSize, $maxSize);
    for ($i = 0; $i < $length; $i++) {
        $char = random_char();
        if (in_array($char, ["9", "q", "0", "O", "I", "l", "1"])) {
            $i--;
            continue;
        }
        $result .= $char;
    }
    return $result;
}

// ユーザIDの形式チェック
function check_uid($db, $uid, $year) {
    if (!preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
        return "「ユーザID:".$uid."」の登録形式に誤りがあります。<br>\n";
    }
    if (!preg_match("/^".$year."-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
        return "「ユーザID:".$uid."」の登録年度に誤りがあります。<br>\n";
    }

    $tmp = explode("-", $uid);
    $pref = (int)$tmp[1];
    if ($pref < 1 || $pref > 47) {
        return "「ユーザID:".$uid."」の登録形式に誤りがあります。<br>\n";
    }

    $sql = "SELECT uid FROM usr WHERE uid='".$uid."'";
    $res = mysqli_query($db, $sql);
    if (mysqli_num_rows($res) != 0) {
        return "「ユーザID:".$uid."」は既に登録されています。<br>\n";
    }

    return "";
}

// 年度取得
function getYear($db) {
    $sql = "SELECT year FROM year";
    $res = mysqli_query($db, $sql);
    $fld = mysqli_fetch_object($res);
    return $fld->year;
}

$errorMsg = '';
$cnt = 0;
$registedList = [];
$uids = explode("\n", $_POST['uid'] ?? '');
$year = getYear($db);

foreach ($uids as $uid) {
    $uid = trim($uid);

    if ($uid == "") continue;

    $errorMsg = check_uid($db, $uid, $year);
    if ($errorMsg != "") break;

    $cnt++;
    $no = substr($uid, 14, 3);

    if ($no == "000") {
        $category_id = 1;
    } elseif ($no >= "001" AND $no <= "050") {
        $category_id = 2;
    } elseif ($no >= "051") {
        $category_id = 3;
    }

    $pass = simplepasswd();

    $sql = "INSERT INTO usr (id, uid, pass, comp, del) VALUES (".$category_id.", '".$uid."', '".$pass."', 'UNCOMPLETE', 'ENABLE')";
    $res = mysqli_query($db, $sql);

    $registedList[] = array($uid, $pass);
}

include 'templates/usr_reg_template.php';
