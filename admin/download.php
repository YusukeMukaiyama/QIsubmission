<?php
/*******************************************************************
	ファイル名：dpwnload.php
	概要　　　：管理者画面：ダウンロード
									(C)2005,University of Hyougo.
*******************************************************************/

require_once ("setup.php");


/*******************************************************************
getPrefectures
	概要：対象年度に使用されている都道府県 <option> の生成
	引数：$year		選択されている年度
		：$pref		選択されている都道府県
	戻値：なし
*******************************************************************/
function getPrefectures(mysqli $db, int $year, int $pref): void
{
    global $prefName; // 都道府県名配列
    $query = "SELECT DISTINCT SUBSTRING(uid, 4, 2) AS pid FROM usr WHERE uid LIKE CONCAT(?, '-%') AND comp='1' ORDER BY pid ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<option value=''>--選択してください--</option>\n";
    while ($row = $result->fetch_assoc()) {
        $selected = ($pref == (int)$row['pid']) ? "selected" : "";
        printf("<option value='%02d' %s>%s</option>\n", $row['pid'], $selected, $prefName[(int)$row['pid']]);
    }
    $stmt->close();
}


/*******************************************************************
getHospital
	概要：病院 <option> の生成
	引数：$year		選択されている年度
		：$pref		選択されている都道府県
		：$hosp		選択されている病院
	戻値：なし
*******************************************************************/
function getHospital(mysqli $db, int $year, int $pref, string $hosp): void
{
    echo "<option value=''>--選択してください--</option>\n";
    if (!$year || !$pref) return;

    $query = "SELECT DISTINCT SUBSTRING(uid, 7, 4) AS id FROM usr WHERE uid LIKE CONCAT(?, '-', ?,'%') AND comp='1' ORDER BY 1 ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $year, $pref);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows < 1) return;
    
    while ($row = $result->fetch_assoc()) {
        $selected = ($hosp == $row['id']) ? "selected" : "";
        printf("<option value='%s' %s>%s</option>\n", $row['id'], $selected, $row['id']);
    }
    $stmt->close();
}

/*******************************************************************
getWard
	概要：病棟 <option> の生成
	引数：$year		選択されている年度
		：$pref		選択されている都道府県
		：$hosp		選択されている病院
		：$ward		選択されている病棟
	戻値：なし
*******************************************************************/
function getWard(mysqli $db, int $year, int $pref, string $hosp, string $ward): void
{
    echo "<option value=''>--選択してください--</option>\n";
    if (!$year || !$pref || !$hosp) return;

    $query = "SELECT DISTINCT SUBSTRING(uid, 12, 2) AS id FROM usr WHERE uid LIKE CONCAT(?, '-', ?, '-', ?,'%') AND comp='1' ORDER BY 1 ASC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("iii", $year, $pref, $hosp);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows < 1) return;
    
    while ($row = $result->fetch_assoc()) {
        $selected = ($ward == $row['id']) ? "selected" : "";
        printf("<option value='%s' %s>%s</option>\n", $row['id'], $selected, $row['id']);
    }
    $stmt->close();
}

/*******************************************************************
check_uid
	概要：uid の形式が正しいかチェックする
	引数：$uid		ユーザID文字列
	戻値：$uid		正常終了
		：NULL		エラー発生時
*******************************************************************/
function check_uid(string $uid): ?string
{
    if (!preg_match("/^\d{2}-\d{2}-\d{4}-\d{2}-\d{3}$/", $uid)) {
        return null;
    }
    $parts = explode("-", $uid);
    $lengths = [2, 2, 4, 2, 3];
    foreach ($parts as $index => $part) {
        if (strlen($part) != $lengths[$index]) {
            return null;
        }
    }
    return $uid;
}
/*******************************************************************
getYear
	概要：現在の年度を取得します。
	引数：$db   データベースオブジェクト
	戻値：年度の配列
*******************************************************************/
function getYear(mysqli $db): int
{
    $query = "SELECT year FROM year LIMIT 1";
    $result = $db->query($query);
    $row = $result->fetch_assoc();
    return (int) $row['year'];
}

$db = Connection::connect(); // データベース接続

// 年度取得
$year = getYear($db);


// ID指定ダウンロード時/
if (!empty($_POST['pdf_u'])) {
	$id = $_POST['uid'] ?? "";
	$err_str = ""; // 初期エラーメッセージは空
	$PDF_ID = ""; // PDF生成用のID初期化

	if ($id === "") {
			$err_str = "IDを入力してください。";
	} elseif (check_uid($id) === NULL) {
			$err_str = "入力したIDの形式に誤りがあります。";
	} else {
			$query = "SELECT comp FROM usr WHERE uid = ?";
			$stmt = $db->prepare($query);
			$stmt->bind_param("s", $id);
			$stmt->execute();
			$result = $stmt->get_result();

			if ($result->num_rows === 0) {
					$err_str = "指定したユーザは存在していません。";
			} else {
					$row = $result->fetch_assoc();
					if ($row['comp'] !== "1") {
							$err_str = "指定したユーザは未回答です。";
					} else {
							$PDF_ID = $id; // IDの保存
					}
			}
			$stmt->close();
	}
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>ダウンロード</title>
<script type='text/javascript'><!--
// 確認メッセージ表示
function down_check(form, ftype) {
	if (confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")) {
	    document.forms[form].action = './makedata.php'; 
	    document.forms[form].target = '_self'; 
	    document.forms[form].ftype.value = ftype; 
	    document.forms[form].submit(); 
	}
}

// PDF生成・ダウンロード
function down_check_pdf(form, ftype) {
    document.forms[form].action = './download_pdf.php'; 
    document.forms[form].target = '_self'; 
    document.forms[form].ftype.value = ftype; 
    document.forms[form].submit(); 
}


// <select>の選択変更
function redraw(fm, no)
{
	switch (no) {
		case 0:fm.hos1.selectedIndex = 0;break;
		case 1:fm.hos2.selectedIndex = 0;
		case 2:fm.ward.selectedIndex = 0;
		default:break;
	}
    fm.action = './download.php';
	fm.target = '_self'; 
	fm.submit();
}
//-->
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


	// カレントディレクトリ
	$curpath = pathinfo ( __FILE__ );	$curpath = $curpath['dirname'];

	// ダウンロードリスト
	$ls = exec ( "ls ".$curpath."/pdf_bat/*.zip" );


	if ( $ls ) {
		if ( is_file ( $ls ) ) {

			$filename = basename ( $ls );

			echo "現在ダウンロード可能な生成済みファイルは以下のとおりです。<br>\n";
			echo "<span style='color:#aaa;'><a href='./pdf_bat/".$filename."'>".$filename."</a>".
					"　サイズ：".round ( filesize ( $ls ) / 1024 ,2 )."KB".
					"　作成日時：".date ( "Y/m/d H:i:s" ,fileatime ( $ls ) )."</span>\n";
		} else {
			echo "現在ダウンロード可能なファイルはありません。\n";
		}

	} else {
		echo "現在ダウンロード可能なファイルはありません。\n";
	}

	// 
	$rs = mysqli_query ( $db, "SELECT pid FROM process" ) or die ( "db error" );
	if ( mysqli_num_rows ( $rs ) ) {
		$fld = mysqli_fetch_object ( $rs );
		$pid = $fld->pid;
		$buf = exec ( "ps -axp ".$pid." | grep ".$pid );
		if ( $buf ) {
			// MOD 2008/05/29 CSVダウンロード機能
			// echo "現在PDF生成中です。";
			// MOD START
			echo "現在PDF・CSV生成中です。";
			// MOD END
		} else {
			// プロセスなし（異常終了の疑い）
			mysqli_query ( $db,"TRUNCATE TABLE process" ) or die ( "db error" );
		}
	}
	mysqli_free_result ( $rs );

?>
		</p>

		<table cellspacing='1' cellpadding='5'>

<!--
		<form action='download_pdf.php' method='post' name='all'>
		<tr><th colspan='3'>一括</th><td><input type='submit' name='pdf_all' value='全PDF'>　<input type='submit' name='csv_all' value='全CSV'></td></tr>
		</form>
-->
		<tr><th colspan='3'>一括(集計済み)</th><td>
<?php
	// ADD 2008/06/05 CRON集計データダウンロード
	// カレントディレクトリ
	$curpath = pathinfo ( __FILE__ );	$curpath = $curpath['dirname'];

	// ダウンロードリスト
	$ls = exec ( "ls ".$curpath."/cron/pdf.zip" );
	if ( $ls && is_file ( $ls ) ) {
		$filename = basename ( $ls );
		print "<a href='./cron/".$filename."?".time()."'>全PDF</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>";
	}else{
		print "全PDF(作成中)　";
	}
	$ls = exec ( "ls ".$curpath."/cron/csv.zip" );
	if ( $ls && is_file ( $ls ) ) {
		$filename = basename ( $ls );
		print "<a href='./cron/".$filename."?".time()."'>全CSV</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>";
	}else{
		print "全CSV(作成中)　";
	}
	// ADD END
?>
		</td></tr>

		<form action='download.php' method='post' name='hos'>
		<input type='hidden' name='ftype' value=''>

		<tr><th rowspan='2'>病院単位</th><th>都道府県</th>
			<td><select name='pref1' onchange='redraw(this.form, 0)'><?php	getPrefectures($year, $_POST['pref1']);	?></select></td>
<?php

	// ボタン作成
	if ($_POST['hos1'] == "") {

		echo "<td rowspan='2'>".
				"<input type='submit' name='pdf_h' value='PDF'>　".
				"<input type='submit' name='pdf_ht' value='PDF(集計)'>　".
				"<input type='submit' name='csv_h' value='CSV'>　".
				"<input type='submit' name='csv_t' value='CSV(テキスト回答)'>　".
				"<input type='submit' name='recom_h' value='CSV(リコメンデーション)'>";

	} else {

		echo "<td rowspan='2'>".
				"<input type='button' name='pdf_h' value='PDF' onClick=\"down_check_pdf('hos', 'pdf_h')\">　".
				"<input type='button' name='pdf_ht' value='PDF(集計)' onClick=\"down_check_pdf('hos', 'pdf_ht')\">　".
				"<input type='button' name='csv_h' value='CSV' onClick=\"down_check_pdf('hos', 'csv_h')\">　".
				"<input type='submit' name='csv_t' value='CSV(テキスト回答)' onClick=\"down_check_pdf('hos', 'csv_t')\">　".
				"<input type='submit' name='recom_h' value='CSV(リコメンデーション)' onClick=\"down_check_pdf('hos', 'recom_h')\">";

	}

	// エラーメッセージ表示
	// MOD 2008/06/05 リコメンデーションでエラーが表示されない問題修正
	//if ($_POST['pdf_h'] != "" || $_POST['pdf_ht'] != "" || $_POST['csv_h'] != "" || $_POST['csv_t'] != "") {  
	// MOD START
	if ($_POST['pdf_h'] != "" || $_POST['pdf_ht'] != "" || $_POST['csv_h'] != "" || $_POST['csv_t'] != "" || $_POST['recom_h'] != "") {
	// MOD END
		if ($_POST['hos1'] == "") {
			echo DispErrMsg("<br><br>選択してください。");
		}
	}

?>
		</td></tr>
		<tr><th>病院No.</th><td>
			<select name=hos1 onchange='redraw(this.form, 3)'>
				<?php	getHospital($year, $_POST['pref1'], $_POST['hos1'])	?>
			</select>
		</td></tr>
		</form>

		<form action='download.php' method='post' name='ward'>

		<input type='hidden' name='ftype' value=''>
		<tr><th rowspan='3'>病棟単位</th><th>都道府県</th>
			<td><select name='pref2' onchange='redraw(this.form, 1)'><?php	getPrefectures($year, $_POST['pref2'])	?></select></td>
<?php

	// ボタンを作成
	if ($_POST['ward'] == "") {
		echo "<td rowspan='3'>".
				"<input type='submit' name='pdf_w' value='PDF'>　".
				"<input type='submit' name='pdf_w' value='PDF(集計)'>　".
				"<input type='submit' name='csv_w' value='CSV'>　".
				"<input type='submit' name='csv_wt' value='CSV(テキスト回答)'>　".
				"<input type='submit' name='recom_w' value='CSV(リコメンデーション)'>";
	} else {
		echo "<td rowspan='3'>".
				"<input type='button' name='pdf_w' value='PDF' onClick=\"down_check_pdf('ward', 'pdf_w')\">　".
				"<input type='button' name='pdf_wt' value='PDF(集計)' onClick=\"down_check_pdf('ward', 'pdf_wt')\">　".
				"<input type='button' name='csv_w' value='CSV' onClick=\"down_check_pdf('ward', 'csv_w')\">　".
				"<input type='submit' name='csv_wt' value='CSV(テキスト回答)' onClick=\"down_check_pdf('ward', 'csv_wt')\">　".
				"<input type='submit' name='recom_w' value='CSV(リコメンデーション)' onClick=\"down_check_pdf('ward', 'recom_w')\">";
	}

	// エラーメッセージ表示
	// MOD 2008/06/05 リコメンデーションでエラーが表示されない問題修正
	//if ($_POST['pdf_w'] != "" || $_POST['pdf_wt'] != "" || $_POST['csv_w'] != "") {  
	// MOD START
	if ($_POST['pdf_w'] != "" || $_POST['pdf_wt'] != "" || $_POST['csv_w'] != "" || $_POST['csv_wt'] != "" || $_POST['recom_w'] != "") {
	// MOD END
		if ($_POST['ward'] == "") {
			echo DispErrMsg("<br><br>選択してください。");
		}
	}

?>
		</td></tr>
		<tr><th>病院No.</th><td><select name=hos2 onchange='redraw(this.form, 2)'><?php	getHospital($year, $_POST['pref2'], $_POST['hos2'])	?></select></td></tr>
		<tr><th>病棟No.</th><td><select name=ward onchange='redraw(this.form, 3)'><?php	getWard($year, $_POST['pref2'], $_POST['hos2'], $_POST['ward'])	?></select>
		</td></tr>

		</form>

		<form action='download.php' method='post' name='IDSELECT'>
			<input type='hidden' name='PDF_ID' value=''>
			<tr><th>ID指定</th><th>ID</th>
				<td><input size='22' type='text' maxlength='17' name='uid' value='<?php echo $_POST['uid'] ?>' onKeyDown='if (window.event.keyCode==13) {return false;}'></td>
				<td><input type='submit' name='pdf_u' value='PDF'><?php	if ($err_str != "") echo DispErrMsg("<br><br>".$err_str);	?></td></tr>

		</form>

		</table>

	</td></tr>

	<tr><th>集計データダウンロード(集計済み)</th></tr>
	<tr><td>
<?php
	// ダウンロードリスト
	$ls = exec ( "ls ".$curpath."/cron/structure-ttl.csv" );
	if ( $ls && is_file ( $ls ) && filesize( $ls ) > 0 ) {
		$filename = basename ( $ls );
		print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>構造</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>";
	}else{
		print "構造(作成中)　";
	}
	$ls = exec ( "ls ".$curpath."/cron/process-ttl.csv" );
	if ( $ls && is_file ( $ls ) && filesize( $ls ) > 0 ) {
		$filename = basename ( $ls );
		print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>過程</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>";
	}else{
		print "過程(作成中)　";
	}
	$ls = exec ( "ls ".$curpath."/cron/outcome-ttl.csv" );
	if ( $ls && is_file ( $ls ) && filesize( $ls ) > 0 ) {
		$filename = basename ( $ls );
		print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>アウトカム</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>";
	}else{
		print "アウトカム(作成中)　";
	}
	$ls = exec ( "ls ".$curpath."/cron/ttl-avg.csv" );
	if ( $ls && is_file ( $ls ) && filesize( $ls ) > 0 ) {
		$filename = basename ( $ls );
		print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>総合</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>";
	}else{
		print "総合(作成中)　";
	}
?>
	</td></tr>

	<tr><th>アンケートデータダウンロード(集計済み)</th></tr>
	<tr><td>
<?php
	$ls = exec ( "ls ".$curpath."/cron/ttl-enq.csv" );
	if ( $ls && is_file ( $ls ) && filesize( $ls ) > 0 ) {
			$filename = basename ( $ls );
			print "<a href='./cron/".$filename."?".time()."' download='".$filename."'>総合</a>　作成日時：".date ( "Y/m/d H:i:s" ,filemtime ( $ls ) )."<br>※過程における質問・回答のテキスト回答データ";
	}else{
		print "総合(作成中)";
	}
?>
<!--
	</td></tr>
	<tr><th>集計データダウンロード</th></tr>
	<tr><td>
		<a href='totoal_csv.php?year=<?= $year ?>&type=1' onClick='return confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")'>構造</a>　
		<a href='totoal_csv.php?year=<?= $year ?>&type=2' onClick='return confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")'>過程</a>　
		<a href='totoal_csv.php?year=<?= $year ?>&type=3' onClick='return confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")'>アウトカム</a>　
		<a href='totoal_csv.php?year=<?= $year ?>&type=10' onClick='return confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")'>総合</a></a>
	</td></tr>
	<tr><th>アンケートデータダウンロード</th></tr>
	<tr><td>
		<a href='totoal_csv.php?year=<?= $year ?>&type=20' onClick='return confirm("この処理には時間がかかります。\n今朝の段階のデータでもいい場合は上記の集計済みファイルを選択してください。\nダウンロードしますか？\n")'>総合</a>※過程における質問・回答のテキスト回答データ</a>
	</td></tr>
-->
	</table>

<?php
// ID指定ダウンロード
if ($_POST['pdf_u'] != "" && $err_str == "") {
?>
	<script stype='text/javascript'><!--
	if (confirm("ダウンロードを開始します。")) {
		document.IDSELECT.action = './makedata.php';
		document.IDSELECT.target = '_self';
		document.IDSELECT.PDF_ID.value = '<?= $PDF_ID ?>';
		document.IDSELECT.submit();
	}
	//-->
	</script>
<?php
}
?>
</div>

</body>
</html>
