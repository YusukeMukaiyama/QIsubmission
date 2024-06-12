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

// ページを追加
$pdf->AddPage();

// テキストを追加
$pdf->Cell(0, 10, 'こんにちは、Zen old mincho!', 0, 1, 'C');

// PDF出力
$pdf->Output('sample_zenmarugothic.pdf', 'I');
?>
