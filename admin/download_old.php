<?php
/*******************************************************************
	ファイル名：download.php
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
function getPrefectures($year, $pref)
{
    // データベース接続
    $db = Connection::connect();

    // クエリの準備と実行
    $sql = "SELECT DISTINCT SUBSTRING(uid FROM 4 FOR 2) AS pid FROM usr WHERE uid LIKE ? AND comp='1' ORDER BY pid ASC";
    $stmt = mysqli_prepare($db, $sql);
    
    $yearParam = $year . '-%';
    mysqli_stmt_bind_param($stmt, 's', $yearParam);

    if (!mysqli_stmt_execute($stmt)) {
        printf("<option value=''>クエリ実行エラー</option>\n");
        return;
    }

    $res = mysqli_stmt_get_result($stmt);

    // 結果の取得
    $prefs = array();
    while ($row = mysqli_fetch_object($res)) {
        $prefs[] = $row->pid;
    }

    // オプションの生成
    printf("<option value='' style='color: #000; background-color: #fff;'>--選択して下さい--</option>\n");
    foreach ($prefs as $pid) {
        $sel = ($pref == (int)$pid) ? "selected" : "";
        printf("<option value='%02d' %s style='color: #000; background-color: #fff;'>%s</option>\n", $pid, $sel, Config::$prefName[(int)$pid]);
    }

    // ステートメントのクローズ
    mysqli_stmt_close($stmt);
    Connection::disconnect($db);
}


/*******************************************************************
getHospital
	概要：病院 <option> の生成
	引数：$year		選択されている年度
		：$pref		選択されている都道府県
		：$hosp		選択されている病院
	戻値：なし
*******************************************************************/
function getHospital($year, $pref, $hosp)
{
	printf("<option value=''>--選択して下さい--</option>\n");
	if (!$year) { return ; }	// 既存データなし
	if (!$pref) { return ; }	// 既存データなし
	$sql = sprintf("SELECT DISTINCT SUBSTRING(uid FROM 7 FOR 4) AS id FROM usr WHERE uid LIKE '%s-%s%%' AND comp='1' ORDER BY 1 ASC" , $year, $pref);
	$db = Connection::connect();
	$res = mysqli_query ( $db ,$sql );
	if ( mysqli_num_rows ( $res ) < 1 ) {  return ; }	// 既存データなし
	for ($i=0;$row=mysqli_fetch_object ( $res );$i++) {
		$sel = ($hosp == $row->id) ? "selected" : "";
		printf("\t<option value='%s' %s>%s</option>\n", $row->id, $sel, $row->id);
	}
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
function getWard($year, $pref, $hosp, $ward)
{
	printf("<option value=''>--選択して下さい--\n");
	if (!$year) { return ; }	// 既存データなし
	if (!$pref) { return ; }	// 既存データなし
	if (!$hosp) { return ; }	// 既存データなし
	$sql = sprintf("SELECT DISTINCT SUBSTRING(uid FROM 12 FOR 2) AS id FROM usr WHERE uid LIKE '%s-%s-%s%%' AND comp='1' ORDER BY 1 ASC" , $year, $pref, $hosp);
	$db = Connection::connect();
	$res = mysqli_query ( $db ,$sql );
	if ( mysqli_num_rows ( $res ) < 1 ) {  return ; }	// 既存データなし
	for ($i=0;$row=mysqli_fetch_object ( $res );$i++) {
		$sel = ($ward == $row->id) ? "selected" : "";
		printf("\t<option value='%s' %s>%s</option>\n", $row->id, $sel, $row->id);
	}
	
}

/*******************************************************************
check_uid
	概要：uid の形式が正しいかチェックする
	引数：$uid		ユーザID文字列
	戻値：$uid		正常終了
		：NULL		エラー発生時
*******************************************************************/
function check_uid($uid)
{
	// 正規表現で一発チェック
	if (!preg_match ("([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})", $uid)) {
		return NULL;
	}

	// yy-pp-hhhh-ww-ttt
	// 文字数が 17 以外はありえないのでエラー
	if (strlen($uid) != 17)   return NULL;

	// - で分割して 5 で無いならエラー
	$tmp = explode ("-", $uid);
	if (count($tmp) != 5)     return NULL;

	// 各区切り値のサイズが異なるならエラー
	if (strlen($tmp[0]) != 2) return NULL;
	if (strlen($tmp[1]) != 2) return NULL;
	if (strlen($tmp[2]) != 4) return NULL;
	if (strlen($tmp[3]) != 2) return NULL;
	if (strlen($tmp[4]) != 3) return NULL;

	// 都道府県チェック
	$pref = (int)$tmp[1];
	if ($pref < 1 || $pref > 47) {
		return NULL;
	}

	return $uid;

}
/*******************************************************************
getYear
	概要：現在の年度を取得します。
	引数：$db   データベースオブジェクト
	戻値：年度の配列
*******************************************************************/
function getYear()
{

	global $db;
	$sql = "SELECT year FROM year";
	$res = mysqli_query ( $db ,$sql );
	$fld = mysqli_fetch_object ( $res );
	$year = $fld->year;
	return $year;
}

$db = Connection::connect();

// 年度
$year = getYear();

// ID指定ダウンロード時///////////////////////////////////この辺の「このキーが存在するかどうかを確認する必要があります。PHPのisset()関数」はエラー用でなくなる可能性あるからおいおいなくす
if (isset($_POST['pdf_u']) && $_POST['pdf_u'] != "") {
	$id = $_POST['uid'];
	if ($id == "") {
		$err_str = "IDを入力してください。";
	} else if (check_uid($id) == NULL) {
		$err_str = "入力したIDの形式に誤りがあります。";
	} else {
		$sql = "SELECT comp FROM usr WHERE uid='".$id."'";
		$res = mysqli_query ( $db ,$sql );

		if ( !mysqli_num_rows ( $res ) ) {
			$err_str = "指定したユーザは存在していません。";
		} else {
			$fld = mysqli_fetch_object ( $res );
			if ( $fld->comp != "1" ) {
				$err_str = "指定したユーザは未回答です。";
			} else {
				$PDF_ID = $id;
			}
		}
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
	// 全てのファイルを再帰的に取得する関数
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

	// pdf_bat と csv_bat ディレクトリ内の全てのファイルを取得
	$pdfFiles = getAllFiles($curpath."/pdf_bat");
	$csvFiles = getAllFiles($curpath."/csv_bat");

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
			echo ErrorHandling::DispErrMsg("<br><br>選択してください。");
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
			echo ErrorHandling::DispErrMsg("<br><br>選択してください。");
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
				<td><input type='submit' name='pdf_u' value='PDF'><?php	if ($err_str != "") echo ErrorHandling::DispErrMsg("<br><br>".$err_str);	?></td></tr>

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
