<?php

	require_once "../admin/setup.php";
	require_once('/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/config.php');

	$db = Connection::connect();	// データベース接続

	$uid = $_REQUEST['uid'];	// ユーザID取得


	if (!$handle = fopen ("template_close.html", "r")) echo "file open error\n";

	$contents = "";

	while(TRUE) {
		$data = fread($handle, 8192);
		if (strlen($data) == 0) {
			break;
		}
		$contents .= $data;
		unset($data);
	}

	$contents =  str_replace("<!-- CONTENTS -->",make_logout($db), $contents);

	echo $contents;


function make_logout($db) {
	$contents = "ログアウト処理が完了しました。<br>ブラウザの閉じるボタンをクリックしてください。<br><br><a href='#' onClick='javascript:window.close();'>閉じる</a>";
	return $contents;
}

?>
