<?php

require_once '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/tcpdf.php';

// TCPDFオブジェクトの生成
$pdf = new TCPDF();

// ドキュメント情報の設定
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Sample PDF');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// フォントの設定
$pdf->SetFont('zenoldmincho', '', 12);

// 追加の設定
$pdf->AddPage();

// テキストを追加
$pdf->Cell(0, 10, 'こんにち、TCPDF！', 0, 1, 'C');

// PDF出力
$pdf->Output('sample.pdf', 'I');

// ログファイルのパス
$log_file = "/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log";

// ログメッセージを記録する関数
function log_message($message) {
    global $log_file;
    error_log($message . "\n", 3, $log_file);
}

// スクリプトの開始をログに記録
log_message("MAKEDATA_CLI: スクリプト開始 - 引数: " . implode(", ", $argv));

?>
