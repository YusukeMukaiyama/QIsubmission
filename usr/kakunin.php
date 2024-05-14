<?php
/*-------------------------------------------------------------------------------------------------
	過程専用
	同意後の確認ページ

	template_kakunin.html をテンプレートとして読み込んで、<!-- CONTENTS -->部分を
	システムに必要なテキストを割り当てて表示します。

-------------------------------------------------------------------------------------------------*/
function replace_text ()
{

	$html =
		"<table>\n".
		"<tr><td>\n".
		"<form method='POST' action='index.php'><input type='submit' name='no' value='≪　いいえ　'></form>\n".
		"</td><td>\n".
		//"<form method='GET' action='q_a.php'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'><input type='submit' name='yes' value='　は　い　≫'></form>\n".
		"<form method='GET' action='q_a.php'><input type='hidden' name='uid' value='".(isset($_REQUEST['uid']) ? $_REQUEST['uid'] : "")."'><input type='submit' name='yes' value='　は　い　≫'></form>\n".

		"</td></tr>\n".
		"</table>\n";

	return $html;

}

	$filename = "./template_kakunin.html";
	$hFile = fopen  ( $filename , "r" ) or die  ( "ERROR FILE:".__FILE__." LINE:".__LINE__ );
	$contents = "";
	while ( TRUE ) {
		$data = fread ( $hFile, 8192 );
		if  ( strlen ( $data ) == 0 ) break;
		$contents .= $data;
		unset ( $data );
	}
	$contents = str_replace ( "<!-- CONTENTS -->", replace_text(), $contents );
	echo $contents;

?>
