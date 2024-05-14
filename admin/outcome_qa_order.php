<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>アウトカム質問順序設定</title>
</head>
<body>
<div align='center'>
	<h1>QIシステム</h1>

	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ アウトカム質問順序設定</th></tr>
	<tr><td>
<?php

	require_once("setup.php");

	$db = Connection::connect();	// データベース接続

	// 公開 / 非公開取得
	$sql = 	"SELECT pub FROM public";
	$res = mysqli_query($db, $sql);
	if (!mysqli_num_rows ( $res )) die ("データの取得に失敗しました");
	$fld = mysqli_fetch_object ( $res );
	$public = $fld->pub;
	

	if ($public == Config::OPEN) {

		echo ("公開中のため設定変更できません。");

	} else {

		// 質問順序が存在する確認
		$sql = "SELECT COUNT(order_no) FROM q_order";
		$res = mysqli_query($db, $sql);

		// 質問順序が無い場合(初回)
		if ((!mysqli_num_rows($res)) || (isset($_GET['reset']) && $_GET['reset'] == 1)) {

			$res = mysqli_query($db, "TRUNCATE TABLE q_order");

			$i = 0;		// カウンタ
			$sql = "SELECT (item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
				"(item1.id)AS id,(item1.id1)AS id1,(item2.id2)AS id2,(item3.id3)AS id3,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
				"FROM ((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
				"WHERE item1.id=3 ORDER BY item1.no asc,item2.no asc,item3.no asc";

			$res = mysqli_query($db, $sql);
			$item1_no = 0;  // 変数を初期化
			$item2_no = 0;  
			$item3_no = 0;
			$item4_no = 0;
			while ( $fld = mysqli_fetch_object ( $res ) ) {

				if ($item1_no != $fld->item1_no) {	// 大項目
					$item1_no = $fld->item1_no;	$item2_no = "";	$item3_no = "";	$item4_no = "";
					$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
				}
				if ($item2_no != $fld->item2_no) {	// 中項目
					$item2_no = $fld->item2_no;	$item3_no = "";	$item4_no = "";
					$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
				}
				if ($item3_no != $fld->item3_no) {	// 小項目
					$item3_no = $fld->item3_no;	$item4_no = "";
					$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
				}
				$qsql = "SELECT id4,qtype,question,no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3;
				$qres = mysqli_query ($db, $qsql);

				while ( $qfld = mysqli_fetch_object ( $qres ) ) {
					$i++;
					$ret = mysqli_query($db, "INSERT INTO q_order(id,id1,id2,id3,id4,order_no) VALUES(".$fld->id.",".$fld->id1.",".$fld->id2.",".$fld->id3.",".$qfld->id4.",".$i.")");

				}
			}

		}

		// 選択されたidをひとつ上げる
		if (isset($_GET['up']) && $_GET['up'] == 1) {

			$sql = "SELECT * FROM q_order WHERE order_no=".$_GET['ord'];
			$res = mysqli_query($db, $sql);
			$fld = mysqli_fetch_object ( $res );
			$upsql = "UPDATE q_order SET order_no=".($_GET['ord'] - 1)." WHERE id=".$fld->id." AND id1=".$fld->id1." AND id2=".$fld->id2." AND id3=".$fld->id3." AND id4=".$fld->id4;

			$sql = "SELECT * FROM q_order WHERE order_no=".($_GET['ord'] - 1);
			$res = mysqli_query($db, $sql);
			$fld = mysqli_fetch_object ( $res );
			$downsql = "UPDATE q_order SET order_no=".$_GET['ord']." WHERE id=".$fld->id." AND id1=".$fld->id1." AND id2=".$fld->id2." AND id3=".$fld->id3." AND id4=".$fld->id4;

			$res = mysqli_query ( $db ,$upsql);
			$res = mysqli_query ( $db ,$downsql );

		}

		// 選択されたidをひとつ下げる
		if (isset($_GET['drop']) && $_GET['drop'] == 1) {

			$sql = "SELECT * FROM q_order WHERE order_no=".$_GET['ord'];
			$res = mysqli_query($db, $sql);
			$fld = mysqli_fetch_object ( $res );
			$upsql = "UPDATE q_order SET order_no=".($_GET['ord'] + 1)." WHERE id=".$fld->id." AND id1=".$fld->id1." AND id2=".$fld->id2." AND id3=".$fld->id3." AND id4=".$fld->id4;

			$sql = "SELECT * FROM q_order WHERE order_no=".($_GET['ord'] + 1);
			$res = mysqli_query($db, $sql);
			$fld = mysqli_fetch_object ( $res );
			$downsql = "UPDATE q_order SET order_no=".$_GET['ord']." WHERE id=".$fld->id." AND id1=".$fld->id1." AND id2=".$fld->id2." AND id3=".$fld->id3." AND id4=".$fld->id4;

			$res = mysqli_query ( $db ,$upsql);
			$res = mysqli_query ( $db ,$downsql );

		}

		// 表示順による質問のリストアップ
		$sql = "SELECT item4.id,item4.id1,item4.id2,item4.id3,item4.id4,item4.question,q_order.order_no ".
			"FROM item4 LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND ".
			"(item4.id2 = q_order.id2) AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id) ".
			"WHERE item4.id=3 ORDER BY q_order.order_no";

		$res = mysqli_query($db, $sql);
		$item_cnt = mysqli_num_rows ( $res );
		$i = 0;
		echo "<table cellspacing='1' cellpadding='5'>\n";
		echo "<tr><th>質問No.</th><th>質問</th><th>表示順</th></tr>";
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			echo "<tr bgcolor='#ffffff'><td>".$fld->id.".".$fld->id1.".".$fld->id2.".".$fld->id3.".".$fld->id4."</td><td>".nl2br($fld->question)."</td><td align='center'>";
			// 削除などによる矛盾を修正
			$i++;
			mysqli_query ( $db, "UPDATE q_order SET order_no=".$i." WHERE id=".$fld->id." AND id1=".$fld->id1." AND id2=".$fld->id2." AND id3=".$fld->id3." AND id4=".$fld->id4 );

			if ($fld->order_no > 1) {
				 echo "<a href='".$_SERVER['PHP_SELF']."?ord=".$fld->order_no."&up=1'>▲</a>";
			}
			if ($fld->order_no != $item_cnt) {
				 echo "<a href='".$_SERVER['PHP_SELF']."?ord=".$fld->order_no."&drop=1'>▼</a>";
			}
			echo "</td></tr>\n";
		}
		echo "</table>\n";

		echo "<p><a href='".$_SERVER['PHP_SELF']."?reset=1'>表示順のリセット</a>　※リセットすると各項目のNo.順に戻ります。</p>";

	}

?>
	</td></tr>
	</table>

</div>

</body>
</html>
