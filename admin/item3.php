
<?php
/*******************************************************************
item3.php
	小項目メンテナンス
									(C)2005,University of Hyougo.
*******************************************************************/

	require_once("setup.php");
	require_once("common.php");//共通関数を読み込み

	$db = Connection::connect();	// データベース接続

	$errstr = ''; // エラーメッセージ用変数を初期化
	$adderrstr = ''; // 追加時のエラーメッセージ用変数を初期化
	$newpoint = null; // $newpoint変数を事前に初期化
	$imgerrstr = '';

	$id = $_GET['id'] ?? $_POST['id'];
	$id1 = $_GET['id1'] ?? $_POST['id1'];
	$id2 = $_GET['id2'] ?? $_POST['id2'];


	// 公開状況を取得
	$public = getPublicationStatus($db);
	
	// 最大IDとNoを取得
	list($maxid, $maxno) = getMaxIdAndNo($db, 'item3', 'id3', ['id' => $id, 'id1' => $id1, 'id2' => $id2]);

	// IDリストを取得
	$array_id = getIdList($db, 'item3', 'id3', ['id' => $id, 'id1' => $id1, 'id2' => $id2]);
		

	/**************************************
		編集内容の評価　追加　削除　編集
	**************************************/
	if (isset($_POST['add'])) {	// 新規追加
		$_POST['newitem'] = StringUtilities::half2full($_POST['newitem']);	// 全て全角　未入力不可
		$newpoint = $_POST['newpoint'] ?? null; // $_POST['newpoint']が存在しない場合はnullを設定する
		if ($public == Config::CLOSE) {
				$newpoint = StringUtilities::str2int($newpoint);    // 数値だけ
				if ($newpoint === "") $adderrstr = "配点は0より大きな値をご入力ください。";    // 項目
		}
		if (!$adderrstr) {	// エラーがない場合は項目を追加する
			$maxid = $maxid + 1;	$maxno = $maxno + 1;	// 最大値+1で最大の値を生成
			$sql = "INSERT INTO item3(id,id1,id2,id3,name,point,no) VALUES(".$id.",".$id1.",".$id2.",".$maxid.",'".$_POST['newitem']."',".$_POST['newpoint'].",".$maxno.")";
			$res = mysqli_query($db, $sql);
		}

	} elseif (isset($_POST['edit'])) {	// 既存データ編集
		/*** 削除 ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			if (isset($_POST['del'.$array_id[$i]])) {	// 削除チェックが付いている
				// 画像削除
				ImageUtilities::deleteImg($id, $id1, $id2, $array_id[$i]);
				// 小項目削除
				$sql = "DELETE FROM item3 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$array_id[$i];
				$res = mysqli_query($db, $sql);
				// 質問削除
				$sql = "DELETE FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$array_id[$i];
				$res = mysqli_query($db, $sql);
				// 回答削除
				$sql = "DELETE FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$array_id[$i];
				$res = mysqli_query($db, $sql);
			}
		}

		/*** 更新チェック ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			// 全て全角　未入力不可
			$_POST['name'.$array_id[$i]] = StringUtilities::half2full($_POST['name'.$array_id[$i]]);
			if (!$_POST['name'.$array_id[$i]]) $errstr = "項目が未入力です。";	// 項目
			if ($public == Config::CLOSE) {	// 公開中は不要
				$_POST['point'.$array_id[$i]] = StringUtilities::str2int($_POST['point'.$array_id[$i]]);	// 数値だけ
				if ($_POST['point'.$array_id[$i]] === "") $errstr = "配点は0より大きな値をご入力ください。";
			}
		}

		/*** 更新 ***/
		if (!$errstr) {
			for ($i = 0;$i < sizeof($array_id);$i++) {
				if ($public == Config::CLOSE) {	// 公開中は配点の変更はできない
					$sql = "UPDATE item3 SET name='".$_POST['name'.$array_id[$i]]."',point = ".$_POST['point'.$array_id[$i]]." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$array_id[$i];
				} else {
					$sql = "UPDATE item3 SET name='".$_POST['name'.$array_id[$i]]."' WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$array_id[$i];
				}
				$res = mysqli_query($db, $sql);
			}
		}

		/*** 画像ファイル削除 ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			if (isset($_POST['imgdel'.$array_id[$i]])) {	// 画像削除チェックが付いている
				ImageUtilities::deleteImg($id, $id1, $id2, $array_id[$i]);
			}
		}

		/*** 画像ファイルチェック ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			$imgName = "img".$array_id[$i];
			if (is_uploaded_file($_FILES[$imgName]["tmp_name"])) {
				//画像サイズチェック
				if (filesize($_FILES[$imgName]["tmp_name"]) > 100000) {
					$imgerrstr = "画像サイズが大きすぎます。";
				}
				// 画像の拡張子チェック
				if (!(preg_match("/\.(jpg)$/i", $_FILES[$imgName]["name"])
					|| preg_match("/\.(jpeg)$/i", $_FILES[$imgName]["name"])
					|| preg_match("/\.(gif)$/i", $_FILES[$imgName]["name"])))
				{
					$imgerrstr = "jpg、gif、pngファイルをアップロードしてください。";
				}
			}
		}

		/*** 画像アップロード ***/
		if ($imgerrstr == "") {
			for ($i = 0;$i < sizeof($array_id);$i++) {
				$imgName = "img".$array_id[$i];
				if (is_uploaded_file($_FILES[$imgName]["tmp_name"])) {
					// 古い画像を削除
					$fileName = Config::IMGDIR.$id."_".$id1."_".$id2."_".$array_id[$i];
					if (file_exists($fileName.".jpg")) unlink($fileName.".jpg");
					if (file_exists($fileName.".gif")) unlink($fileName.".gif");
					// 画像を登録
					if (preg_match("/\.(jpg)$/i", $_FILES[$imgName]["name"]) || preg_match("/\.(jpeg)$/i", $_FILES[$imgName]["name"])) {	//jpg
						copy($_FILES[$imgName]['tmp_name'], $fileName.".jpg");
					} elseif (preg_match("/\.(gif)$/i", $_FILES[$imgName]["name"])) {	//gif
						copy($_FILES[$imgName]['tmp_name'], $fileName.".gif");
					} elseif (preg_match("/\.(png)$/i", $_FILES[$imgName]["name"])) {	//png
						copy($_FILES[$imgName]['tmp_name'], $fileName.".png");
					}
				}
			}
		}
	}

	/*** 並び替え ***/
	if (isset($_GET['up'])) {

		$id3 = $_GET['id3'];	$swpno = $_GET['up'];

		$sql = "UPDATE item3 SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND no=".($swpno - 1);
		$res = mysqli_query($db, $sql);

		$sql = "UPDATE item3 SET no=".($swpno - 1)." WHERE id=".$id." AND id1 = ".$id1." AND id2=".$id2." AND id3=".$id3;
		$res = mysqli_query($db, $sql);

	} elseif (isset($_GET['dwn'])) {

		$id3 = $_GET['id3'];	$swpno = $_GET['dwn'];

		$sql = "UPDATE item3 SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND no=".($swpno + 1);
		$res = mysqli_query($db, $sql);

		$sql = "UPDATE item3 SET no=".($swpno + 1)." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3;
		$res = mysqli_query($db, $sql);

	}

// item3の編集後のno整合性維持
$actionRequired = $_POST['add'] ?? $_POST['edit'] ?? false;
if ($actionRequired) {
		maintainOrderConsistency($db, 'item3', ['id' => $id, 'id1' => $id1, 'id2' => $id2]);
}

	/*** 各項目称取得 ***/
	$sql = 	"SELECT (category.category)AS category,(item1.name)AS item1_name,(item2.name)AS item2_name,".
			"item3.id3,(item3.name)AS item3_name,item3.point,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no ".
			"FROM ((category LEFT JOIN item1 ON category.id = item1.id) LEFT JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
			"LEFT JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
			"WHERE category.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." ORDER BY item3.no";

	$res = mysqli_query($db, $sql);
	$fld = mysqli_fetch_object ( $res );
	$category = $fld->category;
	$item1_no = $fld->item1_no;	$item1_name = $fld->item1_name;
	$item2_no = $fld->item2_no;	$item2_name = $fld->item2_name;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" type="text/css" href="./admin.css" media="all">
<title>小項目編集</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<form method='POST' ENCTYPE='multipart/form-data' action='<?=$_SERVER['PHP_SELF']?>'>

	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?=$id?>'>大項目編集</a> ≫ <a href='item2.php?id=<?=$id?>&id1=<?=$id1?>'>中項目編集</a> ≫ 小項目編集</th></tr>
	<tr><td><?= $category ?></td></tr>
	<tr><th><?= $item1_no ?>. <?= $item1_name ?></th></tr>
	<tr><th><?= $item1_no ?>. <?= $item2_no ?>. <?= $item2_name ?></th></tr>
	<tr><td>
<?php
	if ($adderrstr) {
		echo ErrorHandling::DispErrMsg("登録できませんでした。<br>".$adderrstr)."<br>";
	}
	if ($errstr) {
		echo ErrorHandling::DispErrMsg("編集できませんでした。<br>".$errstr)."<br>";
	}
	if ($imgerrstr) {
		echo ErrorHandling::DispErrMsg("編集できませんでした。<br>".$imgerrstr)."<br>";
	}
?>
<?php if ($public == Config::CLOSE) {	// 公開中は新規登録できない?>
	<p>新規登録</p>
	<table cellspacing='1' cellpadding='5'>
	<tr><th>項目</th><th>配点</th><th>登録</th></tr>
	<tr><td><input size='60' type='text' name='newitem' value='<?=(($adderrstr) ? $_POST['newitem'] : '')?>'></td>
		<td><input size='4' type='text' maxlength='4' name='newpoint' value='<?=(($newpoint) ? $_POST['newpoint'] : '')?>'></td>
		<td><input type='submit' name='add' value='　登　録　'></td></tr>
	</table>
<?php
	}
	/**** 小項目を全て列挙する ***/
		$sql = 	"SELECT (category.category)AS category,(item1.name)AS item1_name,(item2.name)AS item2_name,".
				"item3.id3,(item3.name)AS item3_name,item3.point,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no ".
				"FROM ((category INNER JOIN item1 ON category.id = item1.id) INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
				"WHERE category.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." ORDER BY item3.no";
		$res = mysqli_query($db, $sql);
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			echo "<p>登録内容</p>\n";
			echo "<table cellspacing='1' cellpadding='5'>";
			echo "<tr><th>No.</th><th>項目</th><th>配点</th><th>質問</th>".(($public == Config::CLOSE) ? "<th>並べ替え</th><th>削除</th>" : "")."<th>画像登録</th><th>登録</th><th>画像削除</th></tr>\n";
			do {
				echo "<tr><td align='right'>".$fld->item1_no.".".$fld->item2_no.".".$fld->item3_no."</td>";
				echo "<td><input size='60' type='text' name='name".$fld->id3."' value='".$fld->item3_name."'></td><td align='right'>";
				if ($public == Config::CLOSE) {		// 公開中は配点の変更ができない
					echo "<input size='4' type='text' maxlength='4' name='point".$fld->id3."' value='".$fld->point."'>";
				} else {
					echo $fld->point;
				}
				echo "</td>";
				echo "<td><a href='item4.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$fld->id3."'>質問</a></td>";
				if ($public == Config::CLOSE) {
					echo "<td>";
					if ($fld->item3_no != 1) echo "<a href='item3.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$fld->id3."&up=".$fld->item3_no."'>▲</a>　";
					if ($fld->item3_no != $maxno) echo "<a href='item3.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$fld->id3."&dwn=".$fld->item3_no."'>▼</a>";
					echo "</td><td><input type=\"checkbox\" name='del".$fld->id3."'></td>";
				}
				echo "<td><input type='file' name='img".$fld->id3."'></td>";
				
				$fileName = ImageUtilities::getFileName($id, $id1, $id2, $fld->id3);
				if ($fileName) {
					echo "<td><a href='".$fileName."' target='_blank'>あり</a></td>	";
					echo "<td><input type='checkbox' name='imgdel".$fld->id3."'></td>";
				} else {
					echo "<td>なし</td>";
					echo "<td></td>";
				}
				echo "</tr>\n";
			} while ( $fld = mysqli_fetch_object ( $res ) );
			echo "</table>\n";

			echo "<div align='right' style='margin:5px;'><input type='reset' name='reset' value='リセット'>　　<input type='submit' name='edit' value='　編　集　'></div>\n";

		}

?>
	</td></tr>
	</table>

	<input type='hidden' name='id' value='<?=$id?>'>
	<input type='hidden' name='id1' value='<?=$id1?>'>
	<input type='hidden' name='id2' value='<?=$id2?>'>

	</form>

</div>

</body>
</html>
