<?php
/*******************************************************************
list.php
	アンケート一覧
									(C)2005,University of Hyougo.
*******************************************************************/

	// 正規ログイン以外はログイン画面へリダイレクト
	if (!$_REQUEST['uid'])  {
		header("Location: index.php");
		exit();
	}

	require_once "../admin/setup.php";

	$db = Connection::connect();	// データベース接続

	$uid = $_REQUEST['uid'];	// ユーザID

	$id = UserClassification::GetUserType ( $uid );

	$item1_no = "";
	$item2_no = "";
	$item3_no = "";
	$item4_no = "";

?>
<!DOCTYPE HTML>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
  <title>看護ケアの質評価・改善システム</title>
  <link href='liststyle.css' rel='stylesheet' type='text/css'>
<style>
    .image-container {
        display: block; /* 画像が新しい行に表示されるように */
				max-width: 760px; /* 画像の最大幅を設定 */
        width: 100%; /* コンテナの幅を設定 */
        text-align: center; /* 画像を中央に配置 */
    }
    .image-container img {
        max-width: 760px; /* 画像の最大幅を設定 */
        height: auto; /* 高さが自動的に調整されるように */
    }
		img { display: block; }
    .td {
			font-family: Arial, sans-serif;
			border-collapse: collapse;
			line-height: 150%;
			padding: 0;
			font-size: 12px; /* フォントサイズを12pxに設定 */
		}
	
		
</style>
</head>
<body>
<div align='center'>
<table border="0" cellpadding="0" cellspacing="0" background="../usr_img/sub_main_bg.gif" class="tbl01">
<tr><td><img src="../usr_img/sub_head.gif" width="760" height="30" border="0" alt=""></td></tr>
<tr><td><img src="../usr_img/sub_title.jpg" width="760" height="40" border="0" alt=""></td></tr>
<tr class="spnon"><td><img src="../usr_img/spacer.gif" width="760" height="10" border="0" alt=""></td></tr>
<tr><td background="../usr_img/sub_band.jpg">
	<table width="100%"  border="0" cellspacing="0" cellpadding="0">
	<tr><td width="1"><img src="../img/spacer.gif" width="10" height="20"></td><td class='normal'><font color='#FF6600'>≫</font>ＷＥＢ自己評価</td></tr>
	</table>
	</td></tr>
<tr><td valign='top' style="padding:5px;">
	<table width='100%' class='normal'>
	<tr><td align='right'>

<?php


function enum_enquete ( &$enq_answered )
{

	global $db,$id,$uid;

	$html = "";



	$html .= "<table width='100%' cellpadding='5' cellspacing='0' style='margin-top:10px;margin-bottom:2px;border:1px solid #999;' class='normal'>".
		 "<tr><td bgcolor='#999999'><font color='#FFFFFF'>アンケート</font></td></tr></table>\n";

	$sql = "SELECT id,id1,enq,type,unit,csv FROM enquete WHERE id LIKE '".$id."%' ORDER BY id,id1";
	$rs = mysqli_query ( $db ,$sql );

	$enq_answered = TRUE;

	while ( $fld = mysqli_fetch_object ( $rs ) ) {

		// 質問
		$enq = $fld->enq;
		$enq = preg_replace ('<\!--\{ex_([0-9]+)\}-->', '', $enq);
		$html .= "<table width='100%' cellpadding='5' cellspacing='0' style='border:1px solid #999;' class='normal'>\n";
		$html .= "<tr><td>".nl2br($enq)."</td></tr>\n";
		$html .= "<tr><td>";

			// 回答値を取得
			$ans_sql = "SELECT ans FROM enq_usr_ans WHERE id=".$fld->id." AND id1=".$fld->id1." AND uid='".$uid."'";
			$ans_rs = mysqli_query (  $db , $ans_sql );
			$answered = mysqli_num_rows ( $ans_rs );

			if ( $answered ) {

				$ans_fld = mysqli_fetch_object ( $ans_rs );

				if ($ans_fld) { // nullでないか確認

					if ( $fld->type == Config::TEXT || $fld->type == Config::TEXTAREA ) {	// 値 || 文章
						if ( $ans_fld->ans === "" ) {
							$html .= "<span style='color:#f00'>未回答</span>";
						} else {
							$html .= nl2br ( $ans_fld->ans ).$fld->unit;
						}

					} elseif ( $fld->type == Config::CHECK ) {	// 複数選択

						if ( $ans_fld->ans === ""  ) {

							$html .= "<span style='color:#f00'>未回答</span>";

						} else {

							$ans_buf = explode ( "|" ,$ans_fld->ans );	// パイプデリミタによる分割、複数解を得る
							for ( $i = 0; $i < sizeof ( $ans_buf );$i++ ) {
								$q_sql = "SELECT ans FROM enq_ans WHERE id = ".$fld->id." AND id1 = ".$fld->id1." AND id2 = ".$ans_buf[$i];
								$q_rs = mysqli_query ( $db , $q_sql  ) or die ( $q_sql );
								$q_fld = mysqli_fetch_object ( $q_rs );
								$html .= $q_fld->ans."<br>\n";
							}

						}

					} elseif ( $fld->type == Config::RADIO ) {	// 単一選択

						if ( $ans_fld->ans === "" ) {
							$html .= "<span style='color:#f00'>未回答</span>";
						} else {

							$q_sql = "SELECT ans FROM enq_ans WHERE id = ".$fld->id." AND id1 = ".$fld->id1." AND id2 = ".$ans_fld->ans;
							if ( $q_rs = mysqli_query ( $db, $q_sql  ) ) {
								$q_fld = mysqli_fetch_object ( $q_rs );
								$html .= $q_fld->ans."\n";
							}
						}

					}
				} else {
					$html .= "未回答";
				}

			} else {

				if ( $fld->id == "10" || $fld->id == "11" ) {
					$html .= "<span style='color:#f00'>未回答</span>";
				} else {
					$html .= "未回答";
				}

			}

		$html .= "</td></tr>\n";

		if ( $answered ) $html .= "<tr><td><a href='enq.php?edit=1&id=".$fld->id."&id1=".$fld->id1."&uid=".$_REQUEST['uid']."'>≫修正する</a></td></tr>\n";

		$html .= "</table>\n";

		// スペーサ
		$html .= "<table cellpadding='0' cellspacing='0' style='margin-top:10px; class='normal'><tr><td></td></tr></table>\n";

		if ( !$answered ) $enq_answered = FALSE;

	}

	return $html;

}



	if ($id == Config::OUTCOME) {	// アウトカム

		// 質問前後のアンケート
		$html = enum_enquete( $enq_answered );

		if ( $enq_answered ) {
			echo "<a href='./q_a.php?uid=".$_REQUEST['uid']."'>≪アンケートに戻る</a>";
		} else {
			echo "<a href='./enq.php?uid=".$_REQUEST['uid']."'>≪アンケートに戻る</a>";
		}

		echo $html;


		$sql = "SELECT item1.id1,item2.id2,item3.id3,item4.id4,q_order.order_no,".
			"(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no,".
			"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,item4.question ".
			"FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
			"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
			"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
			"LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND (item4.id2 = q_order.id2) ".
			"AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id) ".
			"WHERE item1.id=".$id." ORDER BY q_order.order_no ASC";

		// 未回答値がないかチェック
		$res = mysqli_query ( $db ,$sql );
		$all_answered = TRUE;
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			$item1_no = $fld->item1_no;	$item2_no = $fld->item2_no;	$item3_no = $fld->item3_no;	$item4_no = $fld->item4_no;
			$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;	$id4 = $fld->id4;
			// 回答値取得
			$usrasql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
			$usrares = mysqli_query ( $db ,$usrasql );
			$answered = mysqli_num_rows ( $usrares );	// 回答済
			if ( !$answered ) {
				$all_answered = FALSE;
				break;
			}
		}

		// 全て回答済みの場合は回答完了ボタンを表示
		if ( $all_answered ) echo "<form method='POST' action='".( ($id == Config::OUTCOME) ? "outcome_comp.php" : "enq.php" )."'>\n".
			"<input type='submit' value='≫回答完了'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'></form></td></tr>\n".
			"</table>\n";

		// 質問・回答一覧作成
		$res = mysqli_query ( $db ,$sql );
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			$item1_no = $fld->item1_no;	$item2_no = $fld->item2_no;	$item3_no = $fld->item3_no;	$item4_no = $fld->item4_no;
			$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;	$id4 = $fld->id4;
			echo "<table width='100%' cellpadding='5' cellspacing='0' style='margin-top:10px;margin-bottom:2px;border:1px solid #999;' class='normal'>".
				 "<tr><td bgcolor='#999999'><font color='#FFFFFF'>".$item1_no.".".$item2_no.".".$item3_no.". ".$fld->item3_name."</font></td></tr></table>\n";
			// 質問
			echo "<table width='100%' cellpadding='5' cellspacing='0' style='border:1px solid #999;' class='normal'>\n";
			echo "<tr><td>".nl2br($fld->question)."</td></tr>\n";
			// 回答値取得
			$usrasql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
			$usrares = mysqli_query ( $db ,$usrasql );

			$answered = mysqli_num_rows ( $usrares );	// 回答済
			$usrafld = mysqli_fetch_object ( $usrares );
			echo "<tr><td>";
			// $qfldがnullでないことを確認
			if (isset($qfld) && $qfld->qtype == Config::TEXT) {
				// $usrafldが存在することを確認
				if (isset($usrafld)) {
					echo nl2br($usrafld->ans);
				} else {
					echo "未回答"; // $usrafldが存在しない場合
				}
			} else {
				// $usrafldと$qfldが存在することを確認
				if (isset($usrafld) && isset($qfld)) {
					if(isset($usrafld->ans)) { // $usrafld->ansが存在することを確認
						$asql = "SELECT answer FROM ans WHERE id=" . $id . " AND id1=" . $id1 . " AND id2=" . $id2 . " AND id3=" . $id3 . " AND id4=" . $qfld->id4 . " AND ans_id='" . $usrafld->ans . "'";
						$ares = mysqli_query($db, $asql);
						if ($ares && ($afld = mysqli_fetch_object($ares))) { // 正しく回答が取得できたかを確認
							echo $afld->answer;
						} else {
							echo $answered ? "<span class='no_answer_str'>回答しない</span>" : "未回答";
						}
					} else {
						echo "未回答"; // $usrafld->ansが存在しない場合
					}
				} else {
					echo "未回答"; // $usrafldまたは$qfldが存在しない場合
				}

			}

			echo "</td></tr>\n";
			if ( $answered ) echo"<tr><td><a href='q_a.php?edit=1&id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&id4=".$id4."&uid=".$_REQUEST['uid']."'>≫修正する</a></td></tr>\n";
			echo "</table>\n";
		}

		if ( $enq_answered ) {
			echo "<a href='./q_a.php?uid=".$_REQUEST['uid']."'>≪アンケートに戻る</a>";
		} else {
			echo "<a href='./enq.php?uid=".$_REQUEST['uid']."'>≪アンケートに戻る</a>";
		}

	} else {	// 構造・過程

		// 質問前後のアンケート
		$html = enum_enquete( $enq_answered );

		echo "<a href='./q_a.php?uid=".$_REQUEST['uid']."'>≪アンケートに戻る</a>";

		$sql = "SELECT (item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
			"(item1.id)AS id,(item1.id1)AS id1,(item2.id2)AS id2,(item3.id3)AS id3,".
			"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
			"FROM ((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
			"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
			"WHERE item1.id=".$id." ORDER BY item1.no asc,item2.no asc,item3.no asc";

		// 未回答値がないかチェック
		$res = mysqli_query ( $db ,$sql );
		$all_answered = TRUE;

		while ( $fld = mysqli_fetch_object ( $res ) ) {
			// 大項目
			if ($item1_no != $fld->item1_no) {
				$item1_no = $fld->item1_no;	$item2_no = "";	$item3_no = "";	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}
			// 中項目
			if ($item2_no != $fld->item2_no) {
				$item2_no = $fld->item2_no;	$item3_no = "";	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}
			// 小項目
			if ($item3_no != $fld->item3_no) {
				$item3_no = $fld->item3_no;	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}
			// 質問
			$qsql = "SELECT id4,qtype,question,no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3;
			$qres = mysqli_query ( $db, $qsql  );
			while ( $qfld = mysqli_fetch_object ( $qres ) ) {
				// 回答値取得
				$usrasql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4;
				$usrares = mysqli_query ( $db ,$usrasql );
				$answered = mysqli_num_rows ( $usrares );	// 回答済
				if ( !$answered ) {
					$all_answered = FALSE;
					break;
				}
			}

		}

		// 全て回答済みの場合は回答完了ボタンを表示
		if ( $all_answered ) echo "<form method='POST' action='./confirm.php'>\n".
			"<input type='submit' value='≫回答完了'><input type='hidden' name='uid' value='".$_REQUEST['uid']."'></form></td></tr>\n".
			"</table>\n";


		// 質問・回答一覧作成
		$res = mysqli_query ( $db ,$sql );
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			// 大項目
			if ($item1_no != $fld->item1_no) {
				$item1_no = $fld->item1_no;	$item2_no = "";	$item3_no = "";	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}
			// 中項目
			if ($item2_no != $fld->item2_no) {
				$item2_no = $fld->item2_no;	$item3_no = "";	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
			}
			// 小項目
			if ($item3_no != $fld->item3_no) {
				$item3_no = $fld->item3_no;	$item4_no = "";
				$id = $fld->id;	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
				echo "<table width='100%' cellpadding='5' cellspacing='0' style='margin-top:10px;margin-bottom:2px;border:1px solid #999;' class='normal'>".
					 "<tr><td bgcolor='#999999'><font color='#FFFFFF'>".$item1_no.".".$item2_no.".".$item3_no.". ".$fld->item3_name."</font></td></tr></table>\n";
			}
			// 質問
			echo "<table width='100%' cellpadding='5' cellspacing='0' style='border:1px solid #999;' class='normal'>\n";
			$qsql = "SELECT id4,qtype,question,no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3;
			$qres = mysqli_query ( $db, $qsql  );
			while ( $qfld = mysqli_fetch_object ( $qres ) ) {
				echo "<tr><td>".nl2br($qfld->question)."</td></tr>\n";
				// 回答値取得
				$usrasql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4;
				$usrares = mysqli_query ( $db ,$usrasql );
				$answered = mysqli_num_rows ( $usrares );	// 回答済
				$usrafld = mysqli_fetch_object ( $usrares );

				echo "<tr><td>";
				if ($qfld->qtype == Config::TEXT) {
					// テキストタイプの場合はそのまま表示
					//echo nl2br($usrafld->ans);
					if ($usrafld) {
						echo nl2br($usrafld->ans);
					} else {
						echo "未回答";
					}
				} else {
					// それ以外の場合、選択内容を取得
					if ($usrafld) { // $usrafldがnullでないことを確認
						$asql = "SELECT answer FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$qfld->id4." AND ans_id='".$usrafld->ans."'";
						$ares = mysqli_query($db, $asql);
						// データベースから結果を取得
						if ($afld = mysqli_fetch_object($ares)) {
							// 結果が存在すれば表示
							echo $afld->answer;
						} else {
							// 結果がなければ「回答しない」または「未回答」を表示
							echo $answered ? "<span class='no_answer_str'>回答しない</span>" : "未回答";
						}
					} else {
						// $usrafldがnullの場合は「未回答」を表示
						echo "未回答";
					}
				}
				echo "</td></tr>\n";

				

				if ( $answered ) echo "<tr><td><a href='q_a.php?edit=1&id=".$id."&id1=".$id1."&id2=".$id2."&id3=".$id3."&uid=".$_REQUEST['uid']."'>≫修正する</a></td></tr>\n";
			}
			echo "</table>\n";
		}

		echo $html;

		echo "<a href='./q_a.php?uid=".$_REQUEST['uid']."'>≪アンケートに戻る</a>";

	}


	echo "<table width='100%' class='normal'>\n";
	echo "<tr><td align='right'>";


	if ( $all_answered ) echo "<form method='POST' action='".(($id == Config::OUTCOME) ? "outcome_comp.php" : "./confirm.php")."'>".
		"<input type='submit' value='≫回答完了'>".
		"<input type='hidden' name='uid' value='".$_REQUEST['uid']."'>".
		"</form>";

	echo "</td></tr>\n";

	echo "</table>\n";

?>
</table>
</td></tr>
</table>

<tr><td>
    <div class="image-container">
        <img src="../usr_img/sub_copyright.jpg" width="760" height="20" border="0" alt="">
    </div>
    <div class="image-container">
        <img src="../usr_img/sub_foot.gif" width="760" height="25" border="0" alt="">
    </div>
</td></tr>

</table>

</div>
</body>
</html>
