<?php
/*******************************************************************
ans.php
	回答選択肢メンテナンス
									(C)2005,University of Hyougo.
*******************************************************************/

	require_once("setup.php");
	require_once("common.php");

	$db = Connection::connect();	// データベース接続

	$errstr = ''; // エラーメッセージ用変数を初期化
	$adderrstr = ''; // 追加時のエラーメッセージ用変数を初期化	
	$newpoint = $_POST['newpoint'] ?? ''; // 新規追加時の点数入力用変数を初期化

	$id = $_GET['id'] ?? $_POST['id'];
	$id1 = $_GET['id1'] ?? $_POST['id1'];
	$id2 = $_GET['id2'] ?? $_POST['id2'];
	$id3 = $_GET['id3'] ?? $_POST['id3'];
	$id4 = $_GET['id4'] ?? $_POST['id4'];
	$params = ['id' => $_GET['id'] ?? $_POST['id'], 'id1' => $_GET['id1'] ?? $_POST['id1'], 'id2' => $_GET['id2'] ?? $_POST['id2'], 'id3' => $_GET['id3'] ?? $_POST['id3'], 'id4' => $_GET['id4'] ?? $_POST['id4']];

	/*** 公開 / 非公開取得 ***/
	$public = getPublicationStatus($db);
	
	/*** 最大id、no取得 ***/
	list($maxid, $maxno) = getMaxIdAndNo($db, 'ans', 'ans_id', $params);

	/*** 全てのidを配列に格納 ***/
	$array_id = getIdList($db, 'ans', 'ans_id', $params);

	/**************************************
		編集内容の評価　追加　削除　編集
	**************************************/
	if (isset($_POST['add'])) {	// 新規追加

		if ($maxno > 4) $adderrstr = "既に選択肢が5個登録されています。";

		$_POST['newitem'] = StringUtilities::half2full($_POST['newitem']);	// 全て全角　未入力不可
		if (!$_POST['newitem']) $adderrstr = "回答が未入力です。";	// 回答

		$_POST['newpoint'] = StringUtilities::str2int($_POST['newpoint']);	// 数値だけ

		if (!$adderrstr) {	// エラーがない場合は項目を追加する
			$maxid = $maxid + 1;	$maxno = $maxno + 1;	// 最大値+1で最大の値を生成
			$sql = "INSERT INTO ans(id,id1,id2,id3,id4,ans_id,answer,point,no) ".
					"VALUES(".$id.",".$id1.",".$id2.",".$id3.",".$id4.",'".$maxid."','".$_POST['newitem']."',".$_POST['newpoint'].",".$maxno.")";
			$res = mysqli_query($db,$sql);
		}

	} elseif (isset($_POST['edit'])) {	// 既存データ編集
		/*** 削除 ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			if (isset($_POST['del'.$array_id[$i]])) {	// 削除チェックが付いている
				$sql = "DELETE FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id='".$array_id[$i]."'";
				$res = mysqli_query($db,$sql);
			}
		}

		/*** 更新チェック ***/
		for ($i = 0;$i < sizeof($array_id);$i++) {
			// 全て全角　未入力不可
			$_POST['name'.$array_id[$i]] = StringUtilities::half2full($_POST['name'.$array_id[$i]]);
			if (!$_POST['name'.$array_id[$i]]) $errstr = "回答が未入力です。";	// 項目名
		}

		/*** 更新 ***/
		if (!$errstr) {
			for ($i = 0;$i < sizeof($array_id);$i++) {
				if ($public == Config::CLOSE) {	// 公開中は配点の変更はできない
					$sql = "UPDATE ans SET answer='".$_POST['name'.$array_id[$i]]."',point=".$_POST['point'.$array_id[$i]].
						" WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id='".$array_id[$i]."'";
				} else {
					$sql = "UPDATE ans SET answer='".$_POST['name'.$array_id[$i]]."' WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id='".$array_id[$i]."'";
				}
				$res = mysqli_query($db,$sql);
			}
		}

	}

	/*** 並び替え ***/
	if (isset($_GET['up'])) {
		$ans_id = $_GET['ans_id'];	$swpno = $_GET['up'];

		$sql = "UPDATE ans SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND no=".($swpno - 1);
		$res = mysqli_query($db,$sql);

		$sql = "UPDATE ans SET no=".($swpno - 1)." WHERE id=".$id." AND id1 = ".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id=".$ans_id;
		$res = mysqli_query($db,$sql);

	} elseif (isset($_GET['dwn'])) {
		$ans_id = $_GET['ans_id'];	$swpno = $_GET['dwn'];

		$sql = "UPDATE ans SET no=".$swpno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND no=".($swpno + 1);
		$res = mysqli_query($db,$sql);

		$sql = "UPDATE ans SET no=".($swpno + 1)." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id='".$ans_id."'";
		$res = mysqli_query($db,$sql);

	}

	/*** 編集後のno整合性維持 ***/
	if (isset($_POST['add']) || isset($_POST['edit'])) {	// 追加又は編集があった場合

		$newno = 1;

		$sql = 	"SELECT ans_id,no FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." ORDER BY no";
		$res = mysqli_query($db,$sql);

		while ( $fld = mysqli_fetch_object ( $res ) ) {

			$exesql = "UPDATE ans SET no=".$newno." WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id='".$fld->ans_id."'";
			$maxno = $newno;	// $maxnoの更新(削除や追加があった場合を考慮)
			$exeres = mysqli_query($db, $exesql);
			$newno++;

		}

		
	}

	$sql = "SELECT (category.category)AS category,(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,".
		"(item4.question)AS question,(ans.ans_id)AS ans_id,(ans.answer)AS answer,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no,(ans.point)AS point,(ans.no)AS ans_no ".
		"FROM ((((category INNER JOIN item1 ON category.id = item1.id) ".
		"LEFT JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
		"LEFT JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
		"LEFT JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
		"LEFT JOIN ans ON (item4.id4 = ans.id4) AND (item4.id3 = ans.id3) AND (item4.id2 = ans.id2) AND (item4.id1 = ans.id1) AND (item4.id = ans.id) ".
		"WHERE category.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." AND item3.id3=".$id3." AND item4.id4=".$id4." ORDER BY ans.no";

	$res = mysqli_query($db,$sql);

	$fld = mysqli_fetch_object ( $res );
	$category = $fld->category;
	$item1_no = $fld->item1_no;	$item1_name = $fld->item1_name;
	$item2_no = $fld->item2_no;	$item2_name = $fld->item2_name;
	$item3_no = $fld->item3_no;	$item3_name = $fld->item3_name;
	$question = $fld->question;
	


?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="admin.css" media="all">
<title>回答選択肢編集</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<form method='POST' action='<?=$_SERVER['PHP_SELF']?>'>
	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?=$id?>'>大項目編集</a> ≫ <a href='item2.php?id=<?=$id?>&id1=<?=$id1?>'>中項目編集</a> ≫ 
		<a href='item3.php?id=<?=$id?>&id1=<?=$id1?>&id2=<?=$id2?>'>小項目編集</a> ≫ <a href='item4.php?id=<?=$id?>&id1=<?=$id1?>&id2=<?=$id2?>&id3=<?=$id3?>'>質問編集</a> ≫ 回答選択肢編集</th></tr>
	<tr><td><?= $category ?></td></tr>
	<tr><th><?= $item1_no ?>. <?= $item1_name ?></th></tr>
	<tr><th><?= $item1_no ?>. <?= $item2_no ?>. <?= $item2_name ?></th></tr>
	<tr><th><?= $item1_no ?>. <?= $item2_no ?>. <?= $item3_no ?>. <?= $item3_name ?></th></tr>
	<tr><th><?= nl2br ( $question ) ?></th></tr>
	<tr><td>
<?php
	if ($adderrstr) {
		echo ErrorHandling::DispErrMsg("登録できませんでした。<br>".$adderrstr)."<br>";
	}
	if ($errstr) {
		echo ErrorHandling::DispErrMsg("編集できませんでした。<br>".$errstr)."<br>";
	}
?>
<?php
/***************************************************************************************************************************/
	if (($public == Config::CLOSE) && ($maxno < 5)) {	// 公開中は新規登録できない　最大5個
?>
	<p>新規登録</p>
	<table cellspacing='1' cellpadding='5'>
	<tr><th>回答</th><th>配点</th><th>登録</th></tr>
	<tr><td><input size='75' type='text' name='newitem' value='<?=(($adderrstr) ? $_POST['newitem'] : '')?>'></td>
		<td><input size='4' type='text' maxlength='4' name='newpoint' value='<?=(($newpoint) ? $_POST['newpoint'] : '')?>'></td>
		<td><input type='submit' name='add' value='   登   録   '></td></tr>
	</table>
<?php
	}

	/**** 回答選択肢を全て列挙する ***/
	$sql = "SELECT (category.category)AS category,(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,".
		"(item4.question)AS question,(ans.ans_id)AS ans_id,(ans.answer)AS answer,(ans.point)AS point,(ans.no)AS ans_no ".
		"FROM ((((category INNER JOIN item1 ON category.id = item1.id) ".
		"INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
		"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
		"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
		"INNER JOIN ans ON (item4.id4 = ans.id4) AND (item4.id3 = ans.id3) AND (item4.id2 = ans.id2) AND (item4.id1 = ans.id1) AND (item4.id = ans.id) ".
		"WHERE category.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." AND item3.id3=".$id3." AND item4.id4=".$id4." ORDER BY ans.no";

	$res = mysqli_query($db,$sql);
	while ( $fld = mysqli_fetch_object ( $res ) ) {

		echo "<p>登録内容</p>\n";
		echo "<table cellspacing='1' cellpadding='5'>\n";
		echo "<tr><th>No.</th><th>回答</th><th>配点</th>".(($public == Config::CLOSE) ? "<th>並べ替え</th><th>削除</th>" : "")."</tr>\n";

		do {
			echo "<tr><td align='right'>".$fld->ans_no."</td>";
			echo "<td><input size='75' type='text' name='name".$fld->ans_id."' value='".$fld->answer."'></td><td align='right'>";
			if ($public == Config::CLOSE) {		// 公開中は配点の変更ができない
				echo "<input size='4' type='text' maxlength='4' name='point".$fld->ans_id."' value='".$fld->point."'>";
			} else {
				echo $fld->point;
			}
			echo "</td>";
			if ($public == Config::CLOSE) {
				echo "<td>";
				if ($fld->ans_no != 1) echo "<a href='ans.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&id4=".$id4."&ans_id=".$fld->ans_id."&up=".$fld->ans_no."'>▲</a>　";
				if ($fld->ans_no != $maxno) echo "<a href='ans.php?id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&id4=".$id4."&ans_id=".$fld->ans_id."&dwn=".$fld->ans_no."'>▼</a>";
				echo "</td><td><input type=\"checkbox\" name='del".$fld->ans_id."'></td>";
			}
			echo "</tr>\n";

		} while ( $fld = mysqli_fetch_object ( $res ) );
		echo "</table>\n";

		echo "<div align='right' style='margin:5px;'><input type='reset' name='reset' value='リセット'>　　<input type='submit' name='edit' value='　編　集　'></div>\n";
		echo "</td></tr>\n";

	}
	


?>
	</table>
	<input type='hidden' name='id' value='<?=$id?>'>
	<input type='hidden' name='id1' value='<?=$id1?>'>
	<input type='hidden' name='id2' value='<?=$id2?>'>
	<input type='hidden' name='id3' value='<?=$id3?>'>
	<input type='hidden' name='id4' value='<?=$id4?>'>
	</form>

</div>

</body>
</html>
