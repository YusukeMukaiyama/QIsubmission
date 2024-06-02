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

	$_POST['newitem'] = half2full($_POST['newitem']);	// 全て全角　未入力不可
	if (!$_POST['newitem']) $adderrstr = "項目が未入力です。";	// 項目
	if ($public == Config::CLOSE) {
		$_POST['newpoint'] = str2int($_POST['newpoint']);	// 数値だけ
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
				deleteImg($id, $id1, $array_id[$i], $row->id3);
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
		$_POST['name'.$array_id[$i]] = half2full($_POST['name'.$array_id[$i]]);
		if (!$_POST['name'.$array_id[$i]]) $errstr = "項目が未入力です。";	// 項目

		// 数値
		if (!is_numeric($_POST['recommendation'.$array_id[$i]])) $errstr = "低得点基準は0より大きな値をご入力ください。";
		if (!is_numeric($_POST['up_recommendation'.$array_id[$i]])) $errstr = "高得点基準は0より大きな値をご入力ください。";

		if ($public == Config::CLOSE) {	// 公開中は不要
			$_POST['point'.$array_id[$i]] = str2int($_POST['point'.$array_id[$i]]);	// 数値だけ
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


$stmt = mysqli_prepare($db, "SELECT item2.id2, item2.name AS item2_name, item2.point, item2.no AS item2_no, item2.recommendation, item2.up_recommendation, category.category, item1.name AS item1_name, item1.no AS item1_no FROM category INNER JOIN item1 ON category.id = item1.id INNER JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1 WHERE category.id = ? AND item1.id1 = ? ORDER BY item2.no");
mysqli_stmt_bind_param($stmt, 'ii', $id, $id1);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);


$item2s = [];  // 中項目のデータを保持する配列
$current_item1_id = null;
$current_item1 = [];

while ($row = mysqli_fetch_assoc($res)) {
    if ($current_item1_id != $row['id2']) {
        if (!empty($current_item1)) {
            $item2s[] = $current_item1;  // 現在の大項目を配列に追加
        }
        $current_item1_id = $row['id2'];
        $current_item1 = [
            'item1_name' => $row['item1_name'],
            'item2s' => []
        ];
    }
    $current_item1['item2s'][] = $row;
}
if (!empty($current_item1)) {
    $item2s[] = $current_item1;  // 最後の大項目を追加
}


?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css">
<title>中項目編集</title>
</head>
<body>
<div align='center'>

    <h1>QIシステム</h1>

    <form method='POST' action='<?=$_SERVER['PHP_SELF']?>'>
    <table cellspacing='1' cellpadding='5'>
    <tr><th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?=$id?>'>大項目編集</a> ≫ 中項目編集</th></tr>
    <tr><td><?= $category ?></td></tr>
    <tr><th><?= $id1 ?>. <?= $item1_name ?></th></tr>
    <tr><td>
    <?php
        if ($adderrstr) echo "<div class='error'>登録できませんでした：<br>".$adderrstr."</div><br>";
        if ($errstr) echo "<div class='error'>編集できませんでした：<br>".$errstr."</div><br>";
    ?>
    <?php
        if ($public == Config::CLOSE) { // 公開中は新規登録できない
    ?>

    <p>新規登録</p>
    <table cellspacing='1' cellpadding='5'>
    <tr><th>項目</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>登録</th></tr>
    <tr><td><input size='90' type='text' name='newitem' value='<?=(($adderrstr) ? $_POST['newitem'] : '')?>'></td>
        <td><input size='4' type='text' maxlength='4' name='newpoint' value='<?=(($newpoint) ? $_POST['newpoint'] : '')?>'></td>
        <td><input size='4' type='text' maxlength='4' name='up_recommendation' value='<?=(($up_recommendation) ? $_POST['up_recommendation'] : '')?>'></td>
        <td><input size='4' type='text' maxlength='4' name='recommendation' value='<?=(($recommendation) ? $_POST['recommendation'] : '')?>'></td>
        <td><input type='submit' name='add' value='    登  録    '></td></tr>
    </table>

    <?php
        }
    ?>
    <!-- 各項目の表示 -->
		<p>登録内容</p>
    <table cellspacing='1' cellpadding='5'>
        <tr><th>No.</th><th>項目</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>小項目</th> <th>並べ替え</th><th>削除</th></tr>
        <?php foreach ($items as $item): ?>
            <tr>
								<td align='right'><?= sanitize($item['item1_no']) ?>.<?= sanitize($item['no']) ?></td>
                <td><input size='90' type='text' name='name<?= $item['id1'] ?>' value='<?= sanitize($item['name']) ?>'></td>
                <td><input size='4' type='text' maxlength='4' name='point<?= $item['id2'] ?>' value='<?= $item['point'] ?>'></td>
                <td><input size='4' type='text' maxlength='4' name='up_recommendation<?= $item['id2'] ?>' value='<?= sanitize($item['up_recommendation']) ?>'></td>
                <td><input size='4' type='text' maxlength='4' name='recommendation<?= $item['id2'] ?>' value='<?= sanitize($item['recommendation']) ?>'></td>
                <td><a href='item3.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $item['id2'] ?>'>小項目</a></td>
								<td>
										<a href='?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $item['id2'] ?>&up=<?= $item['no'] ?>'>▲</a>
										<a href='?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $item['id2'] ?>&dwn=<?= $item['no'] ?>'>▼</a>
								</td>
								<td><input type="checkbox" name='del<?= $item['id2'] ?>'></td>
            </tr>
        <?php endforeach; ?>
    </table>
		
    <div align='right' style='margin:5px;'>
			<input type='reset' name='reset' value='リセット'style='margin-right: 30px;'>
			<input type='submit' name='regist<?php echo $category['id2']; ?>' value='   登   録   '>
		</div>
    </td></tr>
    </table>
    </form>
</div>

</body>
</html>