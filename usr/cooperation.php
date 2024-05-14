<?php
/*-------------------------------------------------------------------------------------------------
	構造・過程専用
	研究への協力のお願い

	template_cooperation.html をテンプレートとして読み込んで、<!-- CONTENTS -->部分を
	システムに必要なテキストを割り当てて表示します。

-------------------------------------------------------------------------------------------------*/
	require_once "../admin/setup.php";
	require_once('/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/config.php');

// 正規ログインチェック
if (!$_REQUEST['uid']){
	header("Location: ../index.html");
	exit();
}


function make_cooperation ()
{

	global $id;

	if($id != Config::STRUCTURE && $id != Config::PROCESS) {
		header("Location: disagree.php?uid=".$_REQUEST['uid']);
		exit();
	}
	/*
	if(isset($_POST['yes']) || isset($_POST['no'])) {
		$db = Connection::connect();
		$uid = $_REQUEST['uid'];
		if($_POST['yes']) {
			$val = "同意する";
		} else if($_POST['no']) {
			$val = "同意しない";
		}
		$exesql = "UPDATE usr SET cooperation='".$val."' WHERE id=".$id." AND uid='".$uid."'";
		$exeres = mysqli_query ( $db , $exesql  );

		if ( $id == STRUCTURE ) {	// 構造
			header("Location: q_a.php?uid=".$_REQUEST['uid']);	// 質問へ
		} elseif ( $id == PROCESS ) {	// 過程
			header("Location: kakunin.php?uid=".$_REQUEST['uid']);	// さらに質問ページ
		}
	}
	*/
	if(isset($_POST['yes']) || isset($_POST['no'])) {
		$db = Connection::connect();
		$uid = $_REQUEST['uid'];
		if($_POST['yes']) {
			$val = "同意する";
			$exesql = "UPDATE usr SET cooperation='".$val."' WHERE id=".$id." AND uid='".$uid."'";
			$exeres = mysqli_query($db, $exesql);
	
			if ($id == Config::STRUCTURE) {
				header("Location: q_a.php?uid=".$_REQUEST['uid']);
				exit(); // リダイレクト後に処理を停止
			} elseif ($id == Config::PROCESS) {
				header("Location: kakunin.php?uid=".$_REQUEST['uid']);
				exit(); // リダイレクト後に処理を停止
			}
		} else if($_POST['no']) {
			// 同意しない場合のデータベース更新を省略したり、必要に応じて行います
			// ここではユーザーをindex.phpにリダイレクトします
			header("Location: index.php");
			exit(); // リダイレクト後に処理を停止
		}
	}
	


	$html =
		"<table>\n".
		"<tr><td style='padding-left:40px'>\n".
		"<form method='POST' action='cooperation.php'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'><input type='hidden' name='yes' value='同意する'><input type='image' src='../usr_img/btn_agree.gif' alt='同意する' style='padding-right:5px;'></form>\n".
		"</td><td style='padding-left:40px'>\n".
		"<form method='POST' action='cooperation.php'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'><input type='hidden' name='no' value='同意しない'><input type='image' src='../usr_img/btn_disagree.gif' alt='同意しない'></form>\n".
		"</td></tr>\n".
		"</table>\n";

	return $html;

}

	$id = UserClassification::GetUserType($_REQUEST['uid']);

	$filename = "./template_cooperation.html";
	$hFile = fopen  ( $filename , "r" ) or die  ( "ERROR FILE:".__FILE__." LINE:".__LINE__ );
	$contents = "";
	while ( TRUE ) {
		$data = fread ( $hFile, 8192 );
		if  ( strlen ( $data ) == 0 ) break;
		$contents .= $data;
		unset ( $data );
	}
	$contents = str_replace ( "<!-- CONTENTS -->", make_cooperation(), $contents );
	echo $contents;


?>
