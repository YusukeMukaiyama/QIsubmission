<?php
/*******************************************************************
    ファイル名：download.php
    概要      ：管理者画面：ダウンロード
                                    (C)2005,University of Hyougo.
*******************************************************************/

require_once("setup.php");

$log_file = "/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log";  // ログファイルのパス

// フォントファイルのパスを確認
$fontPath = '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/TCPDF-main/fonts/ZenOldMincho-Regular.ttf';

// フォントファイルの存在を確認する
if (file_exists($fontPath)) {
    error_log("フォントファイルが見つかりました: " . $fontPath, 3, '/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log');
} else {
    error_log("フォントファイルが見つかりません: " . $fontPath, 3, '/home/xs728645/tuneup-works.com/public_html/qisystem/admin/error_log');
    exit("フォントファイルが見つかりません: " . $fontPath);
}

// ステップ1: リクエスト受け取り
error_log("STEP 1: リクエスト受け取り\n", 3, $log_file);

function connectDatabase() {
    return Connection::connect();
}

function closeDatabase($db) {
    mysqli_close($db);
}

function executeQuery($sql, $params, $types) {
    $db = connectDatabase();
    $stmt = mysqli_prepare($db, $sql);
    if ($stmt === false) {
        return ["error" => "クエリ準備エラー"];
    }

    if ($params && $types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        return ["error" => "クエリ実行エラー"];
    }

    $res = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    closeDatabase($db);

    return $data;
}

function getOptions($sql, $params, $types, $selectedValue, $formatFunction) {
    $results = executeQuery($sql, $params, $types);

    if (isset($results["error"])) {
        return sprintf("<option value=''>%s</option>", $results["error"]);
    }

    $options = ["<option value='' style='color: #000; background-color: #fff;'>--選択して下さい--</option>"];
    foreach ($results as $row) {
        $options[] = $formatFunction($row, $selectedValue);
    }

    return implode("\n", $options);
}

function formatPrefOption($row, $selectedValue) {
    $sel = ($selectedValue == (int)$row['pid']) ? "selected" : "";
    return sprintf("<option value='%02d' %s style='color: #000; background-color: #fff;'>%s</option>", $row['pid'], $sel, Config::$prefName[(int)$row['pid']]);
}

function formatHospitalOption($row, $selectedValue) {
    $sel = ($selectedValue == $row['id']) ? "selected" : "";
    return sprintf("<option value='%s' %s>%s</option>", $row['id'], $sel, $row['id']);
}

function formatWardOption($row, $selectedValue) {
    $sel = ($selectedValue == $row['id']) ? "selected" : "";
    return sprintf("<option value='%s' %s>%s</option>", $row['id'], $sel, $row['id']);
}

function getPrefectures($year, $pref) {
    $sql = "SELECT DISTINCT SUBSTRING(uid FROM 4 FOR 2) AS pid FROM usr WHERE uid LIKE ? AND comp='1' ORDER BY pid ASC";
    $yearParam = $year . '-%';
    return getOptions($sql, [$yearParam], 's', $pref, 'formatPrefOption');
}

function getHospital($year, $pref, $hosp) {
    if (!$year || !$pref) return "";
    $sql = "SELECT DISTINCT SUBSTRING(uid FROM 7 FOR 4) AS id FROM usr WHERE uid LIKE ? AND comp='1' ORDER BY 1 ASC";
    $uidParam = sprintf('%s-%s%%', $year, $pref);
    return getOptions($sql, [$uidParam], 's', $hosp, 'formatHospitalOption');
}

function getWard($year, $pref, $hosp, $ward) {
    if (!$year || !$pref || !$hosp) return "";
    $sql = "SELECT DISTINCT SUBSTRING(uid FROM 12 FOR 2) AS id FROM usr WHERE uid LIKE ? AND comp='1' ORDER BY 1 ASC";
    $uidParam = sprintf('%s-%s-%s%%', $year, $pref, $hosp);
    return getOptions($sql, [$uidParam], 's', $ward, 'formatWardOption');
}

function check_uid($uid) {
    if (!preg_match("/^(\d{2})-(\d{2})-(\d{4})-(\d{2})-(\d{3})$/", $uid)) {
        return null;
    }

    $parts = explode("-", $uid);
    if (count($parts) !== 5) return null;

    if ((int)$parts[1] < 1 || (int)$parts[1] > 47) {
        return null;
    }

    return $uid;
}

function getYear() {
    $db = connectDatabase();
    $sql = "SELECT year FROM year";
    $res = mysqli_query($db, $sql);
    $fld = mysqli_fetch_object($res);
    $year = $fld->year;
    mysqli_free_result($res);
    closeDatabase($db);
    return $year;
}

// ステップ3: データベース接続直後
$db = connectDatabase();
error_log("STEP 3: データベース接続 - 成功\n", 3, $log_file);
// デバッグ情報: $_POSTの内容をログに出力
error_log("POST data: " . print_r($_POST, true) . "\n", 3, $log_file);

$year = getYear();

if (isset($_POST['pdf_u']) && $_POST['pdf_u'] != "") {
    // ステップ2: リクエストパラメータのチェック
    error_log("STEP 2: リクエストパラメータのチェック - 成功\n", 3, $log_file);

    $id = $_POST['uid'];
    if ($id == "") {
        $err_str = "IDを入力してください。";
    } else if (check_uid($id) == NULL) {
        $err_str = "入力したIDの形式に誤りがあります。";
    } else {
        // ステップ4: データ取得とチェック直後
        $sql = "SELECT comp FROM usr WHERE uid=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res->num_rows) {
            $err_str = "指定したユーザは存在していません。";
        } else {
            $fld = $res->fetch_object();
            if ($fld->comp != "1") {
                $err_str = "指定したユーザは未回答です。";
            } else {
                $PDF_ID = $id;
            }
        }
        $stmt->close();
        error_log("STEP 4: データ取得とチェック - 成功\n", 3, $log_file);
    }
}
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="./admin.css" media="all">
<title>ダウンロード</title>
<script type='text/javascript'>
function down_check(form, ftype) {
    if (confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")) {
        document.forms[form].action = './makedata.php'; 
        document.forms[form].target = '_self'; 
        document.forms[form].ftype.value = ftype; 
        document.forms[form].submit(); 
    }
}

function down_check_pdf(form, ftype) {
    document.forms[form].action = './download_pdf.php'; 
    document.forms[form].target = '_self'; 
    document.forms[form].ftype.value = ftype; 
    document.forms[form].submit(); 
}

function redraw(fm, no) {
    switch (no) {
        case 0: fm.hos1.selectedIndex = 0; break;
        case 1: fm.hos2.selectedIndex = 0;
        case 2: fm.ward.selectedIndex = 0;
        default: break;
    }
    fm.action = './download.php';
    fm.target = '_self'; 
    fm.submit();
}
</script>
</head>
<body>

<div align='center'>
    <h1>QIシステム</h1>

    <table cellspacing='1' cellpadding='5'>
    <tr><th><a href='index.php'>メニュー</a> ≫ ダウンロード</th></tr>
    <tr><td>20<?= $year ?>年度のデータをダウンロードします。

        <p style='margin : 2px;padding : 5px;background : #dddddd;'>
<?php
    $curpath = pathinfo(__FILE__)['dirname'];

    function getAllFiles($dir) {
        $files = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($rii as $file) {
            if (!$file->isDir()) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    $pdfFiles = getAllFiles($curpath."/pdf_bat");
    $csvFiles = getAllFiles($curpath."/csv_bat");

    if ($ls) {
        if (is_file($ls)) {
            $filename = basename($ls);
            echo "現在ダウンロード可能な生成済みファイルは以下のとおりです。<br>\n";
            echo "<span style='color:#aaa;'><a href='./pdf_bat/".$filename."'>".$filename."</a>".
                    "　サイズ：".round(filesize($ls) / 1024, 2)."KB".
                    "　作成日時：".date("Y/m/d H:i:s", fileatime($ls))."</span>\n";
        } else {
            echo "現在ダウンロード可能なファイルはありません。\n";
        }
    } else {
        echo "現在ダウンロード可能なファイルはありません。\n";
    }

    $rs = mysqli_query($db, "SELECT pid FROM process") or die("db error");
    if (mysqli_num_rows($rs)) {
        $fld = mysqli_fetch_object($rs);
        $pid = $fld->pid;
        $buf = exec("ps -axp ".$pid." | grep ".$pid);
        if ($buf) {
            echo "現在PDF・CSV生成中です。";
        } else {
            mysqli_query($db, "TRUNCATE TABLE process") or die("db error");
        }
    }
    mysqli_free_result($rs);
?>
        </p>

        <table cellspacing='1' cellpadding='5'>
        <tr><th colspan='3'>一括(集計済み)</th><td>
<?php
    $ls = exec("ls ".$curpath."/cron/pdf.zip");
    if ($ls && is_file($ls)) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."'>全PDF</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>";
    } else {
        print "全PDF(作成中)　";
    }
    $ls = exec("ls ".$curpath."/cron/csv.zip");
    if ($ls && is_file($ls)) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."'>全CSV</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>";
    } else {
        print "全CSV(作成中)　";
    }
?>
        </td></tr>

        <form action='download.php' method='post' name='hos'>
        <input type='hidden' name='ftype' value=''>

        <tr><th rowspan='2'>病院単位</th><th>都道府県</th>
            <td><select name='pref1' onchange='redraw(this.form, 0)'><?php echo getPrefectures($year, $_POST['pref1']); ?></select></td>
<?php
    $buttonHtml = "<td rowspan='2'>".
                  "<input type='submit' name='pdf_h' value='PDF'>　".
                  "<input type='submit' name='pdf_ht' value='PDF(集計)'>　".
                  "<input type='submit' name='csv_h' value='CSV'>　".
                  "<input type='submit' name='csv_t' value='CSV(テキスト回答)'>　".
                  "<input type='submit' name='recom_h' value='CSV(リコメンデーション)'>";

    if ($_POST['hos1'] != "") {
        $buttonHtml = "<td rowspan='2'>".
                      "<input type='button' name='pdf_h' value='PDF' onClick=\"down_check_pdf('hos', 'pdf_h')\">　".
                      "<input type='button' name='pdf_ht' value='PDF(集計)' onClick=\"down_check_pdf('hos', 'pdf_ht')\">　".
                      "<input type='button' name='csv_h' value='CSV' onClick=\"down_check_pdf('hos', 'csv_h')\">　".
                      "<input type='submit' name='csv_t' value='CSV(テキスト回答)' onClick=\"down_check_pdf('hos', 'csv_t')\">　".
                      "<input type='submit' name='recom_h' value='CSV(リコメンデーション)' onClick=\"down_check_pdf('hos', 'recom_h')\">";
    }

    if ($_POST['pdf_h'] != "" || $_POST['pdf_ht'] != "" || $_POST['csv_h'] != "" || $_POST['csv_t'] != "" || $_POST['recom_h'] != "") {
        if ($_POST['hos1'] == "") {
            $buttonHtml .= ErrorHandling::DispErrMsg("<br><br>選択してください。");
        }
    }
    echo $buttonHtml;
?>
        </td></tr>
        <tr><th>病院No.</th><td>
            <select name='hos1' onchange='redraw(this.form, 3)'>
                <?php echo getHospital($year, $_POST['pref1'], $_POST['hos1']) ?>
            </select>
        </td></tr>
        </form>

        <form action='download.php' method='post' name='ward'>
        <input type='hidden' name='ftype' value=''>
        <tr><th rowspan='3'>病棟単位</th><th>都道府県</th>
            <td><select name='pref2' onchange='redraw(this.form, 1)'><?php echo getPrefectures($year, $_POST['pref2']); ?></select></td>
<?php
    $buttonHtml = "<td rowspan='3'>".
                  "<input type='submit' name='pdf_w' value='PDF'>　".
                  "<input type='submit' name='pdf_w' value='PDF(集計)'>　".
                  "<input type='submit' name='csv_w' value='CSV'>　".
                  "<input type='submit' name='csv_wt' value='CSV(テキスト回答)'>　".
                  "<input type='submit' name='recom_w' value='CSV(リコメンデーション)'>";

    if ($_POST['ward'] != "") {
        $buttonHtml = "<td rowspan='3'>".
                      "<input type='button' name='pdf_w' value='PDF' onClick=\"down_check_pdf('ward', 'pdf_w')\">　".
                      "<input type='button' name='pdf_wt' value='PDF(集計)' onClick=\"down_check_pdf('ward', 'pdf_wt')\">　".
                      "<input type='button' name='csv_w' value='CSV' onClick=\"down_check_pdf('ward', 'csv_w')\">　".
                      "<input type='submit' name='csv_wt' value='CSV(テキスト回答)' onClick=\"down_check_pdf('ward', 'csv_wt')\">　".
                      "<input type='submit' name='recom_w' value='CSV(リコメンデーション)' onClick=\"down_check_pdf('ward', 'recom_w')\">";
    }

    if ($_POST['pdf_w'] != "" || $_POST['pdf_wt'] != "" || $_POST['csv_w'] != "" || $_POST['csv_wt'] != "" || $_POST['recom_w'] != "") {
        if ($_POST['ward'] == "") {
            $buttonHtml .= ErrorHandling::DispErrMsg("<br><br>選択してください。");
        }
    }
    echo $buttonHtml;
?>
        </td></tr>
        <tr><th>病院No.</th><td><select name='hos2' onchange='redraw(this.form, 2)'><?php echo getHospital($year, $_POST['pref2'], $_POST['hos2']) ?></select></td></tr>
        <tr><th>病棟No.</th><td><select name='ward' onchange='redraw(this.form, 3)'><?php echo getWard($year, $_POST['pref2'], $_POST['hos2'], $_POST['ward']) ?></select></td></tr>
        </form>

        <form action='download.php' method='post' name='IDSELECT'>
            <input type='hidden' name='PDF_ID' value=''>
            <tr><th>ID指定</th><th>ID</th>
                <td><input size='22' type='text' maxlength='17' name='uid' value='<?php echo $_POST['uid'] ?>' onKeyDown='if (window.event.keyCode==13) {return false;}'></td>
                <td><input type='submit' name='pdf_u' value='PDF'><?php if ($err_str != "") echo ErrorHandling::DispErrMsg("<br><br>".$err_str); ?></td></tr>
        </form>

        </table>
    </td></tr>

    <tr><th>集計データダウンロード(集計済み)</th></tr>
    <tr><td>
<?php
    $ls = exec("ls ".$curpath."/cron/structure-ttl.csv");
    if ($ls && is_file($ls) && filesize($ls) > 0) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>構造</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>";
    } else {
        print "構造(作成中)　";
    }
    $ls = exec("ls ".$curpath."/cron/process-ttl.csv");
    if ($ls && is_file($ls) && filesize($ls) > 0) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>過程</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>";
    } else {
        print "過程(作成中)　";
    }
    $ls = exec("ls ".$curpath."/cron/outcome-ttl.csv");
    if ($ls && is_file($ls) && filesize($ls) > 0) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>アウトカム</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>";
    } else {
        print "アウトカム(作成中)　";
    }
    $ls = exec("ls ".$curpath."/cron/ttl-avg.csv");
    if ($ls && is_file($ls) && filesize($ls) > 0) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>総合</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>";
    } else {
        print "総合(作成中)　";
    }
?>
    </td></tr>

    <tr><th>アンケートデータダウンロード(集計済み)</th></tr>
    <tr><td>
<?php
    $ls = exec("ls ".$curpath."/cron/ttl-enq.csv");
    if ($ls && is_file($ls) && filesize($ls) > 0) {
        $filename = basename($ls);
        print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>総合</a>　作成日時：".date("Y/m/d H:i:s", filemtime($ls))."<br>※過程における質問・回答のテキスト回答データ";
    } else {
        print "総合(作成中)";
    }
?>
    </td></tr>
    </table>

<?php
if ($_POST['pdf_u'] != "" && $err_str == "") {
    // ステップ5: PDF生成プロセスの開始
    error_log("STEP 5: PDF生成プロセスの開始\n", 3, $log_file);

    // 仮のPDF生成プロセス例
    $pdf_content = "ユーザID: " . $_POST['uid'] . "\n";
    $pdf_content .= "PDF生成日時: " . date("Y-m-d H:i:s") . "\n";

    // ステップ6: PDF内容の構築開始直後
    error_log("STEP 6: PDF内容の構築 - 処理開始\n", 3, $log_file);

    // 実際のPDF生成処理の例（適宜修正してください）
    try {
        $pdf_file_path = "/path/to/save/" . $_POST['uid'] . ".pdf";
        file_put_contents($pdf_file_path, $pdf_content);

        // ステップ7: PDFファイルの保存開始直後
        error_log("STEP 7: PDFファイルの保存 - 処理開始\n", 3, $log_file);
        
        // 生成されたPDFのパスを保存
        $download_link = "http://yourdomain.com/path/to/save/" . $_POST['uid'] . ".pdf";

        // ステップ8: ダウンロードリンクの提供直後
        error_log("STEP 8: ダウンロードリンクの提供\n", 3, $log_file);
        
        // ダウンロードリンクを表示（例）
        echo "PDFファイルが生成されました。<a href='$download_link'>ダウンロード</a>";
    } catch (Exception $e) {
        error_log("PDF生成中にエラーが発生しました: " . $e->getMessage() . "\n", 3, $log_file);
    }
}
?>
</div>

</body>
</html>
