<?php

/*******************************************************************
ファイル名：makedata.php
概要　　　：管理者画面：CVS/PDF作成
									(C)2005,University of Hyougo.
*******************************************************************/
// POST
require_once ( "setup.php" );
require_once __DIR__ . '/../lib/chart_lib.php';
// MOD 2008/05/27
//require_once ( "../lib/chart_lib_hosp.php" );
//require_once ( "../lib/chart_lib_ward.php" );
// MOD START
require_once __DIR__ . '/../lib/chart_lib_hosp2.php';
require_once __DIR__ . '/../lib/chart_lib_ward2.php';

// MOD END

$db = Connection::connect();	

/*******************************************************************
createCSVData
	概要：CSVデータを作成
	引数：$like	検索条件(LIKE文で使用する形式)
	戻値：なし
*******************************************************************/
function createCSVData ( $like )
{

	global $db;
	// ADD 2008/05/27
	$ret = '';
	// ADD END

	$sql = "SELECT id,category FROM category ORDER BY id";
	$res = mysqli_query (  $db ,$sql );
	while ( $row = mysqli_fetch_object ( $res ) ) {

		$category = $row->id;

		// MOD 2008/05/27
		//echo $row->category."\n\n";
		// MOD START
		$ret .= $row->category."\n\n";
		// MOD END

		// 研究協力 2016/06
		if($category == 1 || $category == 2) {
			$ret .= "研究へのご協力のお願い\n";
			$ret .= "ID,回答\n";
			$sql = "SELECT usr.uid, usr.cooperation ".
					"FROM usr ".
					"WHERE usr.id = '".$category."' AND usr.uid LIKE '" . $like . "%' AND usr.uid IS NOT NULL AND usr.comp = '".Config::COMPLETE. "' ";
			$res0 = mysqli_query (  $db ,$sql );
			while ( $row0 = mysqli_fetch_object ( $res0 ) ) {
				$ret .= $row0->uid.",".$row0->cooperation."\n";	// CVSデータ
			}
			$ret .= "\n";
		}

		$sql = "SELECT item1.no as no1,item2.no as no2,item3.no as no3,item4.no as no4,item4.id1,item4.id2,item4.id3,item4.id4,item4.question ".
				"FROM item1,item2,item3,item4 ".
				"WHERE item1.id=item4.id AND item1.id1=item4.id1 AND item2.id=item4.id AND item2.id1=item4.id1 AND item2.id2=item4.id2 AND ".
				"item3.id=item4.id AND item3.id1=item4.id1 AND item3.id2=item4.id2 AND item3.id3=item4.id3 AND ".
				"item4.id='".$category."' ".
				"ORDER BY item1.no,item2.no,item3.no,item4.no";
		$res1 = mysqli_query (  $db ,$sql );

		while ( $row = mysqli_fetch_object ( $res1 ) ) {

			$id1 = $row->id1;
			$id2 = $row->id2;
			$id3 = $row->id3;
			$id4 = $row->id4;
			$no1 = $row->no1;
			$no2 = $row->no2;
			$no3 = $row->no3;
			$no4 = $row->no4;

			// MOD 2008/05/27
			//echo $id1.$id2.$id3." ".preg_replace ( "/\r\n|\n|\r/" ,"" ,$row->question )."\n";
			//echo "ID,回答,得点\n";
			// MOD START
//			$ret .= $id1.$id2.$id3." ".preg_replace ( "/\r\n|\n|\r/" ,"" ,$row->question )."\n";
			$ret .= $no1.$no2.$no3." ".preg_replace ( "/\r\n|\n|\r/" ,"" ,$row->question )."\n";
			$ret .= "ID,回答,得点\n";
			// MOD END

			// 質問項目で検索
			$sql = "SELECT usr_ans.uid, ans.point, usr_ans.ans, ans.answer,item4.qtype ".
					"FROM (item4 INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
					"AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN ans ON (usr_ans.ans = ans.ans_id) AND (usr_ans.id4 = ans.id4) ".
					"AND (usr_ans.id3 = ans.id3) AND (usr_ans.id2 = ans.id2) AND (usr_ans.id1 = ans.id1) AND (usr_ans.id = ans.id) WHERE item4.id='".$category."' ".
					"AND item4.id1='".$id1."' AND item4.id2='".$id2."' AND item4.id3='".$id3."' AND item4.id4='".$id4."' AND usr_ans.uid LIKE '" . $like . "%' ORDER BY usr_ans.uid";
			// ADD 2008/05/23 TODO: SQL高速化
			$sql = "SELECT usr_ans.uid, ans.point, usr_ans.ans, ans.answer,item4.qtype ".
					"FROM (usr_ans left JOIN item4 ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
					"AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN ans ON (usr_ans.ans = ans.ans_id) AND (usr_ans.id4 = ans.id4) ".
					"AND (usr_ans.id3 = ans.id3) AND (usr_ans.id2 = ans.id2) AND (usr_ans.id1 = ans.id1) AND (usr_ans.id = ans.id) WHERE item4.id='".$category."' ".
					"AND usr_ans.id1='".$id1."' AND usr_ans.id2='".$id2."' AND usr_ans.id3='".$id3."' AND usr_ans.id4='".$id4."' AND usr_ans.uid LIKE '" . $like . "%' ORDER BY usr_ans.uid";
			// ADD END
			// ADD 2008/06/26 TODO 削除済みユーザーを出力しない
			$sql = "SELECT usr_ans.uid, ans.point, usr_ans.ans, ans.answer,item4.qtype ".
					" FROM (usr_ans left JOIN item4 ON (item4.id4 = usr_ans.id4) ".
					"  AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
					"  AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) ".
					" LEFT JOIN ans ON (usr_ans.ans = ans.ans_id) AND (usr_ans.id4 = ans.id4) ".
					"  AND (usr_ans.id3 = ans.id3) AND (usr_ans.id2 = ans.id2) ".
					"  AND (usr_ans.id1 = ans.id1) AND (usr_ans.id = ans.id) ".
					" LEFT JOIN usr ON usr_ans.uid = usr.uid ".
					" WHERE item4.id='".$category."' AND usr_ans.id1='".$id1."' AND usr_ans.id2='".$id2."' AND usr_ans.id3='".$id3."' ".
					"  AND usr_ans.id4='".$id4."' AND usr_ans.uid LIKE '" . $like . "%' and usr.uid IS NOT NULL and usr.comp = '".Config::COMPLETE. "' ".
					" ORDER BY usr_ans.uid ";
			// ADD END
			$res2 = mysqli_query (  $db ,$sql );
			while ( $row2 = mysqli_fetch_object ( $res2 ) ) {	// ラジオボタン
				if ( $row2->qtype == Config::SELECT ) {
					if($row2->ans === "0"){
						$anser = "回答しない";
					}else{
						$anser = $row2->answer;
					}
					$point = $row2->point;
				} else {	// テキスト入力
					$anser = preg_replace ( "/\r\n|\n|\r/" ,"" ,$row2->ans );
					$point = "";
				}
				// MOD 2008/05/27
				// echo $row2->uid.",".$anser.",".$point."\n";	// CVSデータ
				// MOD ATART
				$ret .= $row2->uid.",".$anser.",".$point."\n";	// CVSデータ
				// MOD END
			}
			// MOD 2008/05/27
			// echo "\n";
			// MOD SSTART
			$ret .= "\n";
			// MOD END

		}

	}
	// ADD 2008/05/27
	return $ret;
	// ADD END

}


// --> 2006.11.06 尾苗
/*******************************************************************
CreateCSV_textonly
	概要：テキストによる回答方法を持つ
	引数：$file_name	
	戻値：CSVデータダウンロード
*******************************************************************/
function CreateCSV_textonly( $file_name )
{

	global $db;
	// ADD 2008/05/27
	$ret = '';
	// ADD END

	$sql = "SELECT id,category FROM category ORDER BY id";
	$res = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $res ) ) {

		$id = $fld->id;	// カテゴリIDの取得
		// MOD 2008/05/27
		//echo $fld->category."\r\n\r\n";	// ≪カテゴリ名の出力
		// MOD START
		$ret .= $fld->category."\r\n\r\n";	// ≪カテゴリ名の出力
		// MOD END

		// 研究協力 2016/06
		if($id == 1 || $id == 2) {
			$ret .= "研究へのご協力のお願い\n";
			$ret .= "ID,回答\n";
			$sql = "SELECT usr.uid, usr.cooperation ".
					"FROM usr ".
					"WHERE usr.id = '".$id."' AND usr.uid LIKE '" . $file_name . "%' AND usr.uid IS NOT NULL AND usr.comp = '".Config::COMPLETE. "' ";
			$res0 = mysqli_query (  $db ,$sql );
			while ( $row0 = mysqli_fetch_object ( $res0 ) ) {
				$ret .= $row0->uid.",".$row0->cooperation."\n";	// CVSデータ
			}
			$ret .= "\n";
		}

		// テキスト回答を含む問題の抽出
//		$sql = "SELECT id1,id2,id3 FROM item4 WHERE qtype='2' AND id='".$id."' GROUP BY id,id1,id2,id3 ORDER BY id,id1,id2,id3";
		$sql = " SELECT item4.id, item4.id1, item4.id2, item4.id3,MIN(item1.no) AS no1, MIN(item2.no) AS no2, MIN(item3.no) AS no3 "
			  ." FROM item1 "
			  ." LEFT JOIN item2 ON item2.id = item1.id AND item2.id1 = item1.id1 "
			  ." LEFT JOIN item3 ON item3.id = item2.id AND item3.id1 = item2.id1 AND item3.id2 = item2.id2 "
			  ." LEFT JOIN item4 ON item4.id = item3.id AND item4.id1 = item3.id1 AND item4.id2 = item3.id2 AND item4.id3 = item3.id3 "
			  ." WHERE item4.qtype='2' AND item1.id='".$id."' "
			  ." GROUP BY item4.id, item4.id1, item4.id2, item4.id3 "
			  ." ORDER BY no1, no2, no3 ";
		$q_res = mysqli_query (  $db ,$sql );

		while ( $q_fld = mysqli_fetch_object ( $q_res ) ) {

			$sql = "SELECT id4,question FROM item4 WHERE id='".$id."' AND id1='".$q_fld->id1."' AND id2='".$q_fld->id2."' AND id3='".$q_fld->id3."' ORDER BY id4";
			$q4_rs = mysqli_query (  $db ,$sql );

			while ( $q4_fld = mysqli_fetch_object ( $q4_rs ) ) {

				// MOD 2008/05/27
				//echo $q_fld->id1.$q_fld->id2.$q_fld->id3." ".preg_replace ( "/\r\n|\n|\r/" ,"" ,$q4_fld->question )."\n";	// ≪質問番号、質問の出力
				//echo "ID,回答,得点\r\n";
				// MOD START
				$ret .= $q_fld->no1.$q_fld->no2.$q_fld->no3." ".preg_replace ( "/\r\n|\n|\r/" ,"" ,$q4_fld->question )."\n";	// ≪質問番号、質問の出力
//				$ret .= $q_fld->id1.$q_fld->id2.$q_fld->id3." ".preg_replace ( "/\r\n|\n|\r/" ,"" ,$q4_fld->question )."\n";	// ≪質問番号、質問の出力
				$ret .= "ID,回答,得点\r\n";
				// MOD END

				$sql = "SELECT usr_ans.uid,usr_ans.ans,ans.point ".
						"FROM (item4 INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
						"AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN ans ON (usr_ans.ans = ans.ans_id) AND (usr_ans.id4 = ans.id4) ".
						"AND (usr_ans.id3 = ans.id3) AND (usr_ans.id2 = ans.id2) AND (usr_ans.id1 = ans.id1) AND (usr_ans.id = ans.id) WHERE item4.id='".$id."' ".
						"AND item4.id1='".$q_fld->id1."' AND item4.id2='".$q_fld->id2."' AND item4.id3='".$q_fld->id3."' AND item4.id4='".$q4_fld->id4.
						"' AND usr_ans.uid LIKE '" . $file_name . "%' ORDER BY usr_ans.uid";
				// ADD 2008/05/23 TODO: SQL高速化
				$sql = "SELECT usr_ans.uid,usr_ans.ans,ans.point ".
						"FROM (usr_ans INNER JOIN item4 ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
						"AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN ans ON (usr_ans.ans = ans.ans_id) AND (usr_ans.id4 = ans.id4) ".
						"AND (usr_ans.id3 = ans.id3) AND (usr_ans.id2 = ans.id2) AND (usr_ans.id1 = ans.id1) AND (usr_ans.id = ans.id) WHERE usr_ans.id='".$id."' ".
						"AND usr_ans.id1='".$q_fld->id1."' AND usr_ans.id2='".$q_fld->id2."' AND usr_ans.id3='".$q_fld->id3."' AND usr_ans.id4='".$q4_fld->id4.
						"' AND usr_ans.uid LIKE '" . $file_name . "%' ORDER BY usr_ans.uid";
				// ADD END
				// ADD 2008/06/26 TODO 削除済みユーザーを出力しない
				$sql = "SELECT usr_ans.uid,usr_ans.ans,ans.point ".
						" FROM (usr_ans INNER JOIN item4 ON (item4.id4 = usr_ans.id4) ".
						"  AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) ".
						"  AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) ".
						" LEFT JOIN ans ON (usr_ans.ans = ans.ans_id) AND (usr_ans.id4 = ans.id4) ".
						"  AND (usr_ans.id3 = ans.id3) AND (usr_ans.id2 = ans.id2) ".
						"  AND (usr_ans.id1 = ans.id1) AND (usr_ans.id = ans.id) ".
						" LEFT JOIN usr ON usr_ans.uid = usr.uid ".
						" WHERE usr_ans.id='".$id."' AND usr_ans.id1='".$q_fld->id1."'".
						"  AND usr_ans.id2='".$q_fld->id2."' AND usr_ans.id3='".$q_fld->id3."' ".
						"  AND usr_ans.id4='".$q4_fld->id4."' AND usr_ans.uid LIKE '" . $file_name . "%' ".
						"  AND usr.uid IS NOT NULL and usr.comp = '".Config::COMPLETE. "' ".
						" ORDER BY usr_ans.uid ";
				// ADD END
				$a_rs = mysqli_query (  $db ,$sql );
				while ( $a_fld = mysqli_fetch_object ( $a_rs ) ) {
					// MOD 2008/05/27
					//echo $a_fld->uid.",".preg_replace ( "/\r\n|\n|\r/" ,"" ,$a_fld->ans ).",".$a_fld->point."\r\n";
					$ret .= $a_fld->uid.",".preg_replace ( "/\r\n|\n|\r/" ,"" ,$a_fld->ans ).",".$a_fld->point."\r\n";
					// MOD END
				}
				// MOD 2008/05/27
				//echo "\r\n";
				// MOD START
				$ret .= "\r\n";
				// MOD END

			}

		}

	}
	// ADD 2008/05/27
	return $ret;
	// ADD END

}
// <-- 2006.11.06 尾苗


/*******************************************************************
getUserList
	概要：条件を指定してユーザ一覧を取得します。
	引数：$like
	戻値：ユーザ一覧
*******************************************************************/
function getUserList ( $like )
{
	global $db;
	$sql = "SELECT uid FROM usr WHERE uid LIKE '".$like."%' AND comp='1' AND del='1' ORDER BY uid";
	$rs = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		$array_usr[] = $fld->uid;
	}
	return $array_usr;
}

/*******************************************************************
createFilaName
	概要：CVS/PDFファイル名を作成
	引数：なし
	戻値：CVS/PDFファイル名
*******************************************************************/
function createFilaName()
{

	if ( $_POST['ftype'] == 'csv_a' || $_POST['ftype'] == 'pdf_a' ) {

		return getYear();

	} elseif ( $_POST['ftype'] == 'csv_h' || $_POST['ftype'] == 'csv_t' || $_POST['ftype'] == 'pdf_h' || $_POST['ftype'] == 'pdf_ht' || $_POST['recom_h'] ) {

		return getYear()."-".$_POST['pref1']."-".$_POST['hos1'];

	} elseif ( $_POST['ftype'] == 'csv_w' || $_POST['ftype'] == 'csv_wt' || $_POST['ftype'] == 'pdf_w' || $_POST['ftype'] == 'pdf_wt' || $_POST['recom_w'] ) {

		return getYear()."-".$_POST['pref2']."-".$_POST['hos2']."-".$_POST['ward'];

	}

}


/*******************************************************************
getYear
	概要：現在年度取得
	引数：なし
	戻値：年度配列
*******************************************************************/
function getYear()
{
	global $db;

	$sql = "SELECT year FROM year";
	$res = mysqli_query (  $db ,$sql );
	$fld = mysqli_fetch_object ( $res );

	$year = $fld->year;

	return $year;

}


/*******************************************************************
downloadZip
	概要：生成したPDFファイルをZIPしてダウンロードさせる
	引数：$zip	zipファイル名
	戻値：なし
*******************************************************************/
function downloadZip ( $zip )
{

	exec ( 'rm -f ./pdf/*.zip' );	// zipファイル削除

	exec ( '/usr/local/bin/zip ./pdf/'.$zip.' ./pdf/*.pdf' );	// PDFファイルをZIP圧縮

	exec ( 'rm -f ./pdf/*.pdf' );	// PDFファイル削除

	header ( "Location: ./pdf/".$zip );	// ZIPファイルダウンロード

}

/*
	リコメンデーション
	recom_csv ( $file_name );
*/
function recom_csv ( $file_name )
{
$f=fopen('./test.txt','a');
fwrite($f, $file_name . "\"\n");
fclose($f);

	global $db, $year, $usrtype; // ここで必要な変数をグローバル宣言
	// ADD 2008/05/27
	$ret = '';
	// ADD END

	$sql = "SELECT id1 FROM history WHERE year='".$year."' AND id='".$usrtype."' ORDER BY id1 ASC";
	$rs = mysqli_query (  $db ,$sql );

	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		$id1[] = $fld->id1;
	}

	//---------------------------------------------------------------------------------------------
	// 大項目単位のリコメンデーション

	// 集計一時テーブルの初期化
	// MOD 2008/05/23 TODO: 同時実行対応
	//mysqli_query ( "TRUNCATE TABLE ans_total" ,$db );
	// MOD START
	$sql = "CREATE TEMPORARY TABLE t_ans_total (id int(11) NOT NULL default '0',".
		" id1 int(11) NOT NULL default '0', id2 int(11) NOT NULL default '0',".
		" uid varchar(17) NOT NULL default '', point float NOT NULL default '0',".
		" PRIMARY KEY  (id,id1,id2,uid))";
	mysqli_query($db , $sql );
	$sql = "TRUNCATE t_ans_total";
	mysqli_query($db , $sql );
	// MOD END

	// 集計結果を一時テーブルに保存
	$sql = "INSERT INTO ans_total(id,id1,uid,point) ".
		"SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.uid ,SUM(usr_ans.point)AS sum_point ".
		"FROM item4,usr,usr_ans ".
		"WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND ".
		"item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND ".
		"item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$file_name."%' ".
		"GROUP BY usr_ans.id,usr_ans.id1,usr_ans.uid";
	// ADD 2008/05/23 TODO: SQL高速化
	$sql = "INSERT INTO t_ans_total(id,id1,uid,point) ".
		"SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.uid ,SUM(usr_ans.point)AS sum_point ".
		" FROM (usr left join usr_ans ".
		"  ON usr.uid = usr_ans.uid AND usr.id = usr_ans.id) ".
		" LEFT join item4 ".
		"  ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 ".
		"  AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND item4.qtype = '1' ".
		" WHERE usr.comp = '1' ".
		"  AND usr.del = '1' AND usr.uid LIKE '".$file_name."%' and usr.comp = '".Config::COMPLETE. "' ".
		" GROUP BY usr_ans.id,usr_ans.id1,usr_ans.uid";
	// ADD END
	mysqli_query (  $db ,$sql );

	// MOD 2008/05/27
	//echo "基準点以上の大項目\n";
	//echo "ID,ID1,基準点,平均点,項目\n";
	//$sql = "SELECT ans_total.id,ans_total.id1,item1.name,ROUND(AVG(ans_total.point),1)AS avg_point,item1.up_recommendation ".
	//		"FROM ans_total,item1 WHERE item1.id = ans_total.id AND item1.id1 = ans_total.id1 ".
	//		"GROUP BY ans_total.id,ans_total.id1 ORDER BY ans_total.id,ans_total.id1";
	// MOD START
	$ret .= "高得点基準以上の大項目\n";
	$ret .= "ID,ID1,高得点基準,平均点,項目\n";
	$sql = "SELECT t_ans_total.id,t_ans_total.id1,item1.name,ROUND(AVG(t_ans_total.point),1)AS avg_point,item1.up_recommendation ".
			"FROM t_ans_total,item1 WHERE item1.id = t_ans_total.id AND item1.id1 = t_ans_total.id1 ".
			"GROUP BY t_ans_total.id,t_ans_total.id1 ORDER BY t_ans_total.id,t_ans_total.id1";
	// MOD END
	$rs = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		if ( $fld->avg_point >= $fld->up_recommendation ) {
			// MOD 2008/05/27
			//echo $fld->id.",".$fld->id1.",".$fld->recommendation.",".$fld->avg_point.",".$fld->name."\n";
			// MOD START

			$ret .= $fld->id . "," . $fld->id1 . "," . preg_replace("/\r|\n/", '', $fld->up_recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";

			// MOD END
		}
	}

	// MOD 2008/05/27
	//echo "基準点未満の大項目\n";
	//echo "ID,ID1,基準点,平均点,項目\n";
	//$sql = "SELECT ans_total.id,ans_total.id1,item1.name,ROUND(AVG(ans_total.point),1)AS avg_point,item1.recommendation ".
	//		"FROM ans_total,item1 WHERE item1.id = ans_total.id AND item1.id1 = ans_total.id1 ".
	//		"GROUP BY ans_total.id,ans_total.id1 ORDER BY ans_total.id,ans_total.id1";
	// MOD START
	$ret .= "低得点基準以下の大項目\n";
	$ret .= "ID,ID1,低得点基準,平均点,項目\n";
	$sql = "SELECT t_ans_total.id,t_ans_total.id1,item1.name,ROUND(AVG(t_ans_total.point),1)AS avg_point,item1.recommendation ".
			"FROM t_ans_total,item1 WHERE item1.id = t_ans_total.id AND item1.id1 = t_ans_total.id1 ".
			"GROUP BY t_ans_total.id,t_ans_total.id1 ORDER BY t_ans_total.id,t_ans_total.id1";
	// MOD END
	$rs = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		if ( $fld->avg_point <= $fld->recommendation ) {
			// MOD 2008/05/27
			//echo $fld->id.",".$fld->id1.",".$fld->recommendation.",".$fld->avg_point.",".$fld->name."\n";
			// MOD START
			$ret .= $fld->id . "," . $fld->id1 . "," . preg_replace("/\r|\n/", '', $fld->up_recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";

			// MOD END
		}
	}

	//---------------------------------------------------------------------------------------------
	// 中項目単位のリコメンデーション

	// 集計一時テーブルの初期化
	// MOD 2008/05/23 TODO: 同時実行対応
	//mysqli_query ( "TRUNCATE TABLE ans_total" ,$db );
	// MOD START
	//mysqli_query ( "TRUNCATE TABLE t_ans_total" ,$db );
	// MOD END

	// 集計結果を一時テーブルに保存
	$sql = "INSERT INTO ans_total(id,id1,id2,uid,point) ".
		"SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.id2 ,usr_ans.uid ,SUM(usr_ans.point)AS point ".
		"FROM item4,usr,usr_ans ".
		"WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND ".
		"item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND ".
		"item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$file_name."%' ".
		"GROUP BY usr_ans.id,usr_ans.id1,usr_ans.id2,usr_ans.uid";
	// ADD 2008/05/23
	$sql = "INSERT INTO t_ans_total(id,id1,id2,uid,point) ".
		"SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.id2 ,usr_ans.uid ,SUM(usr_ans.point)AS point ".
		" FROM (usr LEFT JOIN usr_ans ".
		"  ON usr.uid = usr_ans.uid AND usr.id = usr_ans.id) ".
		"  LEFT JOIN item4 ".
		"   ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 ".
		"    AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 ".
		" WHERE usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$file_name."%' ".
		"  AND item4.qtype = '1' ".
		" GROUP BY usr_ans.id,usr_ans.id1,usr_ans.id2,usr_ans.uid";
	// ADD END
	mysqli_query (  $db ,$sql );

	// MOD 2008/05/27
	//echo "基準点以上の中項目\n";
	//echo "ID,ID1,ID2,基準点,平均点,項目\n";
	//$sql = "SELECT ans_total.id,ans_total.id1,ans_total.id2,item2.name,ROUND(AVG(ans_total.point),1)AS avg_point,item2.up_recommendation ".
	//		"FROM ans_total,item2 WHERE item2.id = ans_total.id AND item2.id1 = ans_total.id1 AND item2.id2 = ans_total.id2 ".
	//		"GROUP BY ans_total.id,ans_total.id1,ans_total.id2 ORDER BY ans_total.id,ans_total.id1,ans_total.id2";
	// MOD START
	$ret .= "高得点基準以上の中項目\n";
	$ret .= "ID,ID1,ID2,高得点基準,平均点,項目\n";
	$sql = "SELECT t_ans_total.id,t_ans_total.id1,t_ans_total.id2,item2.name,ROUND(AVG(t_ans_total.point),1)AS avg_point,item2.up_recommendation,MIN(item1.no) AS no1,MIN(item2.no) AS no2 ".
			"FROM t_ans_total,item2 ".
			"LEFT JOIN item1 ON item1.id=item2.id AND item1.id1=item2.id1 ".
			"WHERE item2.id = t_ans_total.id AND item2.id1 = t_ans_total.id1 AND item2.id2 = t_ans_total.id2 ".
			"GROUP BY t_ans_total.id,t_ans_total.id1,t_ans_total.id2 ORDER BY t_ans_total.id,no1,no2";
//			"GROUP BY t_ans_total.id,t_ans_total.id1,t_ans_total.id2 ORDER BY t_ans_total.id,t_ans_total.id1,t_ans_total.id2";
	// MOD END

	$rs = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		if ( $fld->avg_point >= $fld->up_recommendation ) {
			// MOD 2008/05/27
			//echo $fld->id.",".$fld->id1.",".$fld->id2.",".$fld->recommendation.",".$fld->avg_point.",".$fld->name."\n";
			// MOD START
//			$ret .= $fld->id.",".$fld->id1.",".$fld->id2.",".ereg_replace("\r|\n",'',$fld->up_recommendation).",".$fld->avg_point.",".ereg_replace("\r|\n",'',$fld->name)."\n";
			$ret .= $fld->id . "," . $fld->no1 . "," . $fld->no2 . "," . preg_replace("/\r|\n/", '', $fld->up_recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";
			// MOD END
		}
	}

	// MOD 2008/05/27
	//echo "基準点未満の中項目\n";
	//echo "ID,ID1,ID2,基準点,平均点,項目\n";
	//$sql = "SELECT ans_total.id,ans_total.id1,ans_total.id2,item2.name,ROUND(AVG(ans_total.point),1)AS avg_point,item2.recommendation ".
	//		"FROM ans_total,item2 WHERE item2.id = ans_total.id AND item2.id1 = ans_total.id1 AND item2.id2 = ans_total.id2 ".
	//		"GROUP BY ans_total.id,ans_total.id1,ans_total.id2 ORDER BY ans_total.id,ans_total.id1,ans_total.id2";
	// MOD START
	$ret .= "低得点基準以下の中項目\n";
	$ret .= "ID,ID1,ID2,低得点基準,平均点,項目\n";
	$sql = "SELECT t_ans_total.id,t_ans_total.id1,t_ans_total.id2,item2.name,ROUND(AVG(t_ans_total.point),1)AS avg_point,item2.recommendation ,MIN(item1.no) AS no1,MIN(item2.no) AS no2 ".
			"FROM t_ans_total,item2 ".
			"LEFT JOIN item1 ON item1.id=item2.id AND item1.id1=item2.id1 ".
			"WHERE item2.id = t_ans_total.id AND item2.id1 = t_ans_total.id1 AND item2.id2 = t_ans_total.id2 ".
			"GROUP BY t_ans_total.id,t_ans_total.id1,t_ans_total.id2 ORDER BY t_ans_total.id,no1,no2";
//			"GROUP BY t_ans_total.id,t_ans_total.id1,t_ans_total.id2 ORDER BY t_ans_total.id,t_ans_total.id1,t_ans_total.id2";
	// MOD END
	$rs = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		if ( $fld->avg_point <= $fld->recommendation ) {
			// MOD 2008/05/27
			//echo $fld->id.",".$fld->id1.",".$fld->id2.",".$fld->recommendation.",".$fld->avg_point.",".$fld->name."\n";
			// MOD START
			$ret .= $fld->id . "," . $fld->no1 . "," . $fld->no2 . "," . preg_replace("/\r|\n/", '', $fld->recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";
//			$ret .= $fld->id.",".$fld->id1.",".$fld->id2.",".ereg_replace("\r|\n",'',$fld->recommendation).",".$fld->avg_point.",".ereg_replace("\r|\n",'',$fld->name)."\n";
			// MOD END
		}
	}

	//---------------------------------------------------------------------------------------------
	// 質問単位のリコメンデーション

	// 集計一時テーブルの初期化
	mysqli_query ( $db , "TRUNCATE TABLE ans_total2" );

	// 集計結果を一時テーブルに保存
	$sql =
		"INSERT INTO ans_total2(id,id1,id2,id3,id4,uid,point) ".
		"SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.id2, usr_ans.id3, usr_ans.id4 ,usr_ans.uid ,SUM(usr_ans.point)AS point ".
		"FROM item4,usr,usr_ans ".
		"WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND ".
		"item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND ".
		"item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$file_name."%' ".
		"GROUP BY usr_ans.id,usr_ans.id1,usr_ans.id2,usr_ans.uid";
	// ADD 2008/05/23 TODO
	$sql =
		"INSERT INTO ans_total2(id,id1,id2,id3,id4,uid,point) ".
		"SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.id2, usr_ans.id3, usr_ans.id4 as id4 ,usr_ans.uid ,SUM(usr_ans.point)AS point ".
		" FROM usr,item4 LEFT JOIN usr_ans ".
		"  ON usr.id = usr_ans.id AND usr.uid = usr_ans.uid ".
		" WHERE item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 ".
		"  AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND item4.qtype = '1' ".
		"  AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$file_name."%' ".
		" GROUP BY usr_ans.id,usr_ans.id1,usr_ans.id2,usr_ans.uid ";
	// ADD END
	mysqli_query (  $db ,$sql );

	// MOD 2008/05/27
	//echo "基準点以下の質問\n";
	//echo "ID,ID1,ID2,ID3,ID4,基準点,点数,項目\n";
	// MOD START
	$ret .= "基準点以下の質問\n";
	$ret .= "ID,ID1,ID2,ID3,ID4,基準点,点数,項目\n";
	// MOD END
	$sql = 
		"SELECT ans_total2.id,ans_total2.id1,ans_total2.id2,ans_total2.id3,ans_total2.id4,item3.name,ROUND(AVG(ans_total2.point),1)AS avg_point,MIN(item1.no) AS no1,MIN(item2.no) AS no2,MIN(item3.no) AS no3,MIN(item4.no) AS no4 ".
		"FROM ans_total2,item4, item3 ".
		"LEFT JOIN item2 ON item2.id=item3.id AND item2.id1=item3.id1 AND item2.id2=item3.id2 ".
		"LEFT JOIN item1 ON item1.id=item2.id AND item1.id1=item2.id1 ".
		"WHERE item4.id = ans_total2.id AND item4.id1 = ans_total2.id1 ".
		" AND item4.id2 = ans_total2.id2 AND item4.id3 = ans_total2.id3 ".
		" AND item4.id4 = ans_total2.id3 AND item4.id = item3.id ".
		" AND item4.id1 = item3.id1 AND item4.id2 = item3.id2 AND item4.id3 = item3.id3 ".
		"GROUP BY ans_total2.id,ans_total2.id1,ans_total2.id2,ans_total2.id3,ans_total2.id4 ".
		"ORDER BY ans_total2.id,no1,no2,no3,no4";
//		"ORDER BY ans_total2.id,ans_total2.id1,ans_total2.id2,ans_total2.id3,ans_total2.id4";

	$rs = mysqli_query (  $db ,$sql );
	while ( $fld = mysqli_fetch_object ( $rs ) ) {
		if ( $fld->avg_point <= 1 ) {
			// MOD 2008/05/27
			//echo $fld->id.",".$fld->id1.",".$fld->id2.",".$fld->id3.",".$fld->id4.",1,".$fld->avg_point.",\"".$fld->question."\"\n";
			// MOD START
//			$ret .= $fld->id.",".$fld->id1.",".$fld->id2.",".$fld->id3.",".$fld->id4.",1,".$fld->avg_point.",\"".ereg_replace("\r|\n",'',$fld->name)."\"\n";
			$ret .= $fld->id . "," . $fld->no1 . "," . $fld->no2 . "," . $fld->no3 . "," . $fld->no4 . ",1," . $fld->avg_point . ",\"" . preg_replace("/\r|\n/", '', $fld->name) . "\"\n";

			// MOD END
		}
	}
	// ADD 2008/05/27
	return $ret;
	// ADD END
}


	// CSVファイル出力
	if ($_POST['ftype'] == 'csv_a' || $_POST['ftype'] == 'csv_h' || $_POST['ftype'] == 'csv_w' ||
		$_POST['ftype'] == 'csv_t' || $_POST['ftype'] == 'csv_wt' ||
		$_POST['ftype'] == 'recom_h' || $_POST['ftype'] == 'recom_w' ) {

		$file_name = createFilaName();	// ファイル名作成

		$csv = $file_name.".csv";

		// ヘッダー出力
		header ( "Cache-Control: public" );
		header ( "Pragma: public" );
		header ( "Content-Type: text/octet-stream" );
		header ( "Content-Disposition: attachment; filename=".$csv );

		if ( $_POST['ftype'] == 'csv_t' || $_POST['ftype'] == 'csv_wt' ) {
			// MOD 2008/05/27
			// CreateCSV_textonly ( $file_name );	// テキスト回答CSVデータ作成
			// MOD START
			echo CreateCSV_textonly ( $file_name );	// テキスト回答CSVデータ作成
			// MOD END
		} elseif ( $_POST['recom_h'] || $_POST['recom_w'] ) {
			// MOD 2008/05/27
			// recom_csv ( $file_name );
			// MOD START
			echo recom_csv ( $file_name );
			// MOD END
		} else {
			// MOD 2008/05/27
			// createCSVData ( $file_name );	// CSVデータ作成
			// MOD START
			echo createCSVData ( $file_name );	// CSVデータ作成
			// MOD END
		}
	// ADD 2008/05/26 全CSV
	} elseif ($_POST['ftype'] == 'include') {
  // ADD END
	} elseif ( $_POST['ftype'] == 'pdf_a' ) {	// PDFファイル出力

		exec ( 'rm -f ./pdf/*.pdf' );	// PDFファイル削除

		// ファイル名作成
		$file_name = createFilaName();
		$zip = $file_name.".zip";
		$user_list = getUserList($file_name);	// ユーザ一覧取得
		$year = getYear();
		$ave_arr = array();	// 平均(構造、過程、アウトカム)

		// 全国平均取得
		for ( $i = 1;$i <= 3;$i++ ) {
			$ave_arr[$i] = getNowAverage ( $year ,$i );
		}

		// ユーザ毎にPDF取得
		for ( $i = 0;$i < count ( $user_list );$i++ ) {
			$pdf = new PDF;
			$type_no = getTypeNo ( substr ( $user_list[$i] ,14 ) );

			// チャート作成
			$pdf->SaveChart ( $user_list[$i] ,$ave_arr[$type_no] );

		}

		downloadZip ( $zip );	// ZIPファイルダウンロード


	} elseif ( $_POST['PDF_ID'] ) {	// PDFファイル出力(ID指定)

		exec ( 'rm -f ./pdf/*.pdf' );	// PDFファイル削除

		$split_id = preg_split ( '/-/' ,$_POST['uid'] );



		$year = $split_id[0];
		$type = getTypeNo ( $split_id[4] );
		$zip = $_POST['uid'].".zip";

		$ave_arr = getNowAverage ( $year ,$type );	// 全国平均取得

		$pdf=new PDF;

		$pdf->SaveChart ( $_POST['uid'] ,$ave_arr );	// PDF保存

		$_POST['uid'] = NULL;

		downloadZip($zip);	// ZIPファイルダウンロード


	} else {

		echo "unkown error.";


	}


?>
