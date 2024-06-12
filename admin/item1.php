<?php
/*******************************************************************
item1.php
--大項目編集
*******************************************************************/
require_once("setup.php");//設定を読み込み
require_once("common.php");//共通関数を読み込み

$db = Connection::connect();	// データベース接続

$errstr = ''; // エラーメッセージ用変数を初期化
$public = getPublicationStatus($db);// 公開状況を取得


// カテゴリ・大項目
$stmt = mysqli_prepare($db, "SELECT COUNT(category.id) AS idcnt, MAX(item) AS itemcnt FROM category");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$fld = mysqli_fetch_object($res);
$idcnt = $fld->idcnt;
$itemcnt = $fld->itemcnt;

// 編集内容の評価
$stmt = mysqli_prepare($db, "SELECT id, id1 FROM item1 ORDER BY id ASC, id1 ASC");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($fld = mysqli_fetch_object($res)) {
		if (isset($_POST['regist' . $fld->id])) {
				$_POST['name' . $fld->id . $fld->id1] = sanitize($_POST['name' . $fld->id . $fld->id1]);
				if (!$_POST['name' . $fld->id . $fld->id1]) $errstr = "項目名が未入力です。";

				if (!is_numeric($_POST['recommendation' . $fld->id . $fld->id1])) $errstr = "低得点基準は0より大きな値をご入力ください。";
				if (!is_numeric($_POST['up_recommendation' . $fld->id . $fld->id1])) $errstr = "高得点基準は0より大きな値をご入力ください。";

				if ($public == Config::CLOSE) {
						$_POST['point' . $fld->id . $fld->id1] = intval($_POST['point' . $fld->id . $fld->id1]);
						if ($_POST['point' . $fld->id . $fld->id1] < 1) $errstr = "配点は0より大きな値をご入力ください。";
				}
		}
}

// 編集内容の更新
if (!$errstr) {    // エラーがない場合にのみ
	$sql = "SELECT id, id1 FROM item1 ORDER BY id ASC, id1 ASC";
	$res = mysqli_query($db, $sql);
	if ($res) {
			$stmt = mysqli_prepare($db, "UPDATE item1 SET name=?, recommendation=?, up_recommendation=? WHERE id=? AND id1=?");
			while ($fld = mysqli_fetch_object($res)) {
					if (isset($_POST['regist' . $fld->id])) {
							mysqli_stmt_bind_param($stmt, 'siiii', $_POST['name' . $fld->id . $fld->id1], $_POST['recommendation' . $fld->id . $fld->id1], $_POST['up_recommendation' . $fld->id . $fld->id1], $fld->id, $fld->id1);
							mysqli_stmt_execute($stmt);
							if (mysqli_stmt_error($stmt)) {
									error_log("Stmt execute failed: " . mysqli_stmt_error($stmt));
							}
					}
			}
			mysqli_stmt_close($stmt);  // ステートメントを閉じる
	}
}



// カテゴリと大項目データを取得(HTML表示用)
$categories = [];  // カテゴリと大項目のデータを保持する配列
$stmt = mysqli_prepare($db, "SELECT category.item, category.id, category.category, item1.id1, item1.name, item1.point, item1.no, item1.recommendation, item1.up_recommendation FROM item1 INNER JOIN category ON item1.id = category.id ORDER BY category.id ASC, item1.no ASC");
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$current_category_id = null;
$current_category = [];

while ($row = mysqli_fetch_assoc($res)) {
		if ($current_category_id != $row['id']) {
				if (!empty($current_category)) {
						$categories[] = $current_category;  // 現在のカテゴリを配列に追加
				}
				$current_category_id = $row['id'];
				$current_category = [
						'category' => $row['category'],
						'items' => []
				];
		}
		$current_category['items'][] = $row;
}
if (!empty($current_category)) {
		$categories[] = $current_category;  // 最後のカテゴリを追加
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin.css" media="all">
<title>大項目編集</title>
</head>
<body>

<div align='center'>
	<h1>QIシステム</h1>
	<form method='POST' action='<?php echo $_SERVER['PHP_SELF']; ?>'>
		<table cellspacing='1' cellpadding='5'>
			<tr><th><a href='index.php'>メニュー</a> ≫ 大項目編集<?php if (!empty($errstr)) echo "<br>" . sanitize($errstr); ?></th></tr>
			<tr><td>
				<?php foreach ($categories as $category): ?>
					<fieldset>
						<legend><?php echo sanitize($category['category']); ?></legend>
						<table cellspacing='1' cellpadding='5'>
							<tr><th>No.</th><th>内容</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>中項目</th></tr>
							<?php foreach ($category['items'] as $item): ?>
								<tr>
									<td align='right'><?php echo $item['no']; ?></td>
									<td><input size='60' type='text' name='name<?php echo $item['id'].$item['id1']; ?>' value='<?php echo sanitize($item['name']); ?>'></td>
									<td align='right'>
										<?php if ($public == Config::OPEN): ?>
											<?php echo sanitize($item['point']); ?> 
										<?php else: ?>
											<input size='4' type='text' maxlength='4' name='point<?php echo $item['id'].$item['id1']; ?>' value='<?php echo sanitize($item['point']); ?>'>
										<?php endif; ?>
									</td>
									<td style='text-align:center;'><input size='4' type='text' maxlength='4' name='up_recommendation<?php echo $item['id'].$item['id1']; ?>' value='<?php echo sanitize($item['up_recommendation']); ?>'></td>
									<td style='text-align:center;'><input size='4' type='text' maxlength='4' name='recommendation<?php echo $item['id'].$item['id1']; ?>' value='<?php echo sanitize($item['recommendation']); ?>'></td>
									<td><a href='item2.php?id=<?php echo $item['id']; ?>&id1=<?php echo $item['id1']; ?>'>中項目</a></td>
								</tr>
							<?php endforeach; ?>
						</table>
						<div align='right' style='margin:5px;'>
							<input type='reset' name='reset' value='リセット'style='margin-right: 30px;'>
							<input type='submit' name='regist<?php echo $category['id']; ?>' value='   登   録   '>
						</div>
					</fieldset>
				<?php endforeach; ?>
			</td></tr>
		</table>
	</form>
</div>

</body>
</html>