<?php
require_once "../admin/setup.php";

// グローバル変数の定義
global $prefName;
$prefName = Config::$prefName;


try {
    $db = Connection::connect();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$templatePath = "template.html";
if (!file_exists($templatePath)) {
    die("Template file not found.");
}

$contents = file_get_contents($templatePath);
$contents = str_replace("<!-- CONTENTS -->", make_login($db), $contents);
echo $contents;

function make_login($db)
{
    global $prefName;

    $type = $_REQUEST['type'] ?? "";
    $uid_value = $_POST['uid'] ?? '';
    $err_msg = "";

    if (isset($_REQUEST['logout'])) {
        $_REQUEST['uid'] = "";
    }

    if (isset($_POST['login']) && $_POST['login']) {
        $err_msg = validate_user($db, $_POST['uid'], $_POST['pass']);
        if (!$err_msg) {
            $redirect_url = get_redirect_url($type);
            header("Location: $redirect_url?uid=" . urlencode($_POST['uid']));
            exit();
        }
    }

    $public = get_public_status($db);
    if ($public == 2) {
        return generate_login_form($type, $uid_value, $err_msg);
    } else {
        return "<table border='0' align='center' cellpadding='5' cellspacing='0'>\n" .
               "<tr><td><font color='red'>現在メンテナンス中です。</font></td></tr>\n" .
               "</table>\n";
    }
}

function validate_user($db, $uid, $password)
{
    global $prefName;

    $specialIDs = [
        "21-01-0001-11-000" => [
            "url" => "../usr/cooperation.php?uid=21-01-0001-11-000",
            "password" => "194919"
        ],
        "21-02-0001-11-001" => [
            "url" => "../usr/cooperation.php?uid=21-02-0001-11-001",
            "password" => "419162"
        ],
        "21-03-0001-11-052" => [
            "url" => "../usr/agreement.php?uid=21-03-0001-11-052",
            "password" => "088449"
        ]
    ];

    if (array_key_exists($uid, $specialIDs) && $password === $specialIDs[$uid]['password']) {
        header("Location: " . $specialIDs[$uid]['url']);
        exit();
    } elseif (!preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
        return "ユーザIDの形式に誤りがあります。";
    }

    $tmp = explode("-", $uid);
    $pref = ltrim($tmp[1], '0'); // 先頭のゼロを削除

    // 都道府県コードが有効かどうかをチェック
    if ($pref < 1 || $pref > 47 || !isset($prefName[$pref])) {
        return "ユーザIDの形式に誤りがあります。";
    }

    $stmt = mysqli_prepare($db, "SELECT pass, comp, del, lastupdate FROM usr WHERE uid = ?");
    mysqli_stmt_bind_param($stmt, "s", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) == 0) {
        return "ユーザIDが無効です。";
    }

    $fld = mysqli_fetch_object($res);
    $current_date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - Config::login_arrow_date, date("Y")));
    if ($fld->comp == "COMPLETE" && $fld->lastupdate && $fld->lastupdate < $current_date) {
        return "回答済みです。";
    } elseif ($fld->del == "DISABLE") {
        return "IDが無効となっています。";
    } elseif ($fld->pass !== $password) {
        return "パスワードの入力に誤りがあります。";
    }

    return "";
}

function get_redirect_url($type)
{
    if ($type == "STRUCTURE" || $type == "PROCESS") {
        return "cooperation.php";
    } else {
        return "agreement.php";
    }
}

function get_public_status($db)
{
    $sql = "SELECT pub FROM public";
    $res = mysqli_query($db, $sql);
    if (!$res) {
        die("Database query failed.");
    }
    $fld = mysqli_fetch_object($res);
    return $fld->pub;
}

function generate_login_form($type, $uid_value, $err_msg)
{
    $bgColor = [
        "STRUCTURE" => "#14A1A1",
        "PROCESS" => "#FF66CC",
        "OUTCOME" => "#6938FE"
    ];

    $imageSrc = [
        "STRUCTURE" => "../usr_img/login_title1.gif",
        "PROCESS" => "../usr_img/login_title2.gif",
        "OUTCOME" => "../usr_img/login_title3.gif"
    ];

    $bg = $bgColor[$type] ?? "#14A1A1";
    $imgSrc = $imageSrc[$type] ?? $imageSrc["STRUCTURE"];

    $form = "<form method='POST' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) . "'>\n";
    $form .= "<table width='380' border='0' align='center' cellpadding='1' cellspacing='0' bgcolor='$bg'>\n" .
             "<tr><td>\n" .
             "   <table border='0' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF'>\n" .
             "   <tr><td><img src='$imgSrc' alt='ログインページ' width='376' height='34'></td></tr>\n" .
             "   <tr><td style='padding:10px;'>\n" .
             "       <table border=\"0\" align=\"center\" cellpadding=\"0\" cellspacing=\"5\" style='background-color: #FFFFFF;'>\n".
             "       <tr><td align='right'><img src='../usr_img/login_id.gif' alt='ID' width='33' height='13'></td>\n" .
             "       <td align='left'><input size='25' type='text' maxlength='17' name='uid' id='uid' value='" . htmlspecialchars($uid_value, ENT_QUOTES) . "'></td></tr>\n" .
             "       <tr><td align='right'><img src='../usr_img/login_pw.gif' alt='PASSWORD' width='112' height='13'></td><td align='left'><input size='25' type='password' name='pass' id='pass'></td></tr>\n" .
             "       <tr><td colspan='2' align='right'><input type='hidden' name='login' value='1'><input type='image' src='../usr_img/btn_login.gif' alt='ログイン' width='85' height='20'></td></tr>\n" .
             "       </table>\n" .
             "   </td></tr>\n" .
             "   </table>\n" .
             "</td></tr>\n" .
             "</table>\n" .
             "<table width='380' border='0' align='center' cellpadding='5' cellspacing='0'>\n" .
             "<tr><td><font color='red'>$err_msg</font></td></tr>\n" .
             "</table>\n" .
             "<input type='hidden' name='type' value='" . htmlspecialchars($type, ENT_QUOTES) . "'>\n" .
             "</form>\n";

    return $form;
}
?>
