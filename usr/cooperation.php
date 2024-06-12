<?php
/*-------------------------------------------------------------------------------------------------
    構造・過程専用
    研究への協力のお願い

    template_cooperation.html をテンプレートとして読み込んで、<!-- CONTENTS -->部分を
    システムに必要なテキストを割り当てて表示します。
-------------------------------------------------------------------------------------------------*/

require_once "../admin/setup.php";

function checkLogin() {
    if (!isset($_REQUEST['uid'])) {
        header("Location: ../index.html");
        exit();
    }
}

function updateCooperationStatus($db, $uid, $val) {
    global $id;
    $sql = "UPDATE usr SET cooperation=? WHERE id=? AND uid=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('sis', $val, $id, $uid);
    $stmt->execute();
}

function redirectToPage($page) {
    header("Location: $page?uid=" . $_REQUEST['uid']);
    exit();
}

function make_cooperation() {
    global $id;

    if ($id != Config::STRUCTURE && $id != Config::PROCESS) {
        redirectToPage('disagree.php');
    }

    if (isset($_POST['yes']) || isset($_POST['no'])) {
        $db = Connection::connect();
        $uid = $_REQUEST['uid'];
        if ($_POST['yes']) {
            $val = "同意する";
            updateCooperationStatus($db, $uid, $val);

            if ($id == Config::STRUCTURE) {
                redirectToPage('q_a.php');
            } elseif ($id == Config::PROCESS) {
                redirectToPage('kakunin.php');
            }
        } else if ($_POST['no']) {
            redirectToPage('index.php');
        }
    }

    $html = "<table>\n"
          . "<tr><td style='padding-left:40px'>\n"
          . "<form method='POST' action='cooperation.php'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'><input type='hidden' name='yes' value='同意する'><input type='image' src='../usr_img/btn_agree.gif' alt='同意する' style='padding-right:5px;'></form>\n"
          . "</td><td style='padding-left:40px'>\n"
          . "<form method='POST' action='cooperation.php'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'><input type='hidden' name='no' value='同意しない'><input type='image' src='../usr_img/btn_disagree.gif' alt='同意しない'></form>\n"
          . "</td></tr>\n"
          . "</table>\n";

    return $html;
}

function loadTemplate($filename) {
    $hFile = fopen($filename, "r") or die("ERROR FILE:".__FILE__." LINE:".__LINE__);
    $contents = "";
    while ($data = fread($hFile, 8192)) {
        $contents .= $data;
    }
    fclose($hFile);
    return $contents;
}

checkLogin();

$id = UserClassification::GetUserType($_REQUEST['uid']);
$filename = "./template_cooperation.html";
$contents = loadTemplate($filename);
$contents = str_replace("<!-- CONTENTS -->", make_cooperation(), $contents);
echo $contents;
?>
