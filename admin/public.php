<?php
/*******************************************************************
public.php
--公開 / 非公開 設定
*******************************************************************/
	require_once("setup.php");
	require_once("common.php");

	$db = Connection::connect();	// データベース接続

	/***************************************************************************************************************************/
	// 公開 / 非公開設定
	if (isset($_POST['pub'])) {
		$sql = 	"UPDATE public SET pub = '".$_POST['pub']."';";
		$res = mysqli_query ( $db ,$sql  );

		if ($_POST['pub'] == OPEN) {
			// 最大点数を記録
			$sql = "SELECT (LEFT(uid,2))AS year FROM usr GROUP BY LEFT(uid,2) ORDER BY LEFT(uid,2) desc LIMIT 1";
			$res = mysqli_query ( $db ,$sql  );
			$fld = mysqli_fetch_object ( $res );
			$year = $fld->year;

			$sql = "DELETE FROM history WHERE year='".$year."'";
			$res = mysqli_query ( $db ,$sql  );

			$sql = "INSERT INTO history SELECT id,'".$year."' as year,id1,no,name,point FROM item1";
			$res = mysqli_query ( $db ,$sql  );

		}

	}
	
	/***************************************************************************************************************************/
	// 公開 / 非公開状態の取得
	$public = getPublicationStatus($db);
	
	/***************************************************************************************************************************/
	// 配点チェックエラー詳細取得
	function GetErrText($id,$id1="",$id2="",$id3="",$id4="") {

		global $db;
		$sql = ""; 
		

		switch ($id) {
		case 1:	$category = "構造";			break;
		case 2:	$category = "過程";			break;
		case 3:	$category = "アウトカム";	break;
		default:							break;
		}

		// カテゴリレベルを確認
		if ((!$sql) && ($id3)) {	// 小項目と質問とのチェック
			$sql = "SELECT (item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no ".
				"FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
				"WHERE item1.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." AND item3.id3=".$id3;
			$res = mysqli_query ( $db ,$sql  );
			$fld = mysqli_fetch_object ( $res );
			$item1_no = $fld->item1_no;	$item2_no = $fld->item2_no;	$item3_no = $fld->item3_no;
			$errtxt = "※カテゴリ-".$category."　大項目NO.-".$item1_no."　中項目NO.-".$item2_no."　小項目NO.-".$item3_no;
		}
		if ((!$sql) && ($id2)) {	// 中項目と小項目とのチェック
			$sql = "SELECT (item1.no)AS item1_no,(item2.no)AS item2_no ".
				"FROM item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id) ".
				"WHERE item1.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2;
			$res = mysqli_query ( $db ,$sql  );
			$fld = mysqli_fetch_object ( $res );
			$item1_no = $fld->item1_no;	$item2_no = $fld->item2_no;
			$errtxt = "※カテゴリ-".$category."　大項目NO.-".$item1_no."　中項目NO.-".$item2_no;
		}
		if ((!$sql) && ($id1)) {	// 大項目と中項目とのチェック
			$sql = "SELECT (no)AS item1_no FROM item1 WHERE id=".$id." AND id1=".$id1;
			$res = mysqli_query ( $db ,$sql  );
			$fld = mysqli_fetch_object ( $res );
			$item1_no = $fld->item1_no;
			$errtxt = "※カテゴリ-".$category."　大項目NO.-".$item1_no;
		}
		return $errtxt;

	}


	/***************************************************************************************************************************/
	// 公開前データチェック
	$err = "";
	if ($public == Config :: CLOSE) {
		//--------------------------------------------------------------------------------------------------
		// カテゴリ列挙
		$sql = "SELECT id FROM category ORDER BY id";
		$res = mysqli_query ( $db ,$sql  );

		if ( mysqli_num_rows ( $res ) ) {
			while ( ( $fld = mysqli_fetch_object ( $res ) ) && ( !$err ) ) {
				$id = $fld->id;
				//--------------------------------------------------------------------------------------------------
				// 大項目と中項目とのチェック
				$sql = "SELECT item2.id1,item1.point,SUM(item2.point)AS point2 ".
					"FROM item1 INNER JOIN item2 ON (item1.id=item2.id) AND (item1.id1=item2.id1) ".
					"WHERE item1.id=".$id." GROUP BY item2.id1,item1.point ORDER BY item2.id,item2.id1,item2.id2";
				/*$sql = "SELECT item2.id1, item1.point, SUM(item2.point) AS point2
					FROM item1 INNER JOIN item2 ON (item1.id=item2.id) AND (item1.id1=item2.id1)
					WHERE item1.id=".$id." GROUP BY item2.id1, item1.point ORDER BY item2.id1";*/
					
					
				$res1 = mysqli_query ( $db ,$sql  );
				if ( mysqli_num_rows ( $res1) ) {
					while ( ( $fld1 = mysqli_fetch_object ( $res1 ) ) && ( !$err ) ) {
						$id1 = $fld1->id1;
						if ($fld1->point != $fld1->point2) $err .= "大項目の配点と中項目の配点合計が一致していません。\n".GetErrText($id,$id1);

						//--------------------------------------------------------------------------------------------------
						// 中項目と小項目とのチェック
						$sql = "SELECT item2.id2,item2.point,SUM(item3.point)AS point3 ".
							"FROM item2 INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
							"WHERE item2.id=".$id." AND item2.id1=".$id1." GROUP BY item2.id2, item2.point";
						$res2 = mysqli_query ( $db ,$sql  );
						if ( mysqli_num_rows ( $res2 ) ) {
							while ( ( $fld2 = mysqli_fetch_object ( $res2 ) ) && ( !$err ) ) {
								$id2 = $fld2->id2;
								if ($fld2->point != $fld2->point3) $err .= "中項目の配点と小項目配点合計が一致していません。\n".GetErrText($id,$id1,$id2);

								//--------------------------------------------------------------------------------------------------
								// 小項目内の質問を列挙
								$sql = "SELECT id3 FROM item3 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2;
								$res3 = mysqli_query ( $db ,$sql  );
								if ( mysqli_num_rows ( $res3 ) ) {
									while ( ( $fld3 = mysqli_fetch_object ( $res3 ) ) && ( !$err ) ) {
										$id3 = $fld3->id3;
										//--------------------------------------------------------------------------------------------------
										// 小項目と回答のチェック
										$sql = "SELECT item4.id4,item3.point,MAX(ans.point)AS point_ans ".
											"FROM (item3 INNER JOIN item4 ON (item3.id = item4.id) AND (item3.id1 = item4.id1) AND (item3.id2 = item4.id2) AND (item3.id3 = item4.id3)) ".
											"INNER JOIN ans ON (item4.id = ans.id) AND (item4.id1 = ans.id1) AND (item4.id2 = ans.id2) AND (item4.id3 = ans.id3) AND (item4.id4 = ans.id4) ".
											"WHERE item3.id=".$id." AND item3.id1=".$id1." AND item3.id2=".$id2." AND item3.id3=".$id3." GROUP BY item4.id4, item3.point, item3.id3";
										$res4 = mysqli_query ( $db ,$sql  );
										if ( mysqli_num_rows ( $res4 ) ) {
											$sumpoint_ans = 0;
											while ( ( $fld4 = mysqli_fetch_object ( $res4 ) ) && (!$err)) {
												$id4 = $fld4->id4;
												$sumpoint_ans = $sumpoint_ans + $fld4->point_ans;
												$id4_point = $fld4->point;
											}
											if ($id4_point != $sumpoint_ans) 	{
												$err .= "小項目の配点と質問に対する回答選択肢の最高点数合計が一致していません。\n".GetErrText($id,$id1,$id2,$id3);
											}
										} else {
											$err .= "小項目に一致する質問または回答データがありません。".GetErrText($id,$id1,$id2,$id3);
										}//--------------------------------------------------------------------------------------------------
									}
								} else {
									$err .= "小項目に質問がありません。".GetErrText($id,$id1,$id2);
								}//--------------------------------------------------------------------------------------------------
							}
						} else {
							$err .= "大項目に一致する中項目データがありません。".GetErrText($id,$id1);
						}//--------------------------------------------------------------------------------------------------
					}
				} else {
					die ("大項目データがありません。");	// 致命エラー
				}//--------------------------------------------------------------------------------------------------
			}
		} else {
			die ("カテゴリデータがありません。");	// 致命エラー
		}
		
		//--------------------------------------------------------------------------------------------------
		// アウトカム質問順序矛盾チェック
		$sql = "SELECT item4.id FROM item4 LEFT JOIN q_order ON (item4.id = q_order.id) AND (item4.id1 = q_order.id1) AND ".
			"(item4.id2 = q_order.id2) AND (item4.id3 = q_order.id3) AND (item4.id4 = q_order.id4) ".
			"WHERE q_order.order_no Is Null AND item4.id=3";
		$res = mysqli_query ( $db ,$sql  );
		if ( mysqli_num_rows ( $res ) ) {
			die ("アウトカム質問順序の一部又は全てが未設定です。");
		}
		
	}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>公開設定</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<form method='POST' action='<?=$_SERVER['PHP_SELF']?>'>

		<table cellspacing='1' cellpadding='5'>
		<tr><th><a href='./index.php'>メニュー</a> ≫ 公開設定</th></tr>
		<tr><td>
			<input type='radio' name='pub' value='1'<?= (($public == Config::OPEN) ? " checked" : "") ?>> 公開する　<br>
			<input type='radio' name='pub' value='2'<?= (($public == Config::CLOSE) ? " checked" : "") ?>> 非公開にする(メンテナンス)<br>
			<p>配点の変更、年次更新処理は非公開時のみ可能です。</p>
<?php
	if ($err) {
		echo DispErrMsg ("配点チェックに不整合があるため公開できません。<br>\n".nl2br($err));
	} else {
		echo "<input type='submit' name='set' value='　設定　'>\n";
	}


?>
		</td></tr>
		</table>

	</form>

</div>

</body>
</html>
