<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>ダウンロード</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ <a href='download.php'>ダウンロード</a> ≫ PDF・CSV生成</th></tr>
	<tr><td>
<?php

	require_once ( "setup.php" );
	

	$db = Connection::connect();

	// $_REQUEST['pdf_all'] が存在するかどうかをチェック
	if (isset($_REQUEST['pdf_all'])) {
		$arg[0] = "pdf_all";
	}

	// $_REQUEST['csv_all'] が存在するかどうかをチェック
	if (isset($_REQUEST['csv_all'])) {
		$arg[0] = "csv_all";
		$mode = "CSV";
	}

	// $_REQUEST['make'] が存在するかどうかをチェック
	if (isset($_REQUEST['make'])) {
		$mode = $_REQUEST['make'];
	}

	// $_REQUEST['ftype'] の存在をチェック
	if (isset($_REQUEST['ftype'])) {
		$arg[0] = $_REQUEST['ftype'];

		// 特定のファイルタイプに応じた追加の処理
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
						$arg[3] = "";  // ここに必要な処理があれば追加
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
						die("無効なファイルタイプが指定されました。");
		}
	} else {
		die("ファイルタイプが指定されていません。");
	}


	//---------------------------------------------------------------------------------------------
	// リクエストからコマンドラインで使用する引数に割り当て

	// ADD 2008/05/27
	$mode = "PDF";
	// ADD END
	if ( $_REQUEST['pdf_all'] ) {	// 一括

		$arg[0] = "pdf_all";

		// ADD 2008/05/26 全CSV ダウンロード追加 + 進行状況表示
	} elseif ( $_REQUEST['csv_all'] ) {	// 一括
		$arg[0] = "csv_all";
		$mode = "CSV";
	} elseif ($_REQUEST['make']) {
		$mode = $_REQUEST['make'];
		// ADD END
	} else {

		if ($_REQUEST['ftype'] == 'csv_h' || $_REQUEST['ftype'] == 'csv_t' ||
			 $_REQUEST['ftype'] == 'recom_h' || $_REQUEST['ftype'] == 'csv_w' ||
			 $_REQUEST['ftype'] == 'csv_wt' || $_REQUEST['ftype'] == 'recom_w' ) {
			$mode = "CSV";
		}

		$arg[0] = $_REQUEST['ftype'];

		if ( $_REQUEST['ftype'] == 'pdf_h' || $_REQUEST['ftype'] == 'pdf_ht' ||
			 $_REQUEST['ftype'] == 'csv_h' || $_REQUEST['ftype'] == 'csv_t' ||
			 $_REQUEST['ftype'] == 'recom_h') {
			$arg[1] = $_REQUEST['pref1'];
			$arg[2] = $_REQUEST['hos1'];
			$arg[3] = "";

		} elseif ( $_REQUEST['ftype'] == 'pdf_w' || $_REQUEST['ftype'] == 'pdf_wt' ||
			 $_REQUEST['ftype'] == 'csv_w' || $_REQUEST['ftype'] == 'csv_wt' ||
			 $_REQUEST['ftype'] == 'recom_w' ) {
			$arg[1] = $_REQUEST['pref2'];
			$arg[2] = $_REQUEST['hos2'];
			$arg[3] = $_REQUEST['ward'];

		} else {

			die ( "エラー" );
		}

	}


	//---------------------------------------------------------------------------------------------
	// 登録されているプロセスを調べる
	$rs = mysqli_query ($db , "SELECT pid FROM process" ) or die ( "db error" );
	if ( mysqli_num_rows ( $rs ) ) {
		if ( $fld = mysqli_fetch_object ( $rs ) ) {
			$pid = $fld->pid;
			$buf = exec ( "ps -axp ".$pid." | grep ".$pid );
		}
	}
	mysqli_free_result ( $rs );

	// 処理中の場合は生成できない
	if ( $buf ) {

		// MOD 2008/05/26
		//$msg = "<p>現在PDF生成中です。</p>";
		// MOD START
		$file = @file_get_contents("./status/status");
		if($file == ""){
			$file = "<p>現在".$mode."生成中です</p>しばらくこのままでお待ちください";
		}
		$msg = "<p>$file</p>";
		$msg .= "<SCRIPT TYPE=\"text/javascript\">\n".
						"<!--\n".
						"var timer = \"3000\";			//指定ミリ秒単位\n".
						"function ReloadAddr(){\n".
						"	window.location.href=\"download_pdf.php?make=$mode\";	//ページをリロード\n".
						"}\n".
						"setTimeout(ReloadAddr, timer);\n".
						"//-->\n".
						"</SCRIPT>\n";
	}elseif ($_REQUEST['make']) {
		// カレントディレクトリ
		$curpath = pathinfo ( __FILE__ );	$curpath = $curpath['dirname'];
		$ls = exec ( "ls ".$curpath."/dl" );
		if ( $ls ) {
			if ( is_file ( $curpath."/dl/".$ls ) ) {
				$msg =  "生成が完了しました。<br><span style='color:#aaa;'><a href='./dl/".$ls."' download='".$ls."'>".$ls."</a>".
								"　サイズ：".round ( filesize ( "./dl/".$ls ) / 1024 ,2 )."KB".
								"　作成日時：".date ( "Y/m/d H:i:s" ,fileatime ( "./dl/".$ls ) )."</span>\n";
			}else{
				$msg =  "現在ダウンロード可能なファイルはありません。\n";
			}
		}else{
			$msg =  "現在ダウンロード可能なファイルはありません。\n";
		}
		// MOD END

	} else {

		// カレントディレクトリ
		$curpath = pathinfo ( __FILE__ );	$curpath = $curpath['dirname'];

		if ( $_REQUEST['gen'] ) {

			// コマンドの作成
			// MOD 2008/05/26 全CSVダウンロード追加
			//if ( $_REQUEST['pdf_all'] ) {	// 一括
			// MOD START
			if ( $_REQUEST['pdf_all'] || $_REQUEST['csv_all'] ) {	// 一括
			// MOD END

				$cli = "/usr/local/bin/php -f ".$curpath."/makedata_cli.php ".$arg[0]." > /dev/null &";

			} else {

				$cli = "/usr/local/bin/php -f ".$curpath."/makedata_cli.php ".$arg[0]." ".$arg[1]." ".$arg[2].( $arg[3] ? " ".$arg[3] : "" )." > /dev/null &";

			}

			// PDF生成の開始
			exec ( $cli );
			// MOD 2008/05/27
			// $msg = "<p>生成を開始しました。</p>";
			// MOD START
			$msg = "<p>".$mode."生成を開始しました。</p>しばらくこのままでお待ちください\n";
			$msg .= "<SCRIPT TYPE=\"text/javascript\">\n".
							"<!--\n".
							"var timer = \"3000\";			//指定ミリ秒単位\n".
							"function ReloadAddr(){\n".
							"	window.location.href=\"download_pdf.php?make=$mode\";	//ページをリロード\n".
							"}\n".
							"setTimeout(ReloadAddr, timer);\n".
							"//-->\n".
							"</SCRIPT>\n";
			// MOD END

		} else {

			// ダウンロードリスト
			echo "<p style='margin : 2px;padding : 5px;background : #dddddd;'>\n";
			$ls = exec ( "ls ".$curpath."/dl" );
			if ( $ls ) {
				if ( is_file ( $curpath."/dl/".$ls ) ) {
					echo "現在ダウンロード可能な生成済みファイルは以下のとおりです。<br>\n";
					echo "<span style='color:#aaa;'><a href='./dl/".$ls."' download='".$ls."'>".$ls."</a>".
							"　サイズ：".round ( filesize ( "./dl/".$ls ) / 1024 ,2 )."KB".
							"　作成日時：".date ( "Y/m/d H:i:s" ,fileatime ( "./dl/".$ls ) )."</span>\n";
				} else {
					echo "現在ダウンロード可能なファイルはありません。\n";
				}

			} else {
				echo "現在ダウンロード可能なファイルはありません。\n";
			}
			echo "</p>";

			// 生成開始ボタン表示
?>

		<p><?= $mode ?>を作成するには下記のボタンをクリックしてください。※作成済みファイルは上書きされます。</p>

		<form action='./download_pdf.php' method='post'>


			<input type='hidden' name='pdf_all' value='<?= $_REQUEST['pdf_all'] ?>'>
			<input type='hidden' name='csv_all' value='<?= $_REQUEST['csv_all'] ?>'>

			<input type='hidden' name='ftype' value='<?= $_REQUEST['ftype'] ?>'>

			<input type='hidden' name='pref1' value='<?= $_REQUEST['pref1'] ?>'>
			<input type='hidden' name='pref2' value='<?= $_REQUEST['pref2'] ?>'>

			<input type='hidden' name='hos1' value='<?= $_REQUEST['hos1'] ?>'>
			<input type='hidden' name='hos2' value='<?= $_REQUEST['hos2'] ?>'>

			<input type='hidden' name='ward' value='<?= $_REQUEST['ward'] ?>'>

			<div style='margin:10px;'><input type='submit' name='gen' value='≫<?= $mode ?>作成開始'></div>

		</form>

<?php

		}

	}

	echo $msg;

?>
	</td></tr>
	</table>

</div>

</body>
</html>