<?php
/*******************************************************************
item2.php
--中項目メンテナンス
*******************************************************************/
require_once("setup.php");//設定を読み込み
require_once("common.php");//共通関数を読み込み

$db = Connection::connect();	// データベース接続

$errstr = ''; // エラーメッセージ用変数を初期化
$adderrstr = ''; // 追加時のエラーメッセージ用変数を初期化	

$id = $_GET['id'] ?? $_POST['id'];
$id1 = $_GET['id1'] ?? $_POST['id1'];


// 新規登録のデータ準備
$adderrstr = $errstr = '';
$newitem = $_POST['newitem'] ?? '';
$newpoint = $_POST['newpoint'] ?? '';
$up_recommendation = $_POST['up_recommendation'] ?? '';
$recommendation = $_POST['recommendation'] ?? '';

// データの取得
$items = fetchItems($db, $id, $id1);
$category = isset($items[0]) ? $items[0]['category'] : null;  // カテゴリの取得

// 公開状況を取得
$public = getPublicationStatus($db);

// 最大IDとNoを取得
list($maxid, $maxno) = getMaxIdAndNo($db, 'item2', 'id2', ['id' => $id, 'id1' => $id1]);

// IDリストを取得
$array_id = getIdList($db, 'item2', 'id2', ['id' => $id, 'id1' => $id1]);

/**************************************
	編集内容の評価　追加　削除　編集
**************************************/
if (isset($_POST['add'])) {	// 新規追加

	$_POST['newitem'] = StringUtilities::half2full($_POST['newitem']);	// 全て全角　未入力不可
	if (!$_POST['newitem']) $adderrstr = "項目が未入力です。";	// 項目
	if ($public == Config::CLOSE) {
		$_POST['newpoint'] = StringUtilities::str2int($_POST['newpoint']);	// 数値だけ
		if ($_POST['newpoint']==="") $adderrstr = "配点は0より大きな値をご入力ください。";	// 項目
	}
	if (!is_numeric($_POST['recommendation'])) $errstr = "基準点は0より大きな値をご入力ください。";
	if (!is_numeric($_POST['up_recommendation'])) $errstr = "基準点は0より大きな値をご入力ください。";
	if (!$adderrstr) {	// エラーがない場合は項目を追加する
		$maxid = $maxid + 1;	$maxno = $maxno + 1;	// 最大値+1で最大の値を生成
		$stmt = mysqli_prepare($db, "INSERT INTO item2(id, id1, id2, name, point, no, recommendation, up_recommendation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, "iiisiiii", $id, $id1, $maxid, $_POST['newitem'], $_POST['newpoint'], $maxno, $_POST['recommendation'], $_POST['up_recommendation']);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);

	}

} elseif (isset($_POST['edit'])) {	// 既存データ編集
	/*** 削除 ***/
	for ($i = 0;$i < sizeof($array_id);$i++) {
		if (isset($_POST['del'.$array_id[$i]])) {	// 削除チェックが付いている
			// 中項目削除
			$sql = "DELETE FROM item2 WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			$res = mysqli_query($db, $sql);

			// 登録画像を削除
			$sql = "SELECT id3 FROM item3 WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			$res = mysqli_query($db, $sql);
			while ( $row = mysqli_fetch_object ( $res ) ) {	// 画像削除
				ImageUtilities::deleteImg($id, $id1, $array_id[$i], $row->id3);
			}

			// 小項目削除
			$sql = "DELETE FROM item3 WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			$res = mysqli_query($db, $sql);

			// 質問の削除
			$sql = "DELETE FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			$res = mysqli_query($db, $sql);

			// 回答削除
			$sql = "DELETE FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			$res = mysqli_query($db, $sql);

		}

	}


	/*** 更新チェック ***/
	for ($i = 0;$i < sizeof($array_id);$i++) {
		// 全て全角　未入力不可
		$_POST['name'.$array_id[$i]] = StringUtilities::half2full($_POST['name'.$array_id[$i]]);
		if (!$_POST['name'.$array_id[$i]]) $errstr = "項目が未入力です。";	// 項目

		// 数値
		if (!is_numeric($_POST['recommendation'.$array_id[$i]])) $errstr = "低得点基準は0より大きな値をご入力ください。";
		if (!is_numeric($_POST['up_recommendation'.$array_id[$i]])) $errstr = "高得点基準は0より大きな値をご入力ください。";

		if ($public == Config::CLOSE) {	// 公開中は不要
			$_POST['point'.$array_id[$i]] = StringUtilities::str2int($_POST['point'.$array_id[$i]]);	// 数値だけ
			if ($_POST['point'.$array_id[$i]]==="") $errstr = "配点は0より大きな値をご入力ください。";
		}
	}

	/*** 更新 ***/
	if (!$errstr) {
		for ($i = 0;$i < sizeof($array_id);$i++) {
			if ($public == Config::CLOSE) {	// 公開中は配点の変更はできない
				$sql = "UPDATE item2 SET name='".$_POST['name'.$array_id[$i]]."',".
					"point=".$_POST['point'.$array_id[$i]].",recommendation=".$_POST['recommendation'.$array_id[$i]].",".
					"up_recommendation=".$_POST['up_recommendation'.$array_id[$i]].
					" WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			} else {
				$sql = "UPDATE item2 SET name='".$_POST['name'.$array_id[$i]]."',".
					"recommendation=".$_POST['recommendation'.$array_id[$i]].
					"up_recommendation=".$_POST['up_recommendation'.$array_id[$i]].
					" WHERE id=".$id." AND id1=".$id1." AND id2=".$array_id[$i];
			}
			$res = mysqli_query($db, $sql);
		}
	}

}

/*** 並び替え ***/
if (isset($_GET['up'])) {

	$id2 = $_GET['id2'];	$swpno = $_GET['up'];
	$sql = "UPDATE item2 SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND no=".($swpno - 1);
	$res = mysqli_query($db, $sql);

	$sql = "UPDATE item2 SET no=".($swpno - 1)." WHERE id = ".$id." AND id1=".$id1." AND id2=".$id2;
	$res = mysqli_query($db, $sql);

} elseif (isset($_GET['dwn'])) {

	$id2 = $_GET['id2'];	$swpno = $_GET['dwn'];
	$sql = "UPDATE item2 SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND no=".($swpno + 1);
	$res = mysqli_query($db, $sql);

	$sql = "UPDATE item2 SET no=".($swpno + 1)." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2;
	$res = mysqli_query($db, $sql);

}

// item2の編集後のno整合性維持
$actionRequired = $_POST['add'] ?? $_POST['edit'] ?? false;
if ($actionRequired) {
		maintainOrderConsistency($db, 'item3', ['id' => $id, 'id1' => $id1]);
}


	/*** 各項目称取得 ***/
	$sql = 	"SELECT (category.category)AS category,(item1.name)AS item1_name,".
			"(item2.id2)AS id2,(item2.name)AS item2_name,item2.point,(item1.no)AS item1_no,(item2.no)AS item2_no ".
			"FROM (category LEFT JOIN item1 ON category.id = item1.id) LEFT JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1) ".
			"WHERE category.id=".$id." AND item1.id1=".$id1." ORDER BY item2.no";

	$res = mysqli_query($db, $sql);
	$fld = mysqli_fetch_object ( $res );

	$category = $fld->category;
	$item1_no = $fld->item1_no;	$item1_name = $fld->item1_name;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html;charset=Shift_JIS">
<meta http-equiv="Content-Style-Type" content="text/css">
<link rel="stylesheet" type="text/css" href="./admin.css" media="all">
<title>中項目編集</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<form method='POST' action='<?=$_SERVER['PHP_SELF']?>'>
	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?=$id?>'>大項目編集</a> ≫ 中項目編集</th></tr>
	<tr><td><?= $category ?></td></tr>
	<tr><th><?= $item1_no ?>. <?= $item1_name ?></th></tr>
	<tr><td>
<?php
	if ($adderrstr) echo ErrorHandling::DispErrMsg("登録できませんでした。<br>".$adderrstr)."<br>";
	if ($errstr) echo ErrorHandling::DispErrMsg("編集できませんでした。<br>".$errstr)."<br>";
?>
<?php
	if ($public == Config::CLOSE) {	// 公開中は新規登録できない
?>

	<p>新規登録</p>
	<table cellspacing='1' cellpadding='5'>
	<tr><th>項目</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>登録</th></tr>
	<tr><td><input size='90' type='text' name='newitem' value='<?=(($adderrstr) ? $_POST['newitem'] : '')?>'></td>
		<td><input size='4' type='text' maxlength='4' name='newpoint' value='<?=(($newpoint) ? $_POST['newpoint'] : '')?>'></td>
		<td><input size='4' type='text' maxlength='4' name='up_recommendation' value='<?=(($up_recommendation) ? $_POST['up_recommendation'] : '')?>'></td>
		<td><input size='4' type='text' maxlength='4' name='recommendation' value='<?=(($recommendation) ? $_POST['recommendation'] : '')?>'></td>
		<td><input type='submit' name='add' value='　登　録　'></td></tr>
	</table>

<?php
	}
/***************************************************************************************************************************/
?>
<?php
	/**** 中項目を全て列挙する ***/
		$sql = 	"SELECT (category.category)AS category,(item1.name)AS item1_name,".
				"(item2.id2)AS id2,(item2.name)AS item2_name,item2.point,(item1.no)AS item1_no,".
				"(item2.no)AS item2_no,item2.recommendation,item2.up_recommendation ".
				"FROM (category INNER JOIN item1 ON category.id = item1.id) INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1) ".
				"WHERE category.id=".$id." AND item1.id1=".$id1." ORDER BY item2.no";

		$res = mysqli_query($db, $sql);
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			echo "<p>登録内容</p>\n";
			echo "<table cellspacing='1' cellpadding='5'>";
			echo "<tr><th>No.</th><th>項目</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>小項目</th>".(($public == Config::CLOSE) ? "<th>並べ替え</th><th>削除</th>" : "")."</tr>\n";
			do {
				echo "<tr><td align='right'>".$fld->item1_no.".".$fld->item2_no."</td>";
				echo "<td><input size='90' type='text' name='name".$fld->id2."' value='".$fld->item2_name."'></td><td align='right'>";
				if ($public == Config::CLOSE) {		// 公開中は配点の変更ができない
					echo "<input size='4' type='text' maxlength='4' name='point".$fld->id2."' value='".$fld->point."'>";
				} else {
					echo $fld->point;
				}
				echo "</td><td style='text-align:center;'><input size='4' type='text' maxlength='4' name='up_recommendation".$fld->id2."' value='".$fld->up_recommendation."'></td>".
					"</td><td style='text-align:center;'><input size='4' type='text' maxlength='4' name='recommendation".$fld->id2."' value='".$fld->recommendation."'></td>".
					"<td><a href='item3.php?id=".$id."&id1=".$id1."&id2=".$fld->id2."'>小項目</a></td>";
				if ($public == Config::CLOSE) {
					echo "<td>";
					if ($fld->item2_no != 1) echo "<a href='item2.php?id=".$id."&id1=".$id1."&id2=".$fld->id2."&up=".$fld->item2_no."'>▲</a>　";
					if ($fld->item2_no != $maxno) echo "<a href='item2.php?id=".$id."&id1=".$id1."&id2=".$fld->id2."&dwn=".$fld->item2_no."'>▼</a>";
					echo "</td><td><input type=\"checkbox\" name='del".$fld->id2."'></td>";
				}
				echo "</tr>\n";
			} while ( $fld = mysqli_fetch_object ( $res ) );
			echo "</table>\n";

			echo "<div align='right' style='margin:5px;'><input type='reset' name='reset' value='リセット'>　　<input type='submit' name='edit' value='　編　集　'></div>\n";
			echo "</td></tr>\n";
		}
		

?>
	</td></tr>
	</table>

	<input type='hidden' name='id' value='<?=$id?>'>
	<input type='hidden' name='id1' value='<?=$id1?>'>

</form>

</div>

</body>
</html>