<?php
require_once("setup.php");

$log_file = "/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log";  // ログファイルのパス

// リクエストパラメータをチェックし、引数とモードを設定する関数
function checkRequestParams() {
    global $log_file;
    error_log("STEP 2: リクエストパラメータのチェック - 処理開始\n", 3, $log_file);

    $arg = [];
    $mode = "PDF";

    if (isset($_REQUEST['pdf_all'])) {
        $arg[0] = "pdf_all";
    } elseif (isset($_REQUEST['csv_all'])) {
        $arg[0] = "csv_all";
        $mode = "CSV";
    } elseif (isset($_REQUEST['make'])) {
        $mode = $_REQUEST['make'];
    } elseif (isset($_REQUEST['ftype'])) {
        $arg[0] = $_REQUEST['ftype'];

        switch ($_REQUEST['ftype']) {
            case 'pdf_h':
            case 'pdf_ht':
            case 'csv_h':
            case 'csv_t':
            case 'recom_h':
                if (isset($_REQUEST['pref1'])) {
                    $arg[1] = $_REQUEST['pref1'];
                }
                if (isset($_REQUEST['hos1'])) {
                    $arg[2] = $_REQUEST['hos1'];
                }
                $arg[3] = ""; 
                break;

            case 'pdf_w':
            case 'pdf_wt':
            case 'csv_w':
            case 'csv_wt':
            case 'recom_w':
                if (isset($_REQUEST['pref2'])) {
                    $arg[1] = $_REQUEST['pref2'];
                }
                if (isset($_REQUEST['hos2'])) {
                    $arg[2] = $_REQUEST['hos2'];
                }
                if (isset($_REQUEST['ward'])) {
                    $arg[3] = $_REQUEST['ward'];
                }
                break;

            default:
                error_log("STEP 2: リクエストパラメータのチェック - 無効なファイルタイプが指定されました\n", 3, $log_file);
                die("無効なファイルタイプが指定されました。");
        }
    } else {
        error_log("STEP 2: リクエストパラメータのチェック - ファイルタイプが指定されていません\n", 3, $log_file);
        die("ファイルタイプが指定されていません。");
    }

    error_log("STEP 2: リクエストパラメータのチェック - 成功\n", 3, $log_file);
    return [$arg, $mode];
}

// 生成プロセスを開始する関数
function startGeneration($mode, $arg) {
    global $log_file;
    error_log("STEP 5: 生成プロセスの開始 - 処理開始\n", 3, $log_file);

    $curpath = pathinfo(__FILE__, PATHINFO_DIRNAME);

    if ($_REQUEST['pdf_all'] || $_REQUEST['csv_all']) {
        $cli = "~/bin/php -f " . $curpath . "/makedata_cli.php " . $arg[0];
    } else {
        $cli = "~/bin/php -f " . $curpath . "/makedata_cli.php " . implode(" ", $arg);
    }

    // CLIコマンドをログに記録
    error_log("CLI Command: $cli\n", 3, $log_file);

    // execの出力と戻り値を取得
    $output = [];
    $return_var = 0;
    exec($cli, $output, $return_var);

    // execの結果をログに記録
    error_log("exec output: " . implode("\n", $output) . "\n", 3, $log_file);
    error_log("exec return_var: $return_var\n", 3, $log_file);

    error_log("STEP 5: 生成プロセスの開始 - 成功\n", 3, $log_file);
    return "<p>" . $mode . "生成を開始しました。</p>しばらくこのままでお待ちください\n" . 
           "<script type='text/javascript'>\n" . 
           "var timer = 3000;\n" . 
           "function ReloadAddr(){\n" . 
           "window.location.href='download_pdf.php?make=$mode';\n" . 
           "}\n" . 
           "setTimeout(ReloadAddr, timer);\n" . 
           "</script>\n";
}



// 生成されたファイルをチェックする関数
function checkForGeneratedFiles() {
    global $log_file;
    error_log("STEP 6: 生成されたファイルのチェック - 処理開始\n", 3, $log_file);

    $curpath = pathinfo(__FILE__, PATHINFO_DIRNAME);
    $ls = exec("find " . $curpath . "/dl -type f");

    if ($ls) {
        $files = explode("\n", trim($ls));
        $output = "";

        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $output .= "生成が完了しました。<br><span style='color:#aaa;'><a href='$file' download='$filename'>$filename</a>" .
                           "　サイズ：" . round(filesize($file) / 1024, 2) . "KB" .
                           "　作成日時：" . date("Y/m/d H:i:s", fileatime($file)) . "</span>\n";
            }
        }

        if ($output) {
            error_log("STEP 6: 生成されたファイルのチェック - ファイルあり\n", 3, $log_file);
            return $output;
        } else {
            error_log("STEP 6: 生成されたファイルのチェック - ファイルなし\n", 3, $log_file);
            return "現在ダウンロード可能なファイルはありません。\n";
        }
    } else {
        error_log("STEP 6: 生成されたファイルのチェック - ファイルなし\n", 3, $log_file);
        return "現在ダウンロード可能なファイルはありません。\n";
    }
}

// メイン処理
error_log("STEP 1: リクエスト受け取り\n", 3, $log_file);
list($arg, $mode) = checkRequestParams();
$db = Connection::connect();
error_log("STEP 3: データベース接続 - 成功\n", 3, $log_file);

// プロセスが実行中の場合のメッセージ
if ($buf) {
    // プロセスが実行中の場合のメッセージ
    $file = @file_get_contents("./status/status");
    $file = $file ?: "<p>現在" . $mode . "生成中です</p>しばらくこのままでお待ちください";
    $msg = "<p>$file</p>" . "<script type='text/javascript'>\n" . 
           "var timer = 10000;\n" . // 10秒ごとに確認
           "function ReloadAddr(){\n" . 
           "window.location.href='download_pdf.php?make=$mode';\n" . 
           "}\n" . 
           "setTimeout(ReloadAddr, timer);\n" . 
           "</script>\n";
} elseif (isset($_REQUEST['gen'])) {
    $msg = startGeneration($mode, $arg);
} else {
    $msg = checkForGeneratedFiles();
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin.css" media="all">
<title>ダウンロード</title>
</head>
<body>

<div align='center'>
    <h1>QIシステム</h1>
    <table cellspacing='1' cellpadding='5'>
    <tr><th><a href='index.php'>メニュー</a> ≫ <a href='download.php'>ダウンロード</a> ≫ PDF・CSV生成</th></tr>
    <tr><td>
        <?php
            echo "<p style='margin: 2px;padding: 5px;background: #dddddd;'>$msg</p>";

            // 生成開始ボタンを表示する場合の処理
            if (!isset($_REQUEST['gen']) && !$buf) {
                echo "<p>$mode を作成するには下記のボタンをクリックしてください。※作成済みファイルは上書きされます。</p>";
                echo "<form action='./download_pdf.php' method='post'>
                      <input type='hidden' name='pdf_all' value='{$_REQUEST['pdf_all']}'>
                      <input type='hidden' name='csv_all' value='{$_REQUEST['csv_all']}'>
                      <input type='hidden' name='ftype' value='{$_REQUEST['ftype']}'>
                      <input type='hidden' name='pref1' value='{$_REQUEST['pref1']}'>
                      <input type='hidden' name='pref2' value='{$_REQUEST['pref2']}'>
                      <input type='hidden' name='hos1' value='{$_REQUEST['hos1']}'>
                      <input type='hidden' name='hos2' value='{$_REQUEST['hos2']}'>
                      <input type='hidden' name='ward' value='{$_REQUEST['ward']}'>
                      <div style='margin:10px;'><input type='submit' name='gen' value='≫$mode 作成開始'></div>
                      </form>";
            }
        ?>
    </td></tr>
    </table>
</div>

</body>
</html>
