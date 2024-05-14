<?php
/*******************************************************************
q_a.php
	質問・回答
									(C)2005,University of Hyougo.
*******************************************************************/
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../admin_new/setup.php';

// データベース接続
$db = Connection::connect();//

function make_qa($db = null) // $db を関数の引数に追加
{
	$resume = false; // この行を追加して$resumeを初期化

	// 正規ログイン以外はログイン画面へリダイレクト
	function redirectToLoginPage() {
			header("Location: ../index.html");
			exit();
	}

	function redirectToClosePage($uid) {
			header("Location: close.php?uid=" . urlencode($uid));
			exit();
	}

	// 初期チェックとリダイレクト処理を関数で実行
	if (!isset($_REQUEST['uid'])) {
			redirectToLoginPage();
	} elseif (isset($_REQUEST['logout'])) {
			redirectToClosePage($_REQUEST['uid']);
	}


	$uid = $_REQUEST['uid'] ?? '';
	$id1 = $_REQUEST['id1'] ?? '';
	$id2 = $_REQUEST['id2'] ?? '';
	$id3 = $_REQUEST['id3'] ?? '';
	$id4 = $_REQUEST['id4'] ?? '';
	$no = $_REQUEST['no'] ?? '';
	// ユーザID
	$id = UserClassification::GetUserType ( $uid );

	// ユーザ種類を出力
	if ( $id == Config::STRUCTURE ) {
		echo "<!--STRUCTURE-->";
	} elseif ( $id == Config::PROCESS ) {
		echo "<!--PROCESS-->";
	} else {
		echo "<!--Config::OUTCOME-->";
	}

	if (isset($_POST['next']) || isset($_POST['back'])) {

		// 回答の記録
		//--- アウトカム ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
		if ($id == Config::OUTCOME) {

			// HTMLタグ除去
			$_REQUEST['ans'.$id4] = strip_tags($_REQUEST['ans'.$id4]);

			// ポイントの取得
			$ans_sql = "SELECT point FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." AND ans_id='".(int)$_REQUEST['ans'.$id4]."'";
			$ans_res = mysqli_query ( $db , $ans_sql );

			if ( mysqli_num_rows ( $ans_res ) ) {
				$ans_fld = mysqli_fetch_object ( $ans_res );
				$point = $ans_fld->point;
			} else {
				$point = 0;
			}

			// 無条件上書
			$exesql = "DELETE FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
			$exeres = mysqli_query ( $db , $exesql );

			// 回答を記録
			$exesql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(".$id.",'".$uid."',".$id1.",".$id2.",".$id3.",".$id4.",'".$_REQUEST['ans'.$id4]."',".(int)$point.")";
			$exeres = mysqli_query ( $db , $exesql );

			// 記録が完了したら一覧へ戻る
			if (isset($_POST['back'])) header("Location: list.php?uid=".$_REQUEST['uid']);

		//--- 構造・過程 ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
		} else {
			// 変数の値をチェックする
			$id = isset($id) ? $id : 1; // 例えば、デフォルト値として1を設定
			$id1 = isset($id1) ? $id1 : 1;
			$id2 = isset($id2) ? $id2 : 1;
			$id3 = isset($id3) ? $id3 : 1;

			// SQL文を組み立て
			$sql = "SELECT id4, qtype, question, no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." ORDER BY no ASC";
			$res = mysqli_query($db, $sql);
			if (!$res) {
				echo "SQL error: " . mysqli_error($db);
				exit;
			}
			while ( $fld = mysqli_fetch_object ( $res ) ) {

				// HTMLタグ除去
				$_REQUEST['ans'.$fld->id4] = strip_tags($_REQUEST['ans'.$fld->id4]);

				// ポイントの取得
				$ans_sql = "SELECT point FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$fld->id4." AND ans_id='".(int)$_REQUEST['ans'.$fld->id4]."'";
				$ans_res = mysqli_query ( $db , $ans_sql );

				if ( mysqli_num_rows ( $ans_res ) ) {
					$ans_fld = mysqli_fetch_object ( $ans_res );
					$point = $ans_fld->point;
				} else {
					$point = 0;
				}

				// 無条件上書
				$exesql = "DELETE FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$fld->id4;
				$exeres = mysqli_query ( $db , $exesql );

				// 回答を記録
				$exesql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(".$id.",'".$uid."',".$id1.",".$id2.",".$id3.",".$fld->id4.",'".$_REQUEST['ans'.$fld->id4]."',".(int)$point.")";
				$exeres = mysqli_query ( $db , $exesql );

				// 記録が完了したら一覧へ戻る
				if (isset($_POST['back'])) header("Location: list.php?uid=".$_REQUEST['uid']);

			}

		}
		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

		//--- アウトカム ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
		if ($id == Config::OUTCOME) {
			// 次の質問の取得
			$sql = "SELECT item1.id1,item2.id2,item3.id3,item4.id4,q_order.order_no,".
				"(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
				"FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
				"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
				"LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND (item4.id2 = q_order.id2) ".
				"AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id) ".
				"WHERE item1.id=".$id." AND q_order.order_no > ".$no." ORDER BY q_order.order_no ASC LIMIT 1";

			$res = mysqli_query ( $db, $sql  );
			if ( mysqli_num_rows ( $res ) ) {

			} else {
				// 全ての質問が完了した
				header("Location: list.php?uid=".$_REQUEST['uid']);
			}

		//--- 構造・過程 ---------------------------------------------------------------------------------------------------------------------------------------------------------------------
		} else {
			// 現在の質問の並び順を取得
			$sql = "SELECT item1.id AS id, item1.id1 AS id1, item2.id2 AS id2, item3.id3 AS id3, item4.id4 AS id4 "
				  .", item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no, item4.no AS item4_no "
				  ." FROM item1 "
				  ." LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1 "
				  ." LEFT JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2 "
				  ." LEFT JOIN item4 ON item3.id = item4.id AND item3.id1 = item4.id1 AND item3.id2 = item4.id2 AND item3.id3 = item4.id3 "
				  ." WHERE item1.id = ".$id." AND item1.id1 = ".$id1." AND item2.id2 = ".$id2." AND item3.id3 = ".$id3." AND item4.id4 = ".$id4
				  ." ORDER BY item1.no, item2.no, item3.no, item4.no";
			$res = mysqli_query ( $db, $sql  );
			$fld = mysqli_fetch_object ( $res );
			$no1 = $fld->item1_no;
			$no2 = $fld->item2_no;
			$no3 = $fld->item3_no;
			/*$no4 = $fld->item4_no;*/
			$no4 = isset($fld->item4_no) ? $fld->item4_no : 0;


			// 次の質問の取得
			$sql = "SELECT item1.id1,item2.id2,item3.id3,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
				"FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
				"WHERE item1.id=".$id." AND item1.id1=".$id1." AND item2.id2=".$id2." AND item3.no > ".$no3." ORDER BY item1.no asc, item2.no asc, item3.no asc LIMIT 1";

			// 次のitem2の存在を確認
			$res = mysqli_query ( $db, $sql  );
			if ( mysqli_num_rows ( $res ) ) {

			} else {

				$sql = "SELECT item1.id1,item2.id2,item3.id3,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
					"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
					"FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
					"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
					"WHERE item1.id = ".$id." AND item1.id1 = ".$id1." AND item2.no > ".$no2." ORDER BY item1.no asc, item2.no asc, item3.no asc LIMIT 1";

				// 次のitem2の存在を確認
				$res = mysqli_query ( $db, $sql  );
				if ( mysqli_num_rows ( $res ) ) {

				} else {
					// 次のitem1の存在を確認
					$sql = "SELECT item1.id1,item2.id2,item3.id3,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
						"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
						"FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
						"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
						"WHERE item1.id = ".$id." AND item1.no > ".$no1." ORDER BY item1.no asc, item2.no asc, item3.no asc LIMIT 1";

					$res = mysqli_query ( $db, $sql  );
					if ( mysqli_num_rows ( $res ) ) {

					} else {
						// 全ての質問が完了した
						header("Location: enq.php?uid=".$_REQUEST['uid']);
					}
				}
			}
		}
		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

	} elseif (isset($_REQUEST['edit'])) {	// 指定された質問を取得(リストから)


		// 質問の取得
		//--- アウトカム -------------------------------------------------------------------------------------------------------------------------------------------------------------------
		if ($id == Config::OUTCOME) {
			$sql = "SELECT item1.id1,item3.id2,item4.id3,item4.id4,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,q_order.order_no ".
				"FROM ((((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) INNER JOIN item3 ".
				"ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) INNER JOIN item4 ".
				"ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
				"INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
				"AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN q_order ON (usr_ans.id1 = q_order.id1) ".
				"AND (usr_ans.id2 = q_order.id2) AND (usr_ans.id3 = q_order.id3) AND (usr_ans.id4 = q_order.id4) AND (usr_ans.id = q_order.id) ".
				"WHERE item1.id = ".$id." AND item1.id1 = ".$id1." AND item2.id2 = ".$id2." AND item3.id3 = ".$id3." AND item4.id4 = ".$id4;

		//--- 構造・過程 -------------------------------------------------------------------------------------------------------------------------------------------------------------------
		} else {
			$sql = "SELECT item1.id1,item2.id2,item3.id3,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
				"FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
				"WHERE item1.id = ".$id." AND item1.id1 = ".$id1." AND item2.id2 = ".$id2." AND item3.id3 = ".$id3;
		}
		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

	} else {	// 初めて質問に答える

		// 回答データがあるか
		if ($id == Config::OUTCOME) {
			$sql = "SELECT item1.id1,item3.id2,item4.id3,item4.id4,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name,q_order.order_no ".
				"FROM ((((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) INNER JOIN item3 ".
				"ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) INNER JOIN item4 ".
				"ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
				"INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
				"AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN q_order ON (usr_ans.id1 = q_order.id1) ".
				"AND (usr_ans.id2 = q_order.id2) AND (usr_ans.id3 = q_order.id3) AND (usr_ans.id4 = q_order.id4) AND (usr_ans.id = q_order.id) ".
				"WHERE uid='".$uid."' ORDER BY q_order.order_no DESC LIMIT 1";

		//--- 構造・過程 -------------------------------------------------------------------------------------------------------------------------------------------------------------------
		} else {
			$sql = "SELECT item1.id1,item3.id2,item4.id3,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
				"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
				"FROM (((item1 INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
				"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
				"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
				"INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) AND ".
				"(item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id) ".
				"WHERE uid='".$uid."' ORDER BY item1.no desc,item2.no desc,item3.no desc,item4.no desc LIMIT 1";
		}
		//----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

		$res = mysqli_query ( $db, $sql  );
		if ( mysqli_num_rows ( $res ) ) {
			$resume = TRUE;
		}

		if (!$resume) {	// 回答がない場合
			// 最初の質問の取得
			if ($id == Config::OUTCOME) {
				$sql = "SELECT item1.id1,item2.id2,item3.id3,item4.id4,(item1.no)AS item1_no,q_order.order_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
						"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
						"FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
						"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
						"INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND ".
						"(item3.id = item4.id)) LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND ".
						"(item4.id2 = q_order.id2) AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id) ".
						"WHERE item1.id = ".$id." ORDER BY q_order.order_no ASC LIMIT 1";
			} else {
				$sql = "SELECT item1.id1,item2.id2,item3.id3,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,".
					"(item1.name)AS item1_name,(item2.name)AS item2_name,(item3.name)AS item3_name ".
					"FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
					"INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id) ".
					"WHERE item1.id = ".$id." ORDER BY item1.no asc, item2.no asc, item3.no ASC LIMIT 1";
			}
		}
	}

	$res = mysqli_query ( $db, $sql  );
	$fld = mysqli_fetch_object ( $res );
	$id1 = $fld->id1;	$id2 = $fld->id2;	$id3 = $fld->id3;
	$item1_no = $fld->item1_no;	$item2_no = $fld->item2_no;	$item3_no = $fld->item3_no;
	$item1_name = $fld->item1_name;	$item2_name = $fld->item2_name;	$item3_name = $fld->item3_name;

	if ($id == Config::OUTCOME) {
		$id4 = $fld->id4;	$no = $fld->order_no;

		// 全質問数を求める
		$sql = "SELECT COUNT(id4)AS q_ttl FROM item4 WHERE id=".$id;
		$res = mysqli_query ( $db, $sql  );
		$fld = mysqli_fetch_object ( $res );
		$TTL = $fld->q_ttl;

		// 現在の位置を求める(一覧を取得して現在の質問までのカウントを得る)
		$sql = "SELECT q_order.id1,q_order.id2,q_order.id3,q_order.id4 FROM item4,q_order ".
			"WHERE (item4.id=q_order.id AND item4.id1=q_order.id1 AND item4.id2=q_order.id2 AND item4.id3=q_order.id3 AND item4.id4=q_order.id4) AND ".
			"item4.id=".$id." ORDER BY q_order.order_no";
		$res = mysqli_query ( $db, $sql  );
		$NOW = 0;
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			$NOW++;
			if ($fld->id1==$id1 && $fld->id2==$id2 && $fld->id3==$id3 && $fld->id4==$id4) {
				break;
			}
		}

	} else {
			// 現在の質問の並び順を取得
		$sql = "SELECT item1.id AS id, item1.id1 AS id1, item2.id2 AS id2, item3.id3 AS id3 "
			  .", item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no"
			  ." FROM item1 "
			  ." LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1 "
			  ." LEFT JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2 "
			  ." WHERE item1.id = ".$id." AND item1.id1 = ".$id1." AND item2.id2 = ".$id2." AND item3.id3 = ".$id3
			  ." ORDER BY item1.no, item2.no, item3.no";
		$res = mysqli_query ( $db, $sql  );
		$fld = mysqli_fetch_object ( $res );
		$no1 = $fld->item1_no;
		$no2 = $fld->item2_no;
		$no3 = $fld->item3_no;
		//$no4 = $fld->item4_no;
		$no4 = isset($fld->item4_no) ? $fld->item4_no : 0;

		// 全質問数を求める
		$sql = "SELECT COUNT(id3)AS q_ttl FROM item3 WHERE id=".$id;
		$res = mysqli_query ( $db, $sql  );
		$fld = mysqli_fetch_object ( $res );
		$TTL = $fld->q_ttl;

		// 現在の位置を求める(一覧を取得して現在の質問までのカウントを得る)
		$sql = "SELECT item1.id AS id, item1.id1 AS id1, item2.id2 AS id2, item3.id3 AS id3 "
			  .", item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no "
			  ." FROM item1 "
			  ." LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1 "
			  ." LEFT JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2 "
			  ." WHERE item1.id = ".$id
			  ." ORDER BY item1.no, item2.no, item3.no";
		$res = mysqli_query ( $db, $sql  );
		$NOW = 0;
		while ( $fld = mysqli_fetch_object ( $res ) ) {
			$NOW++;
			if ($fld->item1_no==$no1 && $fld->item2_no==$no2 && $fld->item3_no==$no3) {
				break;
			}
		}
	}

	$buf = "現在".$NOW."問目／".$TTL."問中です";

	$contents = ""; // この行を追加して$contentsを初期化
	// 画像がある場合は画像を表示
	$fileName = ImageUtilities::getFileName($id, $id1, $id2, $id3);
	if ($fileName){ 
		$contents .= "<table style='background:url(\"".$fileName."\") no-repeat;background-position: right bottom;width:100%;height:420px;padding:5px;' class='tbl03'><tr><td valign='top'>\n";
	}else{
		$contents .= "<table style='width:100%;height:420px;padding:5px;'><tr><td valign='top'>\n";
	}


	if ($id != Config::OUTCOME) {
		$contents .= "<table width='100%'><tr><td class='normal'><div align='right'><a href='".$_SERVER['PHP_SELF']."?logout=1&uid=".$_REQUEST['uid']."'>ログアウト</a></div></td></tr>\n";
	}else{
		$contents .= "<table width='100%'>\n";
	}
	$contents .= "<tr><td class='normal'><div align='right'>".$buf."</div></td></tr>\n";
	$contents .= "<tr><td class='large'>◆質問 ".$item1_no.".".$item2_no.".".$item3_no.(($id == Config::OUTCOME) ? ".".$id4 : "")."</td></tr>\n";
	$contents .= "</table>";
//
	// 小項目の質問を全て列挙
	if ($id == Config::OUTCOME) {
		$sql = "SELECT item4.id4, item4.qtype, item4.question ".
			"FROM item4 WHERE item4.id=".$id." AND item4.id1=".$id1." AND item4.id2=".$id2." AND item4.id3=".$id3." AND item4.id4=".$id4;
	} else {
		$sql = "SELECT id4 ,qtype,question,no FROM item4 WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." ORDER BY no asc";
	}

	$res = mysqli_query ( $db, $sql  );
	// 質問と選択肢を表示
	while ( $fld = mysqli_fetch_object ( $res ) ) {
		$id4 = $fld->id4;
		// 回答済みの場合は取得
		$usranssql = "SELECT ans FROM usr_ans WHERE id=".$id." AND uid='".$uid."' AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4;
		$usrans_res = mysqli_query ( $db , $usranssql );
		if ( mysqli_num_rows ( $usrans_res ) ) {
			$usrans_fld = mysqli_fetch_object ( $usrans_res );
			$usrans = $usrans_fld->ans;
		} else {
			$usrans = "";
		}

		// 質問表示
		if ($id != Config::OUTCOME){ 
			$contents .= "<table width='600'>\n";
		}else{
			$contents .= "<table width='215'>\n";
		}
		$contents .= "<tr><td class='large'>".nl2br($fld->question)."</td></tr>\n";
		$contents .= "</table>\n";

		// 回答表示

	if ($id != Config::OUTCOME){ 
		$contents .= "<table width='600'>\n";
	}else{
		$contents .= "<table width='215'>\n";
	}

		if ($fld->qtype == Config::SELECT) {	// 選択式
			// 回答を列挙
			$ans_sql = "SELECT ans_id,answer FROM ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2." AND id3=".$id3." AND id4=".$id4." ORDER BY no asc";
			$ans_res = mysqli_query ( $db , $ans_sql );
			while ($ans_fld = mysqli_fetch_object ( $ans_res ) ) {
				$contents .= "<tr><td class='large'><input type='radio' name='ans".$id4."' value='".$ans_fld->ans_id."'".(($usrans == $ans_fld->ans_id) ? ' checked' : '')."> ".$ans_fld->answer."</td></tr>\n";
			}
			// 回答しないは無条件で追加
			$contents .= "<tr><td class='large'><input type='radio' name='ans".$id4."' value='0'".((!$usrans) ? ' checked' : '')."> 回答しない<br><br></td></tr>\n";
		} else {	// 回答入力
			$contents .= "<tr><td valign='top' class='large'>回答:</td><td><textarea rows='3' cols='40' name='ans".$id4."'>".$usrans."</textarea></td></tr>\n";
		}

		$contents .= "</table>\n";

	}
	$contents .= "<table>\n";
	$contents .= "<tr><td class='large'>\n";
	$contents .= "<input type='hidden' name='id'  value='".$id."'>\n";
	$contents .= "<input type='hidden' name='id1' value='".$id1."'>\n";
	$contents .= "<input type='hidden' name='id2' value='".$id2."'>\n";
	$contents .= "<input type='hidden' name='id3' value='".$id3."'>\n";
	$contents .= "<input type='hidden' name='id4' value='".$id4."'>\n";	// アウトカム質問順序変更によ必要
	$contents .= "<input type='hidden' name='no' value='".$no."'>\n";	// アウトカム質問順序変更によ必要
	$contents .= "<input type='hidden' name='uid' value='".$_REQUEST['uid']."'>\n";
	$contents .= "<div class='spimg'><img src='".$fileName."'></div>";
	$contents .= "<input type='submit' name='next' value='≫次の質問へ'>\n";
	if ( isset($_REQUEST['edit']) ) $contents .= "　<input type='submit' name='back' value='≫編集して一覧へ戻る'>\n";

	$contents .= "<br>\n※回答しないを選択した場合は0点となります。";

	$contents .= "</td></tr>\n";
	$contents .= "</table>\n";

	$contents .= "</td></tr>\n";
	$contents .= "</table>\n";


	return $contents;

}

function make_form_start($db)
{
	$contents = ""; // この行を追加
	$contents .= "<form method='POST' action='".$_SERVER['PHP_SELF']."'>\n";
	return $contents;
}

function make_form_end($db)
{
	$contents = ""; // この行を追加
	$contents .= "</form>\n";
	return $contents;
}

$handle = fopen ("template_qa.html", "r") or die ("file open error\n");
$contents = "";
while(TRUE) {
	$data = fread($handle, 8192);
	if (strlen($data) == 0) break;
	$contents .= $data;
}

// 質問一覧へのリンク
$contents = str_replace ( "<!-- QLIST -->" ,"<a href='./list.php?uid=".$_REQUEST['uid']."'>質問一覧へ</a>" ,$contents );

$contents = str_replace("<!-- FORMSTART -->",make_form_start($db), $contents);
$contents = str_replace("<!-- CONTENTS -->",make_qa($db), $contents);
$contents = str_replace("<!-- FORMEND -->",make_form_end($db), $contents);
echo $contents;

?>