<?php
require_once '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/tcpdf.php';
require_once '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/include/tcpdf_fonts.php';

// フォントファイルのパス
$fontfile = '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/fonts/ヒラギノ丸ゴ ProN W4.ttc';

// フォントの出力先ディレクトリ
$outputDir = '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/fonts/';

try {
    // フォントを追加
    $fontname = TCPDF_FONTS::addTTFfont($fontfile, 'TrueTypeUnicode', '', 32, $outputDir);

    if ($fontname) {
        echo "フォントが正常に追加されました: $fontname\n";
    } else {
        echo "フォントの追加に失敗しました。\n";
    }
} catch (Exception $e) {
    echo "例外が発生しました: " . $e->getMessage() . "\n";
}
?>
