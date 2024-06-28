<?php
// 必要なファイルの読み込み
require_once('../lib/PDF_Ward.php');
require_once('../lib/PDF_Hosp.php');
require_once('../lib/PDF.php');
require_once('setup.php');

// 表示するPDFの種類とIDを取得
$uid = $_REQUEST['uid'];
$type = UserClassification::getUserType($uid);

// PDFの生成と表示
switch($type) {
    case Config::STRUCTURE:
        $pdf = new PDF_Hosp();
        $pdf->ViewChart($id);
        break;
    case Config::PROCESS:
        $pdf = new PDF_Ward();
        $pdf->ViewChart($id);
        break;
    case Config::OUTCOME:
        $pdf = new PDF();
        $pdf->ViewChart($id);
        break;
    default:
        echo "無効なタイプが指定されました。";
        break;
}
?>
