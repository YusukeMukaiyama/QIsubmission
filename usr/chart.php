<?php

/*******************************************************************
chart.php
    PDF出力
                                    (C)2005,University of Hyougo.
*******************************************************************/

require_once("../lib/chart_lib.php");
require_once("../lib/chart_lib_ward2.php");
require_once("../lib/chart_lib_hosp2.php");
require_once("../admin/setup.php");


function generatePDF($uid) {
    if (isValidUid($uid)) {
        // TCPDFの共通設定を行うための親クラスのインスタンスを生成
        /*$pdf = new PDF();
        $pdf->ViewChart($uid); // ViewChartメソッドを呼び出してPDF内容を生成
        $pdf->AddPage();*/

        // PDF_Wardのインスタンスを生成し、内容を追加
        /*$pdf_ward = new PDF_Ward();
        $pdf_ward->SetMargins(10, 10, 10); // マージン設定
        $pdf_ward->AddPage();
        $pdf_ward->ViewChart($uid);
        $pdf_ward->AddPage();
        $pdf_ward->writeHTML($pdf_ward->Output('', 'S'));*/

        // PDF_Hospのインスタンスを生成し、内容を追加
        $pdf_hosp = new PDF_Hosp();
        $pdf_hosp->SetMargins(10, 10, 10); // マージン設定
        $pdf_hosp->AddPage();
        $pdf_hosp->ViewChart($uid);
        $pdf_hosp->AddPage();
        $pdf_hosp->writeHTML($pdf_hosp->Output('', 'S'));

        // 結果を表示
        $pdf_hosp->Output('combined.pdf', 'I');
    } else {
        echo "無効なUIDです。";
    }
}

function isValidUid($uid) {
    return isset($uid) && !empty($uid);
}

if (isset($_REQUEST['uid'])) {
    generatePDF($_REQUEST['uid']);
} else {
    echo "UIDが指定されていません。";
}
?>

