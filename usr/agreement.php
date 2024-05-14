<?php
/*******************************************************************
agreement.php
	ご利用規約
									(C)2005,University of Hyougo.
管理者用のログイン後の画面
アクセスの際には
http://localhost/QIsystem/kango3/public_html/usr/agreement.php?uid=18-45-0001-10-089
のようにuid=の後にユーザーIDが必要
*******************************************************************/

require_once "../admin/setup.php";

// 正規ログインチェック
/*if (!$_REQUEST['uid']){
	header("Location: ../index.html");
	exit();
}*/
if (!isset($_REQUEST['uid'])) {
    header("Location: ../index.html");
    exit();
}

$db = Connection::connect();

function make_agreement($db)
{
	global $id;

	// 利用規約同意
	//if ($_POST['agree']) {
	if (isset($_POST['agree'])) {

		if ( $id == Config::STRUCTURE ) {	// 構造

			header("Location: cooperation.php?uid=".$_REQUEST['uid']);	// 質問へ
//			header("Location: q_a.php?uid=".$_REQUEST['uid']);	// 質問へ

		} elseif ( $id == Config::PROCESS ) {	// 過程

			header("Location: cooperation.php?uid=".$_REQUEST['uid']);	// さらに質問ページ
//			header("Location: kakunin.php?uid=".$_REQUEST['uid']);	// さらに質問ページ

		} elseif ( $id == Config::OUTCOME ) {	// アウトカム

			header("Location: enq.php?uid=".$_REQUEST['uid']);	// アンケートへ

		}
		exit();

	//} elseif ($_POST['disagree']) {	// ログアウトHTMLへリダイレクト
	} elseif (isset($_POST['disagree'])) {// ログアウトHTMLへリダイレクト

		header("Location: disagree.php?uid=".$_REQUEST['uid']);
		exit();

	}

	$contents = "<form method='POST' action='".$_SERVER['PHP_SELF']."'>\n".
		"<input type='hidden' name='agree' value='同意する'><input type='image' src='../usr_img/btn_agree.gif' alt='同意する' style='padding-right:5px;'>\n".
		"<input type='hidden' name='uid' value='".$_REQUEST['uid']."'>\n".
		"</form>\n".
		"</td><td width='120'>\n".
		"<form method='POST' action='".$_SERVER['PHP_SELF']."'>\n".
		"<input type='hidden' name='disagree' value='同意しない'><input type='image' src='../usr_img/btn_disagree.gif' alt='同意しない'>\n".
		"<input type='hidden' name='uid' value='".$_REQUEST['uid']."'>\n".
		"</form>\n";

	return $contents;

}

	$id = UserClassification::GetUserType($_REQUEST['uid']);

	if ($id == Config::STRUCTURE) {
		$filename = "template_kiyaku.html";
	} elseif ($id == Config::PROCESS) {
		$filename = "template_kiyaku_process.html";
	} elseif ($id == Config::OUTCOME) {
		$filename = "template_kiyaku_outcome.html";
	}

	$handle = fopen ($filename , "r") or die ("file open error\n");
	$contents = "";
	while(TRUE) {
		$data = fread($handle, 8192);
		if (strlen($data) == 0) {
			break;
		}
		$contents .= $data;
		unset($data);
	}
	$contents =  str_replace("<!-- CONTENTS -->",make_agreement($db), $contents);
	echo $contents;

?>
