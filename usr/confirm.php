<?php
/*******************************************************************
confirm.php
	回答完了確認
									(C)2005,University of Hyougo.
*******************************************************************/
	require_once "../admin/setup.php";
	require_once('/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/config.php');

	// 正規ログイン以外はログイン画面へリダイレクト
	if (!$_REQUEST['uid'])  {
		header("Location: index.php");
		exit();
	}

	// 回答完了
	if (isset($_POST['comp'])) {
		$db = Connection::connect();	// データベース接続
		$uid = $_REQUEST['uid'];	// ユーザID

		// 完了フラグ更新
		$sql = "UPDATE usr SET comp='".Config::COMPLETE."',lastupdate='".date( 'Y-m-d' )."' WHERE uid='".$uid."'";
		$res = mysqli_query ( $db , $sql  );
		header("Location: chart.php?uid=".$_REQUEST['uid']);
	}

?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'
'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=EUC'>
<title>看護ケアの質評価・改善システム</title>
<link href='../style.css' rel='stylesheet' type='text/css'>
</head>
<body>
<div align='center'>
<table width="760" border="0" cellpadding="0" cellspacing="0" background="../usr_img/sub_main_bg.gif">
<tr><td><img src="../usr_img/sub_head.gif" width="760" height="30" border="0" alt=""></td></tr>
<tr><td><img src="../usr_img/sub_title.jpg" width="760" height="40" border="0" alt=""></td></tr>
<tr><td><img src="../usr_img/spacer.gif" width="760" height="10" border="0" alt=""></td></tr>
<tr><td background="../usr_img/sub_band.jpg">
	<table width="100%"  border="0" cellspacing="0" cellpadding="0"><tr>
	<td width="1"><img src="../img/spacer.gif" width="10" height="20"></td><td class='large'><font color='#FF6600'>≫</font>ＷＥＢ自己評価課程評価</td></tr>
	</table>
	</td></tr>
<tr><td valign='top' style="padding:5px; height:420px;">
	<table width='100%' class='normal'>
	<tr><td class='normal'><div align='right'><a href='index.php'>ログアウト</a></div></td></tr>
	</table>

	<form method='POST' action='<?= $_SERVER['PHP_SELF'] ?>'>
	<table width='100%' class='normal'>
	<tr><td class='normal' valign='top'>
		回答を完了します。<br><br>
		完了後は回答の編集はできません。<br><br>
		完了する場合：完了ボタンをクリックし、回答結果をご覧下さい。<br>
		なお、回答結果は一度しかご覧頂けません。<br>
		※回答結果の表示にはAdobe Acrobat Readerが必要です。
	</td></tr>
	<tr><td><input type='submit' name='comp' value='回答を完了する'></td></tr>
	</table>
	<input type='hidden' name='uid' value='<?= $_REQUEST['uid'] ?>'>
	</form>

</td></tr>
<tr><td><img src="../usr_img/sub_copyright.jpg" width="760" height="20" border="0" alt=""></td></tr>
<tr><td><img src="../usr_img/sub_foot.gif" width="760" height="25" border="0" alt=""></td></tr>
</table>

</div>

</body>
</html>
