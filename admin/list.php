<?php
/*******************************************************************
list.php
	アンケート一覧
									(C)2005,University of Hyougo.
*******************************************************************/

	// 正規ログイン以外はログイン画面へリダイレクト
	if ( !$_REQUEST['uid'] )  {
		header ( "Location: index.php" );
		exit();
	}

	require_once ( "./setup.php" );

	$db = Connection::connect();	// データベース接続

	$uid = $_REQUEST['uid'];	// ユーザID

	$id = GetUserType ( $uid );

?>
<html>
<head>
<meta http-equiv='Content-Style-Type' content='text/css'>
<link type='text/css' rel='stylesheet' href='admin.css'>
<title>回答済データ編集</title>
<body>

<input type='button' name='close' value='閉じる' onclick='window.close();'>
<br>
<br>

<?php

function enum_enquete ()
{

	global $db,$id,$uid;

	// 更新
	//if ( $_REQUEST['edit'] ) {
	if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

		$sql = "SELECT id,id1,enq,type,unit,csv FROM enquete WHERE id LIKE '".$id."%' ORDER BY id,id1";
		$rs = mysqli_query ($db,$sql);
		while ( $fld = mysqli_fetch_object ( $rs ) ) {

			if ( $fld->type == TEXT || $fld->type == TEXTAREA || $fld->type == RADIO ) {

				$updatesql = "DELETE FROM enq_usr_ans WHERE id=".$fld->id." AND id1=".$fld->id1." AND uid='".$uid."'";
				$updaters = mysqli_query ( $db ,$updatesql );

				$updatesql = "INSERT INTO enq_usr_ans(id,id1,uid,ans) VALUES(".$fld->id.",".$fld->id1.",'".$uid."','".$_POST['enq_'.$fld->id.'_'.$fld->id1]."')";
				$updaters = mysqli_query ( $db ,$updatesql );


			} elseif ( $fld->type == CHECK ) {	// 複数選択

				$ans_sql = "SELECT * FROM enq_ans WHERE id='".$fld->id."' AND id1='".$fld->id1."' ORDER BY id2 ASC";	// 回答バリエーション取得
				$ans_rs = mysqli_query ( $db ,$ans_sql );
				
				
				$ans_buf = "";

				while ( $ans_fld = mysqli_fetch_object ( $ans_rs ) ) {

					if ( isset( $_POST['enq_'.$fld->id.'_'.$fld->id1.'_'.$ans_fld->id2] ) ) {
						$ans_buf .= "|".$ans_fld->id2;	// パイプ付き回答値を加算
					}
				}
				if ( strlen( $ans_buf ) ) $ans_buf = substr ( $ans_buf ,1);	// 最初のパイプ文字列を削除

				$updatesql = "DELETE FROM enq_usr_ans WHERE id=".$fld->id." AND id1=".$fld->id1." AND uid='".$uid."'";
				$updaters = mysqli_query ( $db ,$updatesql );

				$updatesql = "INSERT INTO enq_usr_ans(id,id1,uid,ans) VALUES(".$fld->id.",".$fld->id1.",'".$uid."','".$ans_buf."')";
				$updaters = mysqli_query ( $db ,$updatesql );

			}

		}

	}

	$html = "";
	$html .= "<br>\n<table width='500'><tr><td>アンケート</td></tr></table><br>\n";
	$sql = "SELECT id,id1,enq,type,unit,csv FROM enquete WHERE id LIKE '".$id."%' ORDER BY id,id1";
	$rs = mysqli_query ($db,$sql);
	while ( $fld = mysqli_fetch_object ( $rs ) ) {

		// 質問
		$html .= "<table width='500'>\n";
		$html .= "<tr><td>".nl2br(preg_replace ('<\!--\{ex_([0-9]+)\}-->', '', $fld->enq))."</td></tr>\n";
		$html .= "<tr><td>";

		// 回答値を取得
		$ans_sql = "SELECT ans FROM enq_usr_ans WHERE id=".$fld->id." AND id1=".$fld->id1." AND uid='".$uid."'";
		$ans_rs = mysqli_query ( $db ,$ans_sql );
		$ans_fld = mysqli_fetch_object ( $ans_rs );

		if ( $fld->type == TEXT ) {	// 値

			$html .= "<input type='text' name='enq_".$fld->id."_".$fld->id1."' value='".$ans_fld->ans."'>　".$fld->unit;

		} elseif ( $fld->type == TEXTAREA ) {	// 文章

			$html .= "<textarea name='enq_".$fld->id."_".$fld->id1."' cols='65' rows='5'>".$ans_fld->ans."</textarea>";

		} elseif ( $fld->type == CHECK ) {	// 複数選択

			$q_sql = "SELECT id2,ans FROM enq_ans WHERE id='".$fld->id."' AND id1='".$fld->id1."' ORDER BY id2 ASC";
			$q_rs = mysqli_query ( $db ,$q_sql );
			$ans_buf = explode ( "|" ,$ans_fld->ans );	// 複数回答を配列に格納
			while ( $q_fld = mysqli_fetch_object ( $q_rs ) ) {
				$html .= "<input type='checkbox' name='enq_".$fld->id."_".$fld->id1."_".$q_fld->id2."'".( in_array ( $q_fld->id2 ,$ans_buf ) ? " checked" : "" ).">".nl2br($q_fld->ans)."<br>\n";
			}

		} elseif ( $fld->type == RADIO ) {	// 単一選択
			$q_sql = "SELECT id2,ans FROM enq_ans WHERE id='".$fld->id."' AND id1='".$fld->id1."' ORDER BY id2 ASC";
			$q_rs = mysqli_query ( $db ,$q_sql );
			while ( $q_fld = mysqli_fetch_object ( $q_rs ) ) {
				$html .= "<input type='radio' name='enq_".$fld->id."_".$fld->id1."' value='".$q_fld->id2."'".( $q_fld->id2 == $ans_fld->ans ? " checked" : "" ).">".nl2br($q_fld->ans)."<br>\n";
			}
		}

		$html .= "</td></tr>\n";
		$html .= "</table><br>\n";
	}

	return $html;

}

	echo "<form method='POST' action='./list.php?uid=".$_REQUEST['uid']."'>\n";

	echo "ユーザID : ".$_REQUEST['uid']."\n";

	// 関数や処理の始めに変数を初期化
	$item1_no = 0; // 適切な初期値に設定
	$item2_no = 0; // 同上
	$item3_no = 0; // 同上
	$item4_no = 0; // 同上

	if ( $id == OUTCOME ) {	// アウトカム

		// 質問前後のアンケート
		echo enum_enquete();

		$qsql = "SELECT item1.id1,item2.id2,item3.id3,item4.id4,q_order.order_no,".
			"(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no,".
			"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,item4.question ".
			"FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
			"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
			"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
			"LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND (item4.id2 = q_order.id2) ".
			"AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id) ".
			"WHERE item1.id=".$id." ORDER BY q_order.order_no ASC";

		//if ( $_REQUEST['edit'] ) {
		if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

			$qrs = mysqli_query ( $db , $qsql  );

			while ( $qfld = mysqli_fetch_object ( $qrs ) ) {
				$item1_no = $qfld->item1_no;	$item2_no = $qfld->item2_no;	$item3_no = $qfld->item3_no;	$item4_no = $qfld->item4_no;
				$id1 = $qfld->id1;	$id2 = $qfld->id2;	$id3 = $qfld->id3;	$id4 = $qfld->id4;

				// HTMLタグ除去
				$_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ] = strip_tags ( $_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ] );

				// ポイントの取得
				$asql = "SELECT point FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4.
							" AND ans_id='".(int)$_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ]."'";
				$ars = mysqli_query ( $db , $asql  );
				if ( mysqli_num_rows ( $ars ) ) {
					$afld = mysqli_fetch_object ( $ars );
					$point = $afld->point;
				} else {
					$point = 0;
				}

				// 無条件上書
				$exesql = "DELETE FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
				mysqli_query ( $db , $exesql  );

				// 回答を記録
				$exesql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(".$id.",'".$uid."',".$id1.",".$id2.",".$id3.",".$id4.",'".
							$_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$id4 ]."',".(int)$point.")";
				mysqli_query ( $db , $exesql  );

			}

		}

		$rs = mysqli_query ( $db , $qsql  );
		while ($fld = mysqli_fetch_object ( $rs )) {
			$item1_no = $fld->item1_no;	$item2_no = $fld->item2_no;	$item3_no = $fld->item3_no;	$item4_no = $fld->item4_no;
			$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;	$id4 = $fld->id4;
			echo "<br>\n".
				 "<br>\n".
				 "<table width='500'>\n".
				 "<tr><td>".$item1_no.".".$item2_no.".".$item3_no.". ".$fld->item3_name."</td></tr>\n";
			// 質問
			echo "<tr><td>".nl2br($fld->question)."</td></tr>\n";
			// 回答値取得
			$vsql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
			$vrs = mysqli_query ( $db , $vsql  );
			$vfld = mysqli_fetch_object ( $vrs );
			echo "<tr><td>";
			//if ( $qfld->qtype == TEXT ) {	// 回答形式がテキスト入力タイプの場合
			if (isset($qfld) && $qfld !== null && $qfld->qtype == TEXT) {
				echo "<textarea name='Q_".$id."_".$id1."_".$id2."_".$id3."_".$id4."' cols='65' rows='5'>".$vfld->ans."</textarea>";
			} else {	// 選択形式タイプの場合
				$asql = "SELECT ans_id,answer FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." ORDER BY ans_id";
				$ars = mysqli_query ( $db , $asql  );

				while ( $afld = mysqli_fetch_object ( $ars ) ) {
					echo "<input type='radio' name='Q_".$id."_".$id1."_".$id2."_".$id3."_".$id4."' value='".$afld->ans_id."'".
							( $vfld->ans == $afld->ans_id ? " checked" : "" ).">".$afld->answer."<br>\n";
				}
				echo "<input type='radio' name='Q_".$id."_".$id1."_".$id2."_".$id3."_".$id4."' value='0'".( $vfld->ans == 0 ? " checked" : "" ).">回答しない<br>\n";
			}
			echo "</td></tr>\n";
			echo "</table>\n";
		}

	} else {	// 構造・過程

		// 大・中・小項目の取得
		$sql = "SELECT (item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item1.id)AS id,(item1.id1)AS id1,(item2.id2)AS id2,(item3.id3)AS id3,".
			"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
			"FROM ((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
			"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
			"WHERE item1.id=".$id." ORDER BY item1.no asc,item2.no asc,item3.no asc";

		//if ( $_REQUEST['edit'] ) {
		if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {


			$exesql = "UPDATE usr SET cooperation='".$_POST['cooperation']."'  WHERE id=".$id." AND uid='".$uid."'";
			mysqli_query ( $db , $exesql  );

			$rs = mysqli_query ($db,$sql);

			while ($fld = mysqli_fetch_object ( $rs )) {	// 質問・回答の表示
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

				// 回答の記録
				$qsql = "SELECT id4 ,qtype,question,no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." ORDER BY no asc";
				$qrs = mysqli_query ( $db , $qsql  );
				while ( $qfld = mysqli_fetch_object ( $qrs ) ) {

					// HTMLタグ除去
					$_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ] = strip_tags ( $_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ] );

					// ポイントの取得
					$asql = "SELECT point FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4.
								" AND ans_id='".(int)$_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ]."'";
					$ars = mysqli_query ( $db , $asql  );
					if ( mysqli_num_rows ( $ars ) ) {
						$afld = mysqli_fetch_object ( $ars );
						$point = $afld->point;
					} else {
						$point = 0;
					}

					// 無条件上書
					$exesql = "DELETE FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4;
					mysqli_query ( $db , $exesql  );

					// 回答を記録
					$exesql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(".$id.",'".$uid."',".$id1.",".$id2.",".$id3.",".$qfld->id4.",'".
								$_POST[ "Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4 ]."',".(int)$point.")";
					mysqli_query ( $db , $exesql  );

				}

			}

		}
		// 協力
		$psql = "SELECT cooperation FROM usr WHERE id=".$id." AND uid='".$uid."'";
		$prs = mysqli_query ( $db , $psql  );
		while ( $pfld = mysqli_fetch_object ( $prs ) ) {
			echo "<br>\n".
			 "<br>\n".
			 "<table width='500'>\n".
			 "<tr><td>研究へのご協力のお願い</td></tr>\n".
			 "<tr><td><input type=\"radio\" name=\"cooperation\" value=\"同意する\" ";
			if($pfld->cooperation == "同意する" || $pfld->cooperation == "諾") {
				echo "checked";
			}
			echo ">同意する<br>\n";
			echo "<input type=\"radio\" name=\"cooperation\" value=\"同意しない\"";
			if($pfld->cooperation == "同意しない" || $pfld->cooperation == "否") {
				echo "checked";
			}
			echo ">同意しない\n";
			echo "</td></tr>";
			echo "</table>\n";
		}

		$rs = mysqli_query ($db,$sql);

		while ( $fld = mysqli_fetch_object ( $rs ) ) {	// 質問・回答の表示

			if ( $item1_no != $fld->item1_no ) {	// 大項目
				$item1_no = $fld->item1_no;	$item2_no = "";	$item3_no = "";	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}

			if ( $item2_no != $fld->item2_no ) {	// 中項目
				$item2_no = $fld->item2_no;	$item3_no = "";	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}

			if ( $item3_no != $fld->item3_no ) {	// 小項目
				$item3_no = $fld->item3_no;	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
				echo "<br>\n".
					 "<br>\n".
					 "<table width='500'>\n".
					 "<tr><td>".$item1_no.".".$item2_no.".".$item3_no.". ".$fld->item3_name."</td></tr>\n";
			}


			// 質問の取得
			$qsql = "SELECT id4,qtype,question,no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3;
			$qrs = mysqli_query ( $db , $qsql  );
			while ( $qfld = mysqli_fetch_object ( $qrs ) ) {
				echo "<tr><td>".nl2br ( $qfld->question )."</td></tr>\n";
				// 回答値取得
				$vsql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4;
				$vrs = mysqli_query ( $db , $vsql  );
				$vfld = mysqli_fetch_object ( $vrs );

				echo "<tr><td>";
				if ( $qfld->qtype == TEXT ) {	// 回答形式がテキスト入力タイプの場合
					echo "<textarea name='Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4."' cols='65' rows='5'>".$vfld->ans."</textarea>";
				} else {	// 選択形式タイプの場合
					$asql = "SELECT ans_id,answer FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4." ORDER BY ans_id";
					$ars = mysqli_query ( $db , $asql  );
					while ( $afld = mysqli_fetch_object ( $ars ) ) {
						echo "<input type='radio' name='Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4."' value='".$afld->ans_id."'".
								( $vfld->ans == $afld->ans_id ? " checked" : "" ).">".$afld->answer."<br>\n";
					}
					echo "<input type='radio' name='Q_".$id."_".$id1."_".$id2."_".$id3."_".$qfld->id4."' value='0'".( $vfld->ans == 0 ? " checked" : "" ).">回答しない<br>\n";
				}
				echo "</td></tr>\n";
			}
			echo "</table>\n";
		}

		// 質問前後のアンケート
		echo enum_enquete();

	}




	echo "<br>\n".
		 "<input type='submit' name='edit' value='　変　更　'>";

?>
</form>
<br>
<input type='button' name='close' value='閉じる' onclick='window.close();'>

</body>
</html>
