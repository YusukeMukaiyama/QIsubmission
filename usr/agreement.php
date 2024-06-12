<?php
/*******************************************************************
agreement.php
    ご利用規約
    (C)2005,University of Hyougo.
    管理者用のログイン後の画面
    ユーザーIDが必要
*******************************************************************/

require_once "../admin/setup.php";

// 正規ログインチェック
if (!isset($_REQUEST['uid'])) {
    header("Location: ../index.html");
    exit();
}

$db = Connection::connect();

function make_agreement($db, $uid, $userType)
{
    if (isset($_POST['agree'])) {
        redirect_by_user_type($uid, $userType);
    } elseif (isset($_POST['disagree'])) {
        header("Location: disagree.php?uid=" . htmlspecialchars($uid));
        exit();
    }

    return generate_agreement_form($uid);
}

function redirect_by_user_type($uid, $userType)
{
    switch ($userType) {
        case Config::STRUCTURE:
            header("Location: cooperation.php?uid=" . htmlspecialchars($uid));
            break;
        case Config::PROCESS:
            header("Location: cooperation.php?uid=" . htmlspecialchars($uid));
            break;
        case Config::OUTCOME:
            header("Location: enq.php?uid=" . htmlspecialchars($uid));
            break;
        default:
            throw new Exception("Invalid user type");
    }
    exit();
}

function generate_agreement_form($uid)
{
    return "<form method='POST' action='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>
                <input type='hidden' name='agree' value='同意する'>
                <input type='image' src='../usr_img/btn_agree.gif' class='button-image' alt='同意する' style='padding-right:5px;'>
                <input type='hidden' name='uid' value='" . htmlspecialchars($uid) . "'>
            </form>
            </td><td width='120'>
            <form method='POST' action='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>
                <input type='hidden' name='disagree' value='同意しない'>
                <input type='image' src='../usr_img/btn_disagree.gif' class='button-image' alt='同意しない'>
                <input type='hidden' name='uid' value='" . htmlspecialchars($uid) . "'>
            </form>";
}

$uid = htmlspecialchars($_REQUEST['uid']);
$userType = UserClassification::GetUserType($uid);

switch ($userType) {
    case Config::STRUCTURE:
        $templateFile = "template_kiyaku.html";
        break;
    case Config::PROCESS:
        $templateFile = "template_kiyaku_process.html";
        break;
    case Config::OUTCOME:
        $templateFile = "template_kiyaku_outcome.html";
        break;
    default:
        throw new Exception("Invalid user type");
}

$contents = file_get_contents($templateFile);
if ($contents === false) {
    die("file open error\n");
}

$agreementForm = make_agreement($db, $uid, $userType);
$contents = str_replace("<!-- CONTENTS -->", $agreementForm, $contents);

echo $contents;
?>
