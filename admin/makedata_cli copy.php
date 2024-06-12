#!/home/xs728645/bin/php
<?php

require_once __DIR__ . '/../lib/chart_lib.php';
require_once __DIR__ . '/../lib/chart_lib_hosp2.php';
require_once __DIR__ . '/../lib/chart_lib_ward2.php';
// TCPDFのデバッグモードを有効にする
define('K_PATH_FONTS', __DIR__ . '/../lib/TCPDF-main/fonts/');
define('K_TCPDF_EXTERNAL_CONFIG', true);
define('K_TCPDF_CALLS_IN_HTML', true);

require_once __DIR__ . '/../lib/chart_lib.php';


$log_file = "/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log";  // ログファイルのパス

// フォントファイルのパスを確認
$fontPath = realpath('/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/fonts/ZenOldMincho-Regular.ttf');

echo "$fontPath\n";
// フォントファイルが存在するか確認
if ($fontPath && file_exists($fontPath)) {
    // TCPDFにフォントを追加
    sleep(1); // 一時的な待機
    $fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32, false, false, true);
    if (!$fontname) {
        $error = error_get_last();
        log_message("TCPDFにフォントを追加する際にエラーが発生しました: $fontPath - " . $error['message']);
        exit("TCPDFにフォントを追加する際にエラーが発生しました: " . $error['message'] . "\n");
    } else {
        log_message("TCPDFにフォントを追加しました: $fontPath");
    }
} else {
    log_message("フォントファイルが見つかりません: $fontPath");
    exit("フォントファイルが見つかりません: $fontPath\n");
}



function log_message($message) {
    global $log_file;
    error_log($message . "\n", 3, $log_file);
}

// スクリプトの開始をログに記録
log_message("MAKEDATA_CLI: スクリプト開始 - 引数: " . implode(", ", $argv));


/**
 * ファイルをZIP圧縮する
 *
 * @param string $filename ZIPファイル名
 */
function file2zip($filename)
{
    log_message("MAKEDATA: ZIPファイル作成開始 - $filename");
    $zip = new ZipArchive();
    if ($zip->open('./dl/' . $filename . '.zip', ZipArchive::CREATE) !== true) {
        log_message("MAKEDATA: ZIPファイルの作成に失敗しました");
        exit("ZIPファイルの作成に失敗しました。\n");
    }

    $files = glob('./pdf_bat/*.pdf');
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();

    array_map('unlink', glob('./pdf_bat/*.pdf'));
    log_message("MAKEDATA: ZIPファイル作成完了 - $filename");
}

/**
 * ディレクトリをZIP圧縮する
 *
 * @param string $filename ZIPファイル名
 * @param string $mode モード
 */
function dir2zip($filename, $mode = 'ui')
{
    log_message("MAKEDATA: ディレクトリZIPファイル作成開始 - $filename, mode: $mode");
    $zip = new ZipArchive();
    $zipFileName = ($mode === 'cron') ? './cron/pdf.zip' : './pdf_bat/' . $filename . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        log_message("MAKEDATA: ディレクトリZIPファイルの作成に失敗しました");
        exit("ZIPファイルの作成に失敗しました。\n");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('./pdf_bat/'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen('./pdf_bat/'));
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    log_message("MAKEDATA: ディレクトリZIPファイル作成完了 - $filename, mode: $mode");
}

/**
 * CSVファイルをZIP圧縮する
 *
 * @param string $filename ZIPファイル名
 * @param string $mode モード
 */
function csvdir2zip($filename, $mode = 'ui')
{
    log_message("MAKEDATA: CSV ZIPファイル作成開始 - $filename, mode: $mode");
    $zip = new ZipArchive();
    $zipFileName = ($mode === 'cron') ? './cron/csv.zip' : './dl/' . $filename . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        log_message("MAKEDATA: CSV ZIPファイルの作成に失敗しました");
        exit("ZIPファイルの作成に失敗しました。\n");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('./csv_bat/'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen('./csv_bat/'));
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    log_message("MAKEDATA: CSV ZIPファイル作成完了 - $filename, mode: $mode");
}

/**
 * データベースから値を取得する
 *
 * @param mysqli $db データベース接続オブジェクト
 * @param string $query SQLクエリ
 * @return mixed クエリ結果
 */
function fetchData($db, $query)
{
    log_message("MAKEDATA: データベースクエリ実行 - $query");
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        log_message("MAKEDATA: データベースクエリエラー - " . $db->error);
        exit('データベースクエリエラー: ' . $db->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    log_message("MAKEDATA: データベースクエリ成功 - $query");
    return $data;
}

/**
 * データベースに値を挿入する
 *
 * @param mysqli $db データベース接続オブジェクト
 * @param string $query SQLクエリ
 */
function insertData($db, $query)
{
    log_message("MAKEDATA: データベース挿入クエリ実行 - $query");
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        log_message("MAKEDATA: データベースクエリエラー - " . $db->error);
        exit('データベースクエリエラー: ' . $db->error);
    }

    $stmt->execute();
    $stmt->close();
    log_message("MAKEDATA: データベース挿入クエリ成功 - $query");
}

/**
 * ステータスファイルを更新する
 *
 * @param string $message メッセージ
 */
function updateStatus($message)
{
    log_message("MAKEDATA: ステータス更新 - $message");
    if (!file_exists("./status")) {
        mkdir("./status", 0777, true);
    }

    $fno = fopen("./status/status", 'w');
    fwrite($fno, $message);
    fclose($fno);
    chmod("./status/status", 0777);
}

/**
 * PDF作成処理
 *
 * @param mysqli $db データベース接続オブジェクト
 * @param string $year 年度
 * @param array $argv CLI引数
 */
function createPDFs($db, $year, $argv)
{
    log_message("MAKEDATA: PDF作成処理開始 - year: $year, argv: " . implode(", ", $argv));
    // 実行中プロセスが無いか確認
    $process = fetchData($db, "SELECT * FROM process");
    if (!empty($process)) {
        log_message("MAKEDATA: 実行中のプロセスあり - 停止します");
        exit("STOP:NOW WORKING.");
    }

    // プロセスの記録
    insertData($db, "INSERT INTO process(pid) VALUES(" . posix_getpid() . ")");

    // ディレクトリごと削除、ディレクトリを再作成
    exec("rm -fR ./pdf_bat/");
    mkdir("./pdf_bat/", 0777, true);

    if ($argv[1] === "pdf_all") {
        log_message("MAKEDATA: 全PDF作成開始");
        // 病院ディレクトリ作成
        $hospitals = fetchData($db, "SELECT SUBSTRING(uid,4,7) AS hospital FROM usr WHERE uid LIKE '$year%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,4,7) ORDER BY SUBSTRING(uid,4,7)");

        $arr_hospital = [];
        $hospital_count = 1;

        foreach ($hospitals as $hospital) {
            $arr_hospital[$hospital['hospital']] = $hospital_count++;
            if (!file_exists("./pdf_bat/{$hospital['hospital']}")) {
                mkdir("./pdf_bat/{$hospital['hospital']}", 0777, true);
            }

            // 病棟ディレクトリ作成
            $wards = fetchData($db, "SELECT SUBSTRING(uid,12,2) AS ward FROM usr WHERE uid LIKE '$year-{$hospital['hospital']}%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,12,2) ORDER BY SUBSTRING(uid,12,2)");

            foreach ($wards as $ward) {
                if (!file_exists("./pdf_bat/{$hospital['hospital']}/{$ward['ward']}")) {
                    mkdir("./pdf_bat/{$hospital['hospital']}/{$ward['ward']}", 0777, true);
                }
            }
        }

        // 対象ユーザ抽出
        $users = fetchData($db, "SELECT uid FROM usr WHERE uid LIKE '$year%' AND comp='1' AND del='1' ORDER BY uid");

        // 全国平均取得(構造、過程、アウトカム)
        $avg_point = [];
        for ($i = 1; $i <= 3; $i++) {
            $avg_point[$i] = getNowAverage($year, $i);
        }

        // 全PDF作成
        $hosp = "";
        $ward = "";
        foreach ($users as $user) {
            $uid = $user['uid'];
            if ($hosp != substr($uid, 3, 7)) {
                updateStatus("現在PDF生成中です。(" . sprintf("%d", $arr_hospital[$hosp] / $hospital_count * 100) . "%完了)");

                $hosp = substr($uid, 3, 7);
                $pdf = new PDF_Hosp;
                $pdf->SaveChart($year . "-" . $hosp, "./pdf_bat/" . $hosp . "/");

                $ward = substr($uid, 11, 2);
                $pdf = new PDF_Ward;
                $pdf->SaveChart($year . "-" . $hosp . "-" . $ward, "./pdf_bat/" . $hosp . "/" . $ward . "/");
            } elseif ($ward != substr($uid, 11, 2)) {
                $ward = substr($uid, 11, 2);
                $pdf = new PDF_Ward;
                $pdf->SaveChart($year . "-" . $hosp . "-" . $ward, "./pdf_bat/" . $hosp . "/" . $ward . "/");
            }

            if (is_numeric(substr($uid, 14)) && substr($uid, 14) > 50) {
                continue;
            }

            $pdf = new PDF;
            $type_no = getTypeNo(substr($uid, 14));
            $pdf->SaveChart($uid, $avg_point[$type_no], "./pdf_bat/" . $hosp . "/" . $ward . "/");
        }

        dir2zip($year, $argv[2]);
        exec("rm -fR ./status/");
        mkdir("./status/", 0777, true);
        log_message("MAKEDATA: 全PDF作成完了");

    } elseif ($argv[1] === "csv_all") {
        log_message("MAKEDATA: 全CSV作成開始");
        exec("rm -fR ./csv_bat/");
        mkdir("./csv_bat/", 0777, true);

        // 病院ディレクトリ作成
        $hospitals = fetchData($db, "SELECT SUBSTRING(uid,4,7) AS hospital FROM usr WHERE uid LIKE '$year%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,4,7) ORDER BY SUBSTRING(uid,4,7)");

        $arr_hospital = [];
        $hospital_count = 1;
        foreach ($hospitals as $hospital) {
            $arr_hospital[$hospital['hospital']] = $hospital_count++;
        }

        foreach ($arr_hospital as $key => $val) {
            updateStatus("現在CSV生成中です。(" . sprintf("%d", $val / $hospital_count * 100) . "%完了)");

            $hospital_uid = $year . "-" . $key;
            $csv = recom_csv($hospital_uid);
            file_put_contents("./csv_bat/" . $hospital_uid . "-recom.csv", mb_convert_encoding($csv, 'sjis-win', 'eucjp-win'));

            $wards = fetchData($db, "SELECT SUBSTRING(uid,4,10) AS hospital FROM usr WHERE uid LIKE '$year-$key%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,4,10) ORDER BY SUBSTRING(uid,4,10)");
            foreach ($wards as $ward) {
                $ward_uid = $year . "-" . $ward['hospital'];
                $csv = recom_csv($ward_uid);
                file_put_contents("./csv_bat/" . $ward_uid . "-recom.csv", mb_convert_encoding($csv, 'sjis-win', 'eucjp-win'));
            }
        }

        csvdir2zip($year . "-csv", $argv[2]);
        exec("rm -fR ./status/");
        mkdir("./status/", 0777, true);
        log_message("MAKEDATA: 全CSV作成完了");

    } elseif ($argv[1] === 'pdf_h' || $argv[1] === 'pdf_w') {
        log_message("MAKEDATA: PDF作成開始 - {$argv[1]}");
        // ファイル名
        $usr = $year . "-" . $argv[2] . "-" . $argv[3];
        if ($argv[1] === 'pdf_w') {
            $usr .= "-" . $argv[4];
        }

        // 対象ユーザ抽出
        $users = fetchData($db, "SELECT uid FROM usr WHERE uid LIKE '$usr%' AND comp='1' AND del='1' ORDER BY uid");

        // 全国平均取得(構造、過程、アウトカム)
        $avg_point = [];
        for ($i = 1; $i <= 3; $i++) {
            $avg_point[$i] = getNowAverage($year, $i);
        }

        // ユーザ毎にPDF作成
        foreach ($users as $user) {
            $pdf = new PDF;
            $type_no = getTypeNo(substr($user['uid'], 14));
            $pdf->SaveChart($user['uid'], $avg_point[$type_no], "./pdf_bat/");
        }

        // ZIPファイル化
        file2zip($usr . ".zip");
        log_message("MAKEDATA: PDF作成完了 - {$argv[1]}");
    } elseif ($argv[1] === 'pdf_ht' || $argv[1] === 'pdf_wt') {
        log_message("MAKEDATA: PDF集計作成開始 - {$argv[1]}");
        // ファイル名
        $file_name = $year . "-" . $argv[2] . "-" . $argv[3];
        if ($argv[1] === 'pdf_wt') {
            $file_name .= "-" . $argv[4];
        }

        // PDF作成
        $pdf = ($argv[1] === 'pdf_ht') ? new PDF_Hosp : new PDF_Ward;
        $pdf->SaveChart($file_name);

        // ZIPファイル化
        file2zip($file_name . "_total.zip");
        log_message("MAKEDATA: PDF集計作成完了 - {$argv[1]}");
    } elseif (in_array($argv[1], ['csv_h', 'csv_t', 'recom_h', 'csv_w', 'csv_wt', 'recom_w'])) {
        log_message("MAKEDATA: CSV作成開始 - {$argv[1]}");
        updateStatus("現在CSV生成中です。<br>しばらくこのままでお待ちください");

        $file_name = $year . "-" . $argv[2] . "-" . $argv[3];
        if (in_array($argv[1], ['csv_w', 'csv_wt', 'recom_w'])) {
            $file_name .= "-" . $argv[4];
        }

        if (in_array($argv[1], ['csv_h', 'csv_w'])) {
            $csv = createCSVData($file_name);  // CSVデータ作成
        } elseif (in_array($argv[1], ['csv_t', 'csv_wt'])) {
            $csv = CreateCSV_textonly($file_name);  // CSVデータ作成
        } elseif (in_array($argv[1], ['recom_h', 'recom_w'])) {
            $csv = recom_csv($file_name); // CSVデータ作成
        }

        exec('rm -f ./dl/*');

        file_put_contents("./dl/" . $file_name . ".csv", mb_convert_encoding($csv, 'sjis-win', 'eucjp-win'));

        exec("rm -fR ./status/");
        mkdir("./status/", 0777, true);
        log_message("MAKEDATA: CSV作成完了 - {$argv[1]}");
    }
}

log_message("MAKEDATA: データベース接続開始");
$db = Connection::connect();
$yearData = fetchData($db, "SELECT year FROM year");
$year = $yearData[0]['year'] ?? null;

if ($year) {
    createPDFs($db, $year, $argv);
}

mysqli_query($db, "DELETE FROM process");
Connection::disconnect($db);
log_message("MAKEDATA: 処理完了");
?>
