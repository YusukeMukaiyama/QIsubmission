<?php

/*******************************************************************
chart.php
	PDF出力
									(C)2005,University of Hyougo.
*******************************************************************/


require_once('../lib/fpdf/japanese.php');
require_once("../lib/chart_lib_f.php");
require_once('auth.php');

if (isset($_REQUEST['uid']) && !empty($_REQUEST['uid'])) {
    $uid = $_REQUEST['uid'];
    $pdf = new PDF();
    $pdf->ViewChart($uid);
} else {
    // uidが無効または存在しない場合の処理
    echo "無効なUIDです。";
}


/*昔のコード
	$uid = $_REQUEST['uid'];	// ユーザID

	$pdf = new PDF();
	$pdf->ViewChart($uid);

	ViewChart($uid);////////////////////////////////////////////元々あったのを削除しました・
*/


?>
