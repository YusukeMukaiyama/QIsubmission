<?php
/*******************************************************************
index.php
    回答者ログイン
                                    (C)2005,University of Hyougo.
*******************************************************************/
require_once "../admin/setup.php";
require_once('/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/config.php');

$db = Connection::connect(); // 修正: データベース接続の初期化を先頭に移動

$uid = isset($_POST['uid']) ? $_POST['uid'] : '';

if (!$handle = fopen("template.html", "r")) echo "file open error\n";

$contents = "";
while (TRUE) {
    $data = fread($handle, 8192);
    if (strlen($data) == 0) {
        break;
    }
    $contents .= $data;
    unset($data);
}

$contents = str_replace("<!-- CONTENTS -->", make_login($db), $contents); // 修正: $db を make_login へ渡す
echo $contents;

function make_login($db)
{
    $id = isset($_REQUEST['uid']) ? UserClassification::GetUserType($_REQUEST['uid']) : null; // 修正: $_REQUEST['uid'] の存在チェック
    $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : ""; // typeのデフォルト値を設定
    $err_msg = ""; // err_msgの初期化
    // $_POST['uid']がセットされていればその値を、そうでなければ空の文字列を使用
    $uid_value = isset($_POST['uid']) ? $_POST['uid'] : '';

    global $prefName;

    if (isset($_REQUEST['logout'])) $_REQUEST['uid'] = ""; // 修正: $_REQUEST['logout'] の存在チェック

    // 公開チェック - mysql_* 関数を mysqli_* へ変更
    $sql = "SELECT pub FROM public";
    $res = mysqli_query($db, $sql); // 修正

    $fld = mysqli_fetch_object($res); // 修正
    $public = $fld->pub;

    $specialIDs = [
        "21-01-0001-11-000" => [
            "url" => "http://localhost/QIsystem/kango3/public_html/usr/cooperation.php?uid=21-01-0001-11-000", // 遷移先URL 1
            "password" => "194919" // 専用パスワード
        ],
        "21-02-0001-11-001" => [
            "url" => "http://localhost/QIsystem/kango3/public_html/usr/cooperation.php?uid=21-02-0001-11-001", // 遷移先URL 2
            "password" => "419162" // 専用パスワード
        ],
        "21-03-0001-11-052" => [
            "url" => "http://localhost/QIsystem/kango3/public_html/usr/agreement.php?uid=21-03-0001-11-052",   // 遷移先URL 3
            "password" => "088449" // 専用パスワード
        ]
    ];
    

    if (isset($_POST['login']) && $_POST['login']) {
        $uid = $_POST['uid'];
        $password = $_POST['pass'];

        if (array_key_exists($uid, $specialIDs) && $password === $specialIDs[$uid]['password']) {
            // マスターIDとマスターパスワードの組み合わせで直接ログイン処理
            header("Location: " . $specialIDs[$uid]['url']);
            exit();
        } elseif (!preg_match("/^([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $_POST['uid'])) {
            $err_msg = "ユーザIDの形式に誤りがあります。";
        } else {
            // 都道府県チェック
            $tmp = explode("-", $_POST['uid']);
            $pref = (int)$tmp[1];
            if (!$prefName[$pref]) {
                $err_msg = "ユーザIDの形式に誤りがあります。";
            } else {
                // ID、パスワードチェック
                $sql = "SELECT pass,comp,del,lastupdate FROM usr WHERE uid = '" . $_POST['uid'] . "'";
                $res = mysqli_query($db, $sql);
                // ユーザIDチェック
                if (!mysqli_num_rows($res)) {
                    $err_msg = "ユーザIDが無効です。";
                } else {
                    // 回答完了、削除、パスワードチェック
                    $fld = mysqli_fetch_object($res);
                    if ($fld->comp == "COMPLETE" &&
                        $fld->lastupdate &&
                        $fld->lastupdate < date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - Config::login_arrow_date, date("Y")))
                    ) {
                        $err_msg = "回答済みです。";
                    } elseif ($fld->del == "DISABLE") {
                        $err_msg = "IDが無効となっています。";
                    } elseif ($fld->pass !== $_POST['pass']) {
                        $err_msg = "パスワードの入力に誤りがあります。";
                    } else {
                        if ($id == Config::STRUCTURE || $id == Config::PROCESS) {
                            header("Location: cooperation.php?uid=" . $_REQUEST['uid']);    // 研究への協力お願いへリダイレクト
                        } else {
                            header("Location: agreement.php?uid=" . $_REQUEST['uid']);    // ご利用規約へリダイレクト
                        }
                        exit();
                    }
                }
            }
        }
    }

        // HTMLコンテンツ生成部分 (コード1から)
        if ($public == 2) {
            $contents = "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>\n";
            if ((!isset($_REQUEST["type"])) || ($_REQUEST["type"] == "STRUCTURE")) {
                $contents .=
                    "<table width='380' border='0' align='center' cellpadding='1' cellspacing='0' bgcolor='#14A1A1'>\n" .
                    "<tr><td>\n" .
                    "   <table border='0' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF'>\n" .
                    //"	<tr><td><img src='../usr_img/login_title1.gif' alt='看護師長様用ログインページ' width='376' height='34'></td></tr>\n";
				    "	<tr><td><img src='http://localhost/QIsystem/kango3/public_html/usr_img/login_title1.gif' alt='看護師長様用ログインページ' width='376' height='34'></td></tr>\n";
    
            } elseif ($_REQUEST["type"] == "PROCESS") {
                $contents .=
                    "<table width='380' border='0' align='center' cellpadding='1' cellspacing='0' bgcolor='#FF66CC'>\n" .
                    "<tr><td>\n" .
                    "   <table border='0' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF'>\n" .
                    //"	<tr><td><img src='../usr_img/login_title2.gif' alt='看護師様用ログインページ' width='376' height='34'></td></tr>\n";
				    "	<tr><td><img src='http://localhost/QIsystem/kango3/public_html/usr_img/login_title2.gif' alt='看護師様用ログインページ' width='376' height='34'></td></tr>\n";
            } elseif ($_REQUEST["type"] == "OUTCOME") {
                $contents .=
                    "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "' target='_blank'>\n" .
                    "<table width='380' border='0' align='center' cellpadding='1' cellspacing='0' bgcolor='#6938FE'>\n" .
                    "<tr><td>\n" .
                    "   <table border='0' cellpadding='1' cellspacing='0' bgcolor='#FFFFFF'>\n" .
                    //"	<tr><td><img src='../usr_img/login_title3.gif' alt='患者様・ご家族の方 ログインページ' width='376' height='34'></td></tr>\n";
				    "	<tr><td><img src='http://localhost/QIsystem/kango3/public_html/usr_img/login_title3.gif' alt='患者様・ご家族の方 ログインページ' width='376' height='34'></td></tr>\n";
            }
            $contents .=
				"	<tr><td style='padding:10px;'>\n".
				"		<table border='0' align='center' cellpadding='0' cellspacing='5'>\n".
				//"		<tr><td align='right'><img src='../usr_img/login_id.gif' alt='ID' width='33' height='13'></td>".
				"		<tr><td align='right'><img src='http://localhost/QIsystem/kango3/public_html/usr_img/login_id.gif' alt='ID' width='33' height='13'></td>".
				// $_POST['uid']がセットされていればその値を、そうでなければ空の文字列を使用
				//$uid_value = isset($_POST['uid']) ? $_POST['uid'] : '';
				// 修正された$uid_valueをフォームのvalue属性に使用
				//"<td align='left'><input size='25' type='text' maxlength='17' name='uid' id='uid' value='".htmlspecialchars($uid_value, ENT_QUOTES)."'></td></tr>\n".

				//"			<td align='left'><input size='25' type='text' maxlength='17' name='uid' id='uid' value='".$_POST['uid']."'></td></tr>\n".
                "<td align='left'><input size='25' type='text' maxlength='17' name='uid' id='uid' value='".(isset($_POST['uid']) ? htmlspecialchars($_POST['uid'], ENT_QUOTES) : '')."'></td></tr>\n".

                
					// 修正された$uid_valueをフォームのvalue属性に使用
				//"<td align='left'><input size='25' type='text' maxlength='17' name='uid' id='uid' value='".htmlspecialchars($uid_value, ENT_QUOTES)."'></td></tr>\n";

				//"		<tr><td align='right'><img src='../usr_img/login_pw.gif' alt='PASSWORD' width='112' height='13'></td><td align='left'><input size='25' type='password' name='pass' id='pass'></td></tr>\n".
				"		<tr><td align='right'><img src='http://localhost/QIsystem/kango3/public_html/usr_img/login_pw.gif' alt='PASSWORD' width='112' height='13'></td><td align='left'><input size='25' type='password' name='pass' id='pass'></td></tr>\n".
				"		<tr><td colspan='2' align='right'><input type='hidden' name='login' value='1'><input type='image' src='http://localhost/QIsystem/kango3/public_html/usr_img/btn_login.gif' alt='ログイン' width='85' height='20'></td></tr>\n".
				"		</table>\n".
				"	</td></tr>\n".
				"	</table>\n".
				"</td></tr>\n".
				"</table>\n".
				"<table width='380' border='0' align='center' cellpadding='5' cellspacing='0'>\n".
				"<tr><td><font color='red'>".$err_msg."</font></td></tr>\n".
				"</table>\n".
				"<input type='hidden' name='type' value='".$type."'>\n";
				"</form>\n";
        } else {
            $contents = "<table border='0' align='center' cellpadding='5' cellspacing='0'>\n" .
                "<tr><td><font color='red'>現在メンテナンス中です。</font></td></tr>\n" .
                "</table>\n";
        }
    
        return $contents;
    }
    
    ?>
    
