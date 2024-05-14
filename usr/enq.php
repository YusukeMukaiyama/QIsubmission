<?php
// この行を追加//////////////////////////////////////////////////元はなかった//////////////////////////////////////////////////////////////////////////元はなかった////////////////////////
function next_enq($utype,&$id,&$id1)
{
	$ERR = "";
	global $db,$uid,$utype;

	// 回答があるか
	/////////元々はこっち/////////////if ( $_POST['next'] || $_POST['edit'] ) {	// 回答を記録
	if (isset($_POST['next']) || isset($_POST['edit'])){

		// 入力形式が単位付きの場合は入力値は数値(.含む)
		$sql = "SELECT type,unit FROM enquete WHERE id = ".$_POST['id']." AND id1 = ".$_POST['id1'];
		$rs = mysqli_query ( $db, $sql  );
		$fld = mysqli_fetch_object ( $rs );

		if ( ( $fld->type == Config::TEXT ) && ( $fld->unit ) ) {
			$_POST['ans'] = StringUtilities::str2decimal($_POST['ans']);
			if ( $_POST['ans'] === "" ) $ERR = "入力された値が正しくありません。";
		}

		if (!$ERR) {

			// 複数回答の場合は値をまとめる
			if ( $fld->type == Config::CHECK  ) {

				$ans_sql = "SELECT * FROM enq_ans WHERE id='".$_POST['id']."' AND id1='".$_POST['id1']."' ORDER BY id2 ASC";	// 回答バリエーション取得
				$ans_rs = mysqli_query ($db , $ans_sql  );
				$ans_buf = "";
				while ( $ans_fld = mysqli_fetch_object ( $ans_rs ) ) {
					if ( isset( $_POST['ans'.$ans_fld->id2] ) ) $ans_buf .= "|".$ans_fld->id2;	// パイプ付き回答値を加算
				}
				if ( strlen( $ans_buf ) ) $ans_buf = substr ( $ans_buf ,1);	// 最初のパイプ文字列を削除

				$_POST['ans'] = $ans_buf;

			}

			// 安全な変数の取得
			$id = isset($_POST['id']) ? mysqli_real_escape_string($db, $_POST['id']) : '';
			$id1 = isset($_POST['id1']) ? mysqli_real_escape_string($db, $_POST['id1']) : '';
			$ans = isset($_POST['ans']) ? mysqli_real_escape_string($db, $_POST['ans']) : '';  // 'ans' キーの存在チェック

			// 回答の記録
			$sql = "DELETE FROM enq_usr_ans WHERE id='".$id."' AND id1='".$id1."' AND uid='".$uid."'";
			$rs = mysqli_query($db, $sql);

			$sql = "INSERT INTO enq_usr_ans(id,id1,uid,ans) VALUES('".$id."','".$id1."','".$uid."','".$ans."')";
			$rs = mysqli_query($db, $sql);


			// 回答の記録
			/*
			$sql = "DELETE FROM enq_usr_ans WHERE id=".$_POST['id']." AND id1=".$_POST['id1']." AND uid='".$uid."'";
			$rs = mysqli_query ( $db, $sql  );

			$sql = "INSERT INTO enq_usr_ans(id,id1,uid,ans) VALUES(".$_POST['id'].",".$_POST['id1'].",'".$uid."','".$_POST['ans']."')";
			$rs = mysqli_query ( $db, $sql  );
			*/

			// 修正後は一覧へ戻る
			//if ( $_POST['edit'] ) {
			if (isset($_POST['edit'])) {
				header ( "Location: ./list.php?uid=".$_REQUEST['uid'] );
				exit();
			}

		}

		$id = $_POST['id'];	$id1 = $_POST['id1'];


		// 次のアンケートを取得
		if ( !$ERR ) {
			$sql = "SELECT id,id1 FROM enquete WHERE id = ".$id." AND id1 > ".$id1." ORDER BY id ASC,id1 ASC LIMIT 1";
			$rs = mysqli_query ( $db, $sql  );
			if ( mysqli_num_rows ( $rs ) ) {	// 次のアンケートを表示

				$fld = mysqli_fetch_object ( $rs );;
				$id = $fld->id;	$id1 = $fld->id1;

			} else {

				// 次のアンケートを取得
				$sql = "SELECT id,id1 FROM enquete WHERE id LIKE '".$utype."%' AND id > ".$id." ORDER BY id ASC,id1 ASC LIMIT 1";
				$rs = mysqli_query ( $db, $sql  );

				if ( mysqli_num_rows ( $rs ) ) {	// 次のアンケートを表示
					$fld = mysqli_fetch_object ( $rs );
					$id = $fld->id;	$id1 = $fld->id1;
				} else {
					// 全ての回答が完了した場合( 構造・過程:confirm.php / アウトカム:q_a.php )
					header("Location: ".( $utype != Config::OUTCOME ? "confirm.php" : "q_a.php" )."?uid=".$_REQUEST['uid']);
					exit();
				}

			}

		}

	} else {
		// 回答済みデータが存在するか
		$sql = "SELECT id,id1 FROM enq_usr_ans WHERE uid='".$uid."' ORDER BY id DESC,id1 DESC LIMIT 1";
		$rs = mysqli_query ( $db, $sql  );
		if ( mysqli_num_rows ( $rs ) ) {
			$fld = mysqli_fetch_object ( $rs );
			$id = $fld->id;	$id1 = $fld->id1;
		} else {
			$sql = "SELECT id,id1 FROM enquete WHERE id LIKE '".$utype."%' ORDER BY id ASC,id1 ASC LIMIT 1";
			$rs = mysqli_query ( $db, $sql  );
			if ( mysqli_num_rows ( $rs ) ) {	// データが存在する
				$fld = mysqli_fetch_object ( $rs );
				$id = $fld->id;	$id1 = $fld->id1;
			} else {	// 全ての回答が完了した場合( 構造・過程:confirm.php / アウトカム:q_a.php )
				header("Location: ".( $utype != Config::OUTCOME ? "confirm.php" : "q_a.php" )."?uid=".$_REQUEST['uid']);
				exit();
			}
		}
	}

	// アンケート生成
	$html = view_enquete($id,$id1);
	if ($ERR) $html .= "<font color='red'>".$ERR."</font>";

	// HTMLを返す
	return $html;

}

function view_enquete($id,$id1)
{
	global $db,$script_flg,$enq;

	$sql = "SELECT * FROM enquete WHERE id='".$id."' AND id1='".$id1."'";
	$rs = mysqli_query ( $db, $sql  );
	$fld = mysqli_fetch_object ( $rs );
	$enq = $fld->enq;	$unit = $fld->unit;	$type = $fld->type;

	$sql = "SELECT ans FROM enq_usr_ans WHERE uid='".$_REQUEST['uid']."' AND id='".$id."' AND id1='".$id1."'";
	$rs = mysqli_query ( $db, $sql  );
	if ( mysqli_num_rows ( $rs ) ) {
		$fld = mysqli_fetch_object ( $rs );
		$ans = $fld->ans;
	} else {
		$ans = "";
	}

	//---------------------------------------------------------------------------------------------
	// HTMLコメントがあれば削除
	if(preg_match_all('<\!--\{ex_([0-9]+)\}-->', $enq, $ptn)){
		foreach($ptn[1] as $key => $val){
			$enq = str_replace ( "<!--{ex_".$val."}-->", "", $enq);
		}
	}

	$buf = array ( "<!--" ,"-->" );
	$enq = str_replace ( $buf, "" ,$enq );
	// javascriptを抽出
	preg_match ( "|<script[^>]*>(.+)</script>|Usi", $enq, $script );
	$retstr = ""; ////////////////////////////////////////////////////////////////////////元はなかった/////////////////////////////////////////// この行を追加
	if ( sizeof ( $script ) ) {
		$retstr .= $script[0];	// javascript部分は改行しない
		// javascript以外の文字列を全て抽出
		$html = preg_split ( "|<script[^>]*>(.+)</script>|Usi", $enq );
		if ( is_array ( $html ) ) {
			for ( $i = 0;$i < sizeof ( $html );$i++ ) {
				$retstr .= nl2br ( trim($html[$i]) );
			}
		}
		$script_flg = TRUE;
	} else {	// javascriptなし
		// 改行を<br>に変換して出力
		$retstr .= nl2br ( $enq );
		$script_flg = FALSE;
	}
	//---------------------------------------------------------------------------------------------

	$retstr = "<table>\n".
			"<tr><td>".$retstr."</td></tr>\n".
			"<tr><td>";
/*
	$retstr = "<table>\n".
			"<tr><td>".nl2br($enq)."</td></tr>\n".
			"<tr><td>";
*/	
	if ( $type == Config::TEXT ) {
		if ( $script_flg ) {
			$retstr .= "<input type='text' name='ans' value='".$ans."' readonly>\n";	// 計算式がある場合は変更できない
		} else {
			$retstr .= "<input type='text' name='ans' value='".$ans."'>　".$unit;
		}
	} elseif ( $type == Config::TEXTAREA ) {
		$retstr .= "<textarea name='ans' cols='50' rows='5'>".nl2br($ans)."</textarea>";
	} else {
		$retstr .= "<table>";
		$sql = "SELECT * FROM enq_ans WHERE id='".$id."' AND id1='".$id1."' ORDER BY id2 ASC";
		$rs = mysqli_query ( $db, $sql  );
		if ( $type== Config::CHECK ) {	// 複数回答
			$ans_buf = explode ( "|" ,$ans );	// 複数回答を配列に格納
			while ( $fld = mysqli_fetch_object ( $rs ) ) {
				// チェックが必要か配列を確認する
				$retstr .= "<tr><td><input type='checkbox' name='ans".$fld->id2."' value='".$fld->id2."'".
							( in_array ( $fld->id2 ,$ans_buf ) ? " checked" : "" ).">".nl2br($fld->ans)."</td></tr>";
			}
		} else {
			while ( $fld = mysqli_fetch_object ( $rs ) ) {
				$retstr .= "<tr><td><input type='radio' name='ans' value='".$fld->id2."'".( $fld->id2 == $ans ? " checked" : "" ).">".nl2br($fld->ans)."</td></tr>";
			}
		}
		$retstr .= "</table>";

	}
	$retstr .= "</td></tr>";
	$retstr .= "</table>";

	return $retstr;

}

	require_once "../admin/setup.php";

	// 受渡データ処理
	// ユーザID
	$uid = $_REQUEST['uid'];

	// ユーザ種別
	//if ($_REQUEST['utype']) { $utype  = $_REQUEST['utype']; } else { $utype = GetUserType($uid); }
	$utype = isset($_REQUEST['utype']) ? $_REQUEST['utype'] : UserClassification::GetUserType($uid);

	$db = Connection::connect();	// データベース接続

	//if ( $_GET['edit'] ) {
	if (isset($_GET['edit'])) {


		$html = view_enquete ( $_GET['id'] ,$_GET['id1'] );

		$id = $_GET['id'];
		$id1 = $_GET['id1'];

	} else {

		$html = next_enq ( $utype ,$id ,$id1 );	// 次の質問取得、回答データ記録

	}

	if ($html) {
		// HTMLヘッダ
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'\n".
		"'http://www.w3.org/TR/html4/loose.dtd'>\n".
		"<html>\n".
		"<head>\n".
		"<meta http-equiv='Content-Type' content='text/html; charset=EUC'>\n".
		"<meta name='viewport' content='width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no'>\n".
		"<title>看護ケアの質評価・改善システム</title>\n".
		"<link href='../style.css' rel='stylesheet' type='text/css'>\n".
		"</head>\n";

		//if ( $_POST['text1'] ) {
		if (isset($_POST['text1'])) {
			echo "<body onload=\"document.enquete.text1.value='".$_POST['text1']."'\">\n";
		} else {
			echo "<body>\n";
		}

		
		echo "<div align='center'>\n".
		"<table border='0' cellpadding='0' cellspacing='0' background='../usr_img/sub_main_bg.gif' class='tbl01'>\n".
		"<tr><td><img src='../usr_img/sub_head.gif' width='760' height='30' border='0' alt=''></td></tr>\n".
		"<tr><td><img src='../usr_img/sub_title.jpg' width='760' height='40' border='0' alt=''></td></tr>\n".
		"<tr class='spnon'><td><img src='../usr_img/spacer.gif' width='760' height='10' border='0' alt=''></td></tr>\n".
		"<tr><td background='../usr_img/sub_band.jpg'>\n".
			"<table width='100%'  border='0' cellspacing='0' cellpadding='0'>\n".
			"<tr><td width='1'><img src='../img/spacer.gif' width='10' height='20'></td><td class='large'><font color='#FF6600'>≫</font>アンケート</td></tr>\n".
			"</table>\n".
		"</td></tr>".
		"<tr><td valign='top' style='padding:5px;'><div align='left'><br>".
		"<table width='100%' height='400'>".
		"<tr><td valign='top'>\n".
		"<form name='enquete' method='POST' action='".$_SERVER['PHP_SELF']."'>\n".
		"<a href='./list.php?uid=".$_REQUEST['uid']."'>質問一覧へ</a>";


		echo $html;

		// 保持データ
		echo "<input type='hidden' name='uid' value='".$uid."'>\n";	// ユーザID
		echo "<input type='hidden' name='utype' value='".$utype."'>\n";	// ユーザ種別
		echo "<input type='hidden' name='id' value='".$id."'>\n";	// 質問ID
		echo "<input type='hidden' name='id1' value='".$id1."'>\n";	// 質問番号

		// HTMLフッタ
		echo "<input type='submit' name='next' value='　次　へ　'".
			(( $script_flg && mb_ereg ( "半角数字", $enq ) ) ? "onclick=\"if ( document.enquete.ans.value=='' ) { alert('計算ボタンをクリックしてください。'); return false; }\"" : "")
			.">\n";
		
		/*
		if ( $_GET['edit'] ) echo "<input type='submit' name='edit' value='　編集して一覧へ　'".
			(( $script_flg && mb_ereg ( "半角数字", $enq ) ) ? "onclick=\"if ( document.enquete.ans.value=='' ) { alert('計算ボタンをクリックしてください。'); return false; }\"" : "").
			">";*/
		
		if (isset($_GET['edit'])) echo "<input type='submit' name='edit' value='　編集して一覧へ　'".
		(( $script_flg && mb_ereg ( "半角数字", $enq ) ) ? "onclick=\"if ( document.enquete.ans.value=='' ) { alert('計算ボタンをクリックしてください。'); return false; }\"" : "").
		">";
		

		echo "</form>\n".
			"</div></td></tr></table></td></tr><tr><td><img src='../usr_img/sub_copyright.jpg' width='760' height='20' border='0' alt=''></td></tr>\n".
			"<tr><td><img src='../usr_img/sub_foot.gif' width='760' height='25' border='0' alt=''></td></tr></table>\n".
			"</div>\n".
			"</body>\n".
			"</html>\n";


	}


?>
