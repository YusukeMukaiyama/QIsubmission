<?php
/*******************************************************************
item4.php
	質問項目メンテナンス
									(C)2005,University of Hyougo.
*******************************************************************/

	require_once("setup.php");
	require_once("common.php");//共通関数を読み込み
	
	$errstr = ''; // エラーメッセージ用変数を初期化
	$adderrstr = ''; // 追加時のエラーメッセージ用変数を初期化

	$db = Connection::connect();	// データベース接続

	$id = $_GET['id'] ?? $_POST['id'];
	$id1 = $_GET['id1'] ?? $_POST['id1'];
	$id2 = $_GET['id2'] ?? $_POST['id2'];
	$id3 = $_GET['id3'] ?? $_POST['id3'];

	// 公開状況を取得
	$public = getPublicationStatus($db);
	
	// 最大IDとNoを取得
	list($maxid, $maxno) = getMaxIdAndNo($db, 'item4', 'id4', ['id' => $id, 'id1' => $id1, 'id2' => $id2, 'id3' => $id3]);

	// IDリストを取得
	$array_id = getIdList($db, 'item4', 'id4', ['id' => $id, 'id1' => $id1, 'id2' => $id2, 'id3' => $id3]);

	/**************************************
		編集内容の評価　追加　削除　編集
	**************************************/
	if (isset($_POST['add'])) {	// 新規追加

		$_POST['newitem'] = StringUtilities::half2full($_POST['newitem']);	// 全て全角　未入力不可
		if (!$_POST['newitem']) $adderrstr = "項目名が未入力です.";	// 項目名

		if (!$adderrstr) {	// エラーがない場合は項目を追加する
			$maxid = $maxid + 1;	$maxno = $maxno + 1;	// 最大値+1で最大の値を生成
			$sql = "INSERT INTO item4(id,id1,id2,id3,id4,qtype,question,no) VALUES(".$id.",".$id1.",".$id2.",".$id3.",".$maxid.",".$_POST['qtype'].",'".$_POST['newitem']."',".$maxno.")";
			$res = mysqli_query($db,$sql);
		}

	} elseif (isset($_POST['edit'])) {	// 既存データ編集
		/*** 削除 ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			if (isset($_POST['del'.$array_id[$i]])) {	// 削除チェックが付いている

				// 質問削除
				$sql = "DELETE FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$array_id[$i];
				$res = mysqli_query($db,$sql);

				// 回答削除
				$sql = "DELETE FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$array_id[$i];
				$res = mysqli_query($db,$sql);

			}
		}

		/*** 更新チェック ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			// 全て全角　未入力不可
			$_POST['name'.$array_id[$i]] = StringUtilities::half2full($_POST['name'.$array_id[$i]]);
			if (!$_POST['name'.$array_id[$i]]) $errstr = "項目名が未入力です.";	// 項目名
		}

		/*** 更新 ***/
		if (!$errstr) {
			for ($i = 0;$i < sizeof($array_id);$i++) {
				$sql = "UPDATE item4 SET question='".$_POST['name'.$array_id[$i]]."' WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$array_id[$i];
				$res = mysqli_query($db,$sql);
			}
		}

	}

	/*** 並び替え ***/
	if (isset($_GET['up'])) {
		$id4 = $_GET['id4'];	$swpno = $_GET['up'];

		$sql = "UPDATE item4 SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND no=".($swpno - 1);
		$res = mysqli_query($db,$sql);

		$sql = "UPDATE item4 SET no=".($swpno - 1)." WHERE id=".$id." AND id1 = ".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
		$res = mysqli_query($db,$sql);

	} elseif ( isset($_GET['dwn']) ) {
		$id4 = $_GET['id4'];	$swpno = $_GET['dwn'];

		$sql = "UPDATE item4 SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND no=".($swpno + 1);
		$res = mysqli_query($db,$sql);

		$sql = "UPDATE item4 SET no=".($swpno + 1)." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
		$res = mysqli_query($db,$sql);

	}

	// item4の編集後のno整合性維持
	$actionRequired = $_POST['add'] ?? $_POST['edit'] ?? false;
	if ($actionRequired) {
			maintainOrderConsistency($db, 'item4', ['id' => $id, 'id1' => $id1, 'id2' => $id2, 'id3' => $id3]);
	}


	/*** 各項目名称取得 ***/
	$sql = 
		"SELECT (category.category)AS category,(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,".
    "item4.id4,item4.qtype,item4.question,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no ".
    "FROM (((category INNER JOIN item1 ON category.id = item1.id) ".
    "LEFT JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
    "LEFT JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
    "LEFT JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id) ".
    "WHERE category.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." AND item3.id3=".$id3." ORDER BY item4.no";

	$res = mysqli_query($db,$sql);
	$fld = mysqli_fetch_object ( $res );
	$category = $fld->category;
	$item1_no = $fld->item1_no;	$item1_name = $fld->item1_name;
	$item2_no = $fld->item2_no;	$item2_name = $fld->item2_name;
	$item3_no = $fld->item3_no;	$item3_name = $fld->item3_name;
	

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" type="text/css" href="./admin.css" media="all">
<title>質問編集</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

<form method='POST' action='<?=$_SERVER['PHP_SELF']?>'>
<table cellspacing='1' cellpadding='5'>
<tr><th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?=$id?>'>大項目編集</a> ≫ <a href='item2.php?id=<?=$id?>&id1=<?=$id1?>'>中項目編集</a> ≫ <a href='item3.php?id=<?=$id?>&id1=<?=$id1?>&id2=<?=$id2?>'>小項目編集</a> ≫ 質問編集</th></tr>
<tr><td><?= $category ?></td></tr>
<tr><th><?= $item1_no ?>. <?=$item1_name ?></th></tr>
<tr><th><?= $item1_no ?>. <?=$item2_no ?>. <?= $item2_name ?></th></tr>
<tr><th><?= $item1_no ?>. <?=$item2_no ?>. <?= $item3_no ?>. <?= $item3_name ?></th></tr>
<tr><td>

<?php
	if ($adderrstr) {
		echo ErrorHandling::DispErrMsg("登録できませんでした<br>".$adderrstr)."<br>";
	}
	if ($errstr) {
		echo ErrorHandling::DispErrMsg("編集できませんでした<br>".$errstr)."<br>";
	}
?>
<?php

	if ($public == Config::CLOSE) {	// 公開中は新規登録できない
		if (!$adderrstr) $_POST['qtype'] = Config::SELECT;
?>

	<p>新規登録</p>
	<table cellspacing='1' cellpadding='5'>
	<tr><th>質問</th><th>回答方法</th><th>登録</th></tr>
	<tr><td><textarea name='newitem' rows='10' cols='40'><?=(($adderrstr) ? $_POST['newitem'] : '')?></textarea></td>
		<td><input size='4' type='radio' name='qtype' value='<?=Config::SELECT?>'<?=(($_POST['qtype'] != Config::TEXT) ? ' checked' : '')?>>選択式
			<input size='4' type='radio' name='qtype' value='<?=Config::TEXT?>'<?=(($_POST['qtype'] == Config::TEXT) ? ' checked' : '')?>>入力式</td>
		<td><input type='submit' name='add' value='　登　録　'></td></tr>
	</table>

<?php
	}

	/**** 質問項目を全て列挙する ***/
	$sql = 
		"SELECT (category.category)AS category,(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,".
		"item4.id4,item4.qtype,item4.question,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no ".
		"FROM (((category INNER JOIN item1 ON category.id = item1.id) ".
		"INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
		"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
		"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id) ".
		"WHERE category.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." AND item3.id3=".$id3." ORDER BY item4.no";

	$res = mysqli_query($db,$sql);

	while ( $fld = mysqli_fetch_object ( $res ) ) {
		echo "<p>登録内容</p>\n";
		echo "<table cellspacing='1' cellpadding='5'>\n";
		echo "<tr><th>No.</th><th>質問</th><th>回答方法</th>".(($public == Config::CLOSE) ? "<th>並べ替え</th><th>削除</th>" : "")."</tr>\n";
		do {
			echo "<tr><td align='right'>".$fld->item1_no.".".$fld->item2_no.".".$fld->item3_no.".".$fld->item4_no."</td>";
			echo "<td><textarea name='name".$fld->id4."' rows='5' cols='80'>".$fld->question."</textarea></td><td>";

			if ($fld->qtype == Config::SELECT) {	// 選択
				echo "<a href='ans.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&id4=".$fld->id4."'>回答選択肢</a>";
			} else {	// テキスト
				echo "テキスト";
			}

			if ($public == Config::CLOSE) {
				echo "<td>";
				if ($fld->item4_no != 1) echo "<a href='item4.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&id4=".$fld->id4."&up=".$fld->item4_no."'>▲</a>　";
				if ($fld->item4_no != $maxno) echo "<a href='item4.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&id4=".$fld->id4."&dwn=".$fld->item4_no."'>▼</a>";
				echo "</td><td><input type=\"checkbox\" name='del".$fld->id4."'></td>";
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
	<input type='hidden' name='id3' value='<?=$id3?>'>

	</form>

</div>

</body>
</html>