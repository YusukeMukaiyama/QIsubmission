<?php

/*******************************************************************
chart.php
	PDF出力
									(C)2005,University of Hyougo.
*******************************************************************/
/*

require_once('../lib/fpdf/japanese.php');
require_once("../lib/chart_lib.php");
require_once('auth.php');
require_once('/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/config.php');

if (isset($_REQUEST['uid']) && !empty($_REQUEST['uid'])) {
    $uid = $_REQUEST['uid'];
    $pdf = new PDF();
    $pdf->ViewChart($uid);
} else {
    // uidが無効または存在しない場合の処理
    echo "無効なUIDです。";
}

*/
/*昔のコード
	$uid = $_REQUEST['uid'];	// ユーザID

	$pdf = new PDF();
	$pdf->ViewChart($uid);

	ViewChart($uid);////////////////////////////////////////////元々あったのを削除しました・
*/

/*******************************************************************
chart.php
    PDF出力
                                    (C)2005,University of Hyougo.
*******************************************************************/

// tFPDFライブラリを読み込む。パスは環境に合わせて調整してください。
require_once("../lib/chart_lib.php");
//require_once("../lib/chart_lib_copy.php");
require_once('auth.php');
require_once('/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/config.php');

if (isset($_REQUEST['uid']) && !empty($_REQUEST['uid'])) {
    $uid = $_REQUEST['uid'];

    // tFPDFオブジェクトを作成
    $pdf = new PDF();

    // PDFの設定
    //$pdf->AddPage();
    // 日本語フォントを追加。フォントファイルへのパスは適宜調整してください。
    $pdf->AddFont('HiraMaruPro', '', 'HiraMaruProN-W4-AlphaNum-02.ttf', true);
    // フォントを設定
    $pdf->SetFont('HiraMaruPro', '', 12);

    // ここでPDFにテキストを追加するコードを書くか、または
    // ViewChartメソッドがある場合はそのメソッド内でフォントとテキストの設定を行う
    // 例: $pdf->Write(8, 'ここに日本語テキスト');
    
    // ViewChartメソッドを修正して、PDF生成の処理を行う
    $pdf->ViewChart($uid); // ViewChartメソッド内部でtFPDFに合わせた処理を行う必要があります

} else {
    // uidが無効または存在しない場合の処理
    echo "無効なUIDです。";
}

?>
