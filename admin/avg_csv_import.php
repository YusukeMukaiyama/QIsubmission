<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>平均点CSVデータインポート</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ 平均点CSVデータインポート</th></tr>
	<tr><td>
<?php

	require_once("setup.php");

/*
-- 平均点
CREATE TABLE dat_avg (
    id          INT(11)     NOT NULL,
    id1         INT(11)     NOT NULL,
    avg         FLOAT       NOT NULL,
    PRIMARY KEY ( id,id1 )
);
*/


/*-------------------------------------------------------------------------------------------------
buffer_flush
	概要：バッファの強制フラッシュ
	引数：なし
	戻値：なし
-------------------------------------------------------------------------------------------------*/
function buffer_flush()
{
	flush();
	ob_flush();
}


/*-------------------------------------------------------------------------------------------------
import_csv
	概要：データインポート
	引数：なし
	戻値：なし
-------------------------------------------------------------------------------------------------*/
function import_csv()
{

	global $db;

	$hFile = fopen ( $_FILES['userfile']['tmp_name'] ,"r" ) or die ( "ERROR FILE:".__FILE__." LINE:".__LINE__ );

		// ヘッダラインを処理
		$data = fgetcsv ( $hFile ,256 ,"," );

		if ( sizeof ( $data ) != 3 ) die ( "データの形式が不正です。" );

		// テーブル初期化
		$sql = "TRUNCATE TABLE dat_avg";
		//mysqli_unbuffered_query ( $db ,$sql ) or die ( "ERROR FILE:".__FILE__." LINE:".__LINE__ );
		$result = mysqli_query($db, $sql, MYSQLI_USE_RESULT) or die("ERROR FILE:".__FILE__." LINE:".__LINE__);
		// 非バッファリングクエリの場合、結果セットを処理した後には明示的に解放する必要があります。
		//mysqli_free_result($result);

		// データインポート
		$cnt = 0;
		while ( ( $data = fgetcsv ( $hFile ,256 ,"," ) ) !== FALSE ) {
			$sql = "INSERT INTO dat_avg(id,id1,avg) VALUES(".$data[0].",".$data[1].",".$data[2].")";
			//mysqli_unbuffered_query ( $db ,$sql ) or die ( "ERROR FILE:".__FILE__." LINE:".__LINE__ );
			mysqli_query($db, $sql, MYSQLI_USE_RESULT) or die("ERROR FILE:".__FILE__." LINE:".__LINE__);
			$cnt++;
			echo $data[0]."-".$data[1]." データをインポートしました。<br>\n";
			buffer_flush();
		}

	fclose ( $hFile );

	// 読み込み件数表示
	echo $cnt."件のデータを読み込みました。";

}


	$db = Connection::connect();	// データベース接続

	////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//以下も無理やりエラーなくしてるif ( is_uploaded_file ( $_FILES['userfile']['tmp_name'] ) ) {

	if (isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {


		echo "処理が完了するまで、そのままお待ちください。<br>";
		echo "※）ブラウザのリロードを行うとデータが重複登録されますので決して行わないでください。<br>";

		echo $_FILES['userfile']['name']."がアップロードされました。<br>";
		buffer_flush();

		echo "インポート処理を行っています。<br>";
		buffer_flush();

		import_csv();

	} else {

?>
	インポートできるデータの形式は以下の通りです（1行目のタイトル行も必要です）。<br>
	<p style='margin : 2px;padding : 5px;background : #dddddd;'>
	ID(構造:1/過程:2/アウトカム:3),ID1(大項目NO.),AVG(平均点)<br>
	ex)<br>
	ID,ID1,AVG<br>
	1,1,6.02<br>
	1,2,8.27<br>
	1,3,8.23<br>
	1,4,18.73<br>
	1,5,17.08<br>
	1,6,13.02<br>
	2,1,6.02<br>
	2,2,8.27<br>
	:<br>
	</p>
	<form method='POST' action='./avg_csv_import.php' enctype='multipart/form-data'>
		<input type='hidden' name='MAX_FILE_SIZE' value='10485760'>
		<input type='file' name='userfile'>　<input type='submit' value='アップロード'>
	</form>
<?php

	}


?>
	</td></tr>
	</table>

</div>

</body>
</html>
