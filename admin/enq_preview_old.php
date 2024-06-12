<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin.css" media="all">
<title>アンケートプレビュー</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

<?php

function view_enquete($id,$id1)
{
	global $db;

    $ans = "";//デフォルト値を設定

	$sql = "SELECT * FROM enquete WHERE id='".$id."' AND id1='".$id1."'";
	$res = mysqli_query ( $db , $sql  );
	$fld = mysqli_fetch_object ( $res );
	$enq = $fld->enq;	$unit = $fld->unit;	$type = $fld->type;

	echo "<form name='enquete'>\n";

	echo "<table cellspacing='1' cellpadding='5'>\n".
		"<tr><td>\n";

	//---------------------------------------------------------------------------------------------
	// HTMLコメントがあれば削除
	$buf = array ( "<!--" ,"-->" );
	$enq = str_replace ( $buf, "" ,$enq );
	// javascriptを抽出
	preg_match ( "|<script[^>]*>(.+)</script>|Usi", $enq, $script );
	if ( sizeof ( $script ) ) {
		echo $script[0];	// javascript部分は改行しない
		// javascript以外の文字列を全て抽出
		$html = preg_split ( "|<script[^>]*>(.+)</script>|Usi", $enq );
		if ( is_array ( $html ) ) {
			for ( $i = 0;$i < sizeof ( $html );$i++ ) {
				echo nl2br ( trim($html[$i]) );
			}
		}
		$script_flg = TRUE;
	} else {	// javascriptなし
		// 改行を<br>に変換して出力
		echo nl2br ( $enq );
		$script_flg = FALSE;
	}
	return $script_flg;
	//---------------------------------------------------------------------------------------------

	echo "</td></tr>\n";

	if ($type == 2) {
		if ( $script_flg ) {
			echo "<input type='hidden' name='ans' value='".$ans."'>\n";	// 計算式がある場合は表示しない
		} else {
			echo "<tr><td><input type='text' name='ans' value='".$ans."'>　".$unit."</td></tr>\n";
		}
	} elseif ($type == 5) {
		echo "<tr><td><textarea name='ans' cols='50' rows='5'>".nl2br($ans)."</textarea></td></tr>\n";
	} else {
		echo "<tr><td>\n<table cellspacing='1' cellpadding='5'>\n";
		$sql = "SELECT * FROM enq_ans WHERE id='".$id."' AND id1='".$id1."'";
		$res = mysqli_query ( $db , $sql  );
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			echo "<tr><td><input type='".(($type== 3) ? "checkbox" : "radio").
				"' name='ans".(($type== 3) ? $fld->id2 : "")."' value='".$fld->id2."'>".nl2br($fld->ans)."</td></tr>\n";
		}
		echo "</table>\n</td></tr>\n";
	}

	echo "</table>\n";

	echo "</form>\n";

}

	require_once("setup.php");

	$db = Connection::connect();	// データベース接続

	$script_flg = view_enquete($_REQUEST['id'],$_REQUEST['id1']);



	if ( $script_flg ) {
		echo "<p><span style='font-size:9pt;color:#bbb;'>※参照中のアンケートには計算式が含まれています。<br>\n".
			"　ボタン等の押下時動作はごの画面では正常動作しません。</span></p>";
	}

?>

	<p><a href='#' onclick='window.close();'>[ 閉じる ]</a></p>

</div>

</body>
</html>
