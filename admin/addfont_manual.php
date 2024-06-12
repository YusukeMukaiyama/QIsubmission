<?php
define('K_TCPDF_EXTERNAL_CONFIG', true);
define('K_TCPDF_CALLS_IN_HTML', true);
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/tcpdf.php';
require_once '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/include/tcpdf_fonts.php';

$log_file = "/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log";  // ログファイルのパス

// ログファイルへの書き込み関数
function log_message($message) {
    global $log_file;
    error_log($message . "\n", 3, $log_file);
}

log_message("フォントの追加を開始します");

// フォントファイルのパス
$fontfile = '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/fonts/ヒラギノ丸ゴ ProN W4.ttc';

if (file_exists($fontfile)) {
    log_message("フォントファイルが存在します: $fontfile");

    try {
        // フォントを追加
        $fontname = TCPDF_FONTS::addTTFfont($fontfile, 'TrueTypeUnicode', '', 32);
        
        if ($fontname) {
            log_message("フォントが正常に追加されました: $fontname");
            echo "フォントが正常に追加されました: $fontname\n";
        } else {
            $error = error_get_last();
            log_message("フォントの追加に失敗しました。エラー: " . ($error ? $error['message'] : '詳細不明'));
            echo "フォントの追加に失敗しました。エラー: " . ($error ? $error['message'] : '詳細不明') . "\n";
        }
    } catch (Exception $e) {
        log_message("例外が発生しました: " . $e->getMessage());
        echo "例外が発生しました: " . $e->getMessage() . "\n";
    }
} else {
    log_message("フォントファイルが見つかりません: $fontfile");
    echo "フォントファイルが見つかりません: $fontfile\n";
}
?>
