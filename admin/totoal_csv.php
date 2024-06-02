<?php
/******************************************************************
#!/usr/local/bin/php

  totoal_csv.php
  集計データ ( CSV )ダウンロード
                 ( C )2005-2006, University of Hyougo.
******************************************************************/

// CSVファイル名
function createFilaName ( $year, $type )
{

  switch ( $type ) {
  case 1:
    $filename = $year."-structure-ttl.csv";
    break;
  case 2:
    $filename = $year."-process-ttl.csv";
    break;
  case 3:
    $filename = $year."-outcome-ttl.csv";
    break;
  case 10:
    $filename = $year."-ttl-avg.csv";
    break;
  case 20:
    $filename = $year."-ttl-enq.csv";
    break;
  default:
    die ( "error!" );
    break;
  }
  return $filename;

}

//===================================================================================================================================================
// $type=1/2/3
// カテゴリ別集計
function create_csv ( $type )
{
  global $db, $year, $utype;

  // アンケートカテゴリ取得
  $rs = mysqli_unbuffered_query ( "SELECT enquete.id FROM enquete WHERE enquete.id Like '".$type."%' GROUP BY enquete.id ORDER BY enquete.id", $db );
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $category[] = $fld->id;
  }

  // ID
  $tmp = "\"ID\",,,,";  // EXCELが不正なSYLK形式と判断するため、\"\"でデータを開始
  // ( 1:構造 / 2:過程 / 3:アウトカム )カテゴリ別のCSV集計データ
  if($type == 1 || $type == 2) {
    $tmp .= ",\"承諾\"";
  }

  // QIヘッダ
  for ( $id1 = 1;$id1 < 7;$id1++ ) {
    $sql1 = "SELECT item4.id1, item4.id2, item4.id3, item4.id4, item1.no AS no1, item2.no AS no2, item3.no AS no3, item4.no AS no4 "
           ." FROM item1 "
           ." LEFT JOIN item2 ON item2.id=item1.id AND item2.id1=item1.id1 "
           ." LEFT JOIN item3 ON item3.id=item2.id AND item3.id1=item2.id1 AND item3.id2=item2.id2 "
           ." LEFT JOIN item4 ON item4.id=item3.id AND item4.id1=item3.id1 AND item4.id2=item3.id2 AND item4.id3=item3.id3 "
           ." WHERE item1.id=".$type." AND item1.id1=".$id1." "
           ." ORDER BY item1.no, item2.no, item3.no, item4.no";
    $rs = mysqli_unbuffered_query ( $sql1, $db );
//    $rs = mysqli_unbuffered_query ( "SELECT id1,id2,id3,id4 FROM item4 WHERE id=".$type." AND id1=".$id1." ORDER BY id1,id2,id3,id4", $db );
    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      $tmp .= ",".$utype[$type].$fld->no1.$fld->no2.$fld->no3.$fld->no4;
//      $tmp .= ",".$utype[$type].$fld->id1.$fld->id2.$fld->id3.$fld->id4;
    }
    $tmp .= ",".$utype[$type].$id1."TTL";
    mysqli_free_result ( $rs );
  }
  $tmp .= ",".$utype[$type]."TTL";

  // アンケートヘッダ
  for ( $i = 0;$i < sizeof ( $category );$i++ ) {
    $sql = "SELECT id1 FROM enquete WHERE id=".$category[$i]." ORDER BY id1 ASC";
    $rs = mysqli_unbuffered_query ( $sql, $db );
    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      $tmp .= ",".$category[$i].$fld->id1;
    }
    mysqli_free_result ( $rs );
  }
  $sql = "select enq_usr_ans_ex.id as id ,enq_usr_ans_ex.id1 as id1 ,enq_usr_ans_ex.name as name ".
        " from usr,enq_usr_ans_ex ".
        " where usr.uid = enq_usr_ans_ex.uid AND usr.id=".$type." AND usr.uid LIKE '".$year."-%' AND usr.comp='1' AND usr.del='1' ".
        " and enq_usr_ans_ex.name != '' ".
        " group by enq_usr_ans_ex.id,enq_usr_ans_ex.id1 order by enq_usr_ans_ex.id,enq_usr_ans_ex.id1 ";
  $rs = mysqli_unbuffered_query ( $sql, $db );
  $arr_ex_fld = array();
  $str_ex_fld = "";
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $tmp .= ",".$fld->name;
    $arr_ex_fld[] = array('id' => $fld->id, 'id1' => $fld->id1);
    if($str_ex_fld == ""){
      $str_ex_fld = $fld->id1;
    }else{
      $str_ex_fld .= ",".$fld->id1;
    }
  }
  mysqli_free_result ( $rs );
  
  $tmp .= "\r\n";

  echo $tmp;
//error_log ( $tmp, 3, "/usr/home/kango2/public_html/07-outcome-ttl.csv");

  if(count($arr_ex_fld) > 0){
    $sql = "select enq_usr_ans_ex.id as id, enq_usr_ans_ex.id1 as id1,enq_usr_ans_ex.uid as uid, enq_usr_ans_ex.ans as ans ".
          " from usr,enq_usr_ans_ex ".
          " where usr.uid = enq_usr_ans_ex.uid AND usr.id=".$type." AND usr.uid LIKE '".$year."-%' AND usr.comp='1' AND usr.del='1' ".
          " and enq_usr_ans_ex.id1 in(".$str_ex_fld.")".
          " order by enq_usr_ans_ex.uid, enq_usr_ans_ex.id, enq_usr_ans_ex.id1";
    $rs_ex = mysqli_query ( $db , $sql  );
    if ( mysqli_num_rows ( $rs_ex ) ) {
      $fld_ex = mysqli_fetch_object ( $rs_ex );
    }
  }

  $tmp = "";

  $arr_point_list = array();
  $sql = "SELECT usr.id as usr_id, usr.uid as usr_uid, usr.cooperation as cooperation, usr_ans.id1 as usr_ans_id1, usr_ans.id2 as usr_ans_id2, usr_ans.id3 as usr_ans_id3, usr_ans.id4 as usr_ans_id4".
        ", usr_ans.ans as usr_ans_ans, usr_ans.point as usr_ans_point".
        " FROM usr ,usr_ans ".
        " LEFT JOIN item4 ON item4.id=usr_ans.id AND item4.id1=usr_ans.id1 AND item4.id2=usr_ans.id2 AND item4.id3=usr_ans.id3 AND item4.id4=usr_ans.id4 ".
        " LEFT JOIN item3 ON item3.id=item4.id AND item3.id1=item4.id1 AND item3.id2=item4.id2 AND item3.id3=item4.id3 ".
        " LEFT JOIN item2 ON item2.id=item3.id AND item2.id1=item3.id1 AND item2.id2=item3.id2 ".
        " LEFT JOIN item1 ON item1.id=item2.id AND item1.id1=item2.id1 ".
        " WHERE usr.uid = usr_ans.uid AND usr.id=".$type." AND usr.uid LIKE '".$year."-%' AND usr.comp='1' AND usr.del='1' AND item4.no IS NOT NULL AND item3.no IS NOT NULL AND item2.no IS NOT NULL AND item1.no IS NOT NULL ".
        " ORDER BY usr.uid, item1.no ASC, item2.no ASC, item3.no ASC, item4.no ASC ";
//        " ORDER BY usr.uid, usr_ans.id1 ASC, usr_ans.id2 ASC, usr_ans.id3 ASC, usr_ans.id4 ASC ";
  $rs = mysqli_query ( $db , $sql  ) or die ( $sql );
  $is_top = 1;
  $usr_uid = "";
  $TTL = 0;
  $IDTTL = array(0,0,0,0,0,0);
  $LIST = array("","","","","","");
  
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    if($fld->usr_uid != $usr_uid){
      if($is_top == 0){
        for ( $id1 = 1;$id1 < 7;$id1++ ) {
          $tmp .= $LIST[$id1].",".$IDTTL[$id1];
        }
        $tmp .=  "," . $TTL;
        // アンケート集計
        for ( $i = 0;$i < sizeof ( $category );$i++ ) {
          $rs1 = mysqli_unbuffered_query ( "SELECT ans FROM enq_usr_ans WHERE id=".$category[$i]." AND uid='".$usr_uid."' ORDER BY id1 ASC", $db );
          while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
            $tmp .= ",".ereg_replace ( "[\r\n\]", "", $fld1->ans );
          }
          mysqli_free_result ( $rs1 );
        }
        foreach($arr_ex_fld as $key => $val){
          if($usr_uid == $fld_ex->uid and $val['id'] == $fld_ex->id and $val['id1'] == $fld_ex->id1 ){
            $tmp .= ",".$fld_ex->ans;
            $fld_ex = mysqli_fetch_object ( $rs_ex );
          }else{
            $tmp .= ",";
          }
        }

        $tmp .= "\r\n";
        echo $tmp;
      }
      $tmp = "=\"".str_replace ( "-", "\",=\"", $fld->usr_uid )."\"";
      // 看護師長、看護師の場合、研究への協力の項目を追加
      if($type == 1 || $type == 2) {
          if($fld->cooperation == "同意する" || $fld->cooperation == "諾") {
              $cooperation = "1";
          } else {
              $cooperation = "0";
          }
          $tmp .= ",=\"".$cooperation."\"";
      }
      // 回答データを集計
      $TTL = 0;
      $IDTTL = array(0,0,0,0,0,0);
      $LIST = array("","","","","","");
      $usr_uid = $fld->usr_uid;
    }
    if(1 <= $fld->usr_ans_id1 and $fld->usr_ans_id1 <= 6){
      $TTL += $fld->usr_ans_point;
      $IDTTL[$fld->usr_ans_id1] += $fld->usr_ans_point;
      if($fld->usr_ans_point === '0' && ($fld->usr_ans_ans === '0' || !is_numeric($fld->usr_ans_ans))){
        $LIST[$fld->usr_ans_id1] .= ",\"\"";
      }else{
        $LIST[$fld->usr_ans_id1] .= "," . $fld->usr_ans_point;
      }
    }
    $is_top = 0;
  }
  for ( $id1 = 1;$id1 < 7;$id1++ ) {
    $tmp .= $LIST[$id1].",".$IDTTL[$id1];
  }
  $tmp .=  "," . $TTL;
  // アンケート集計
  for ( $i = 0;$i < sizeof ( $category );$i++ ) {
    $rs1 = mysqli_unbuffered_query ( "SELECT ans FROM enq_usr_ans WHERE id=".$category[$i]." AND uid='".$usr_uid."' ORDER BY id1 ASC", $db );
    while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
      $tmp .= ",".ereg_replace ( "[\r\n\]", "", $fld1->ans );
    }
    mysqli_free_result ( $rs1 );
  }
  
  foreach($arr_ex_fld as $key => $val){
    if($usr_uid == $fld_ex->uid and $val['id'] == $fld_ex->id and $val['id1'] == $fld_ex->id1 ){
      $tmp .= ",".$fld_ex->ans;
      $fld_ex = mysqli_fetch_object ( $rs_ex );
    }else{
      $tmp .= ",";
    }
  }
  $tmp .= "\r\n";
  echo $tmp;

/*

  // ユーザ列挙
  $sql = "SELECT id,uid FROM usr WHERE id=".$type." AND uid LIKE '".$year."-%' AND comp='1' AND del='1' ORDER BY uid";
  $rs = mysqli_query ( $db , $sql  ) or die ( $sql );

  while ( $fld = mysqli_fetch_object ( $rs ) ) {

    $tmp = "=\"".str_replace ( "-", "\",=\"", $fld->uid )."\"";

    // 回答データを集計
    $TTL = 0;

    $rs1 = mysqli_unbuffered_query ( "SELECT id1,id2,id3,id4,ans,point FROM usr_ans WHERE uid='".$fld->uid."' ORDER BY id1 ASC,id2 ASC,id3 ASC,id4 ASC", $db );
    $arr_point_list = array();
    while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
      $arr_point_list[] = array('id1' => $fld1->id1, 'point' => $fld1->point);
    }
    mysqli_free_result ( $rs1 );
    for ( $id1 = 1;$id1 < 7;$id1++ ) {
      $IDTTL = 0;
      foreach($arr_point_list as $key => $val){
        if($val['id1'] != $id1){
          continue;
        }
        $tmp .= ",".$val['point'];  // 点数
        $IDTTL += $val['point'];  // 大項目計
        $TTL += $val['point'];  // 総合計
      }
      $tmp .= ",".$IDTTL; // 大項目計
    }
    $tmp .= ",".$TTL; // 総合計

    //
    //for ( $id1 = 1;$id1 < 7;$id1++ ) {
    //  $rs1 = mysqli_unbuffered_query ( "SELECT id1,id2,id3,id4,ans,point FROM usr_ans WHERE uid='".$fld->uid."' AND id1=".$id1." ORDER BY id1 ASC,id2 ASC,id3 ASC,id4 ASC", $db );
    //  $IDTTL = 0;
    //  while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
    //    //$tmp .= ",". ( $fld1->ans ? $fld1->point : "" );  // 点数
    //    $tmp .= ",".$fld1->point; // 点数
    //    $IDTTL += $fld1->point; // 大項目計
    //    $TTL += $fld1->point; // 総合計
    //  }
    //  $tmp .= ",".$IDTTL; // 大項目計
    //  mysqli_free_result ( $rs1 );
    //}
    //$tmp .= ",".$TTL; // 総合計
    //

    // アンケート集計
    for ( $i = 0;$i < sizeof ( $category );$i++ ) {
      $rs1 = mysqli_unbuffered_query ( "SELECT ans FROM enq_usr_ans WHERE id=".$category[$i]." AND uid='".$fld->uid."' ORDER BY id1 ASC", $db );
      while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
        $tmp .= ",".ereg_replace ( "[\r\n\]", "", $fld1->ans );
      }
      mysqli_free_result ( $rs1 );
    }
    $tmp .= "\r\n";

    echo $tmp;
//error_log ( $tmp, 3, "/usr/home/kango2/public_html/07-outcome-ttl.csv");

  }
*/
}

//===================================================================================================================================================
// $type=10
// 総合ファイル
function create_total_csv ( )
{
  global $db, $year, $utype;

  // データヘッダ
  // ID
  $tmp = "\"ID\",\"承諾\"";  // EXCELが不正なSYLK形式と判断するため、\"\"でデータを開始
  for ( $type = 1;$type < 4;$type++ ) {
    $tmp .= total_data_qi_header ( $type );
    $tmp .= total_data_enq_header ( $type );
  }
  // QIヘッダ
  for ( $id1 = 1;$id1 < 7;$id1++ ) {
    $rs = mysqli_query (  $db, "SELECT id1,id2,id3,id4 FROM item4 WHERE id=3 AND id1=".$id1." ORDER BY id1,id2,id3,id4" );
    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      $tmp .= ",".$utype[$type].$fld->id1.$fld->id2.$fld->id3.$fld->id4;
    }
  }
  $tmp .= "\r\n";


  echo $tmp;
//error_log ( $tmp, 3, "/usr/home/kango2/public_html/07-ttl-avg.csv");

  $tmp = "";

  // データ

  // 集計一時テーブルの初期化
  mysqli_unbuffered_query ( "TRUNCATE TABLE ans_total" ,$db );

  // 集計結果を一時テーブルに保存
  mysqli_unbuffered_query ("INSERT INTO ans_total(id,id1,uid,point) ".
    "SELECT usr_ans.id, usr_ans.id1, usr_ans.uid, Sum(usr_ans.point) AS sum_point ".
    "FROM (item4 INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) ".
    "AND (item4.id2 = usr_ans.id2) AND (item4.id = usr_ans.id) AND (item4.id1 = usr_ans.id1)) ".
    "INNER JOIN usr ON (usr_ans.id = usr.id) AND (usr_ans.uid = usr.uid) ".
    "WHERE usr.comp='1' AND usr.del='1' AND item4.qtype='1' AND usr_ans.uid LIKE '".$year."%' GROUP BY usr_ans.id, usr_ans.id1, usr_ans.uid", $db );

  // 病棟毎にデータを集計
  $sql = "SELECT LEFT(uid,13) AS ward, SUM(CASE WHEN id=1 OR id=2 THEN 1 ELSE 0 END) AS usr_cnt, SUM(CASE WHEN cooperation='同意する' OR cooperation='諾' THEN 1 ELSE 0 END) AS cooperation_cnt FROM usr WHERE uid LIKE '".$year."-%' AND comp='1' AND del='1' GROUP BY LEFT(uid,13) ORDER BY uid";
  $rs = mysqli_query ( $db , $sql  );
  while ( $fld = mysqli_fetch_object ( $rs ) ) {

    $tmp .= $fld->ward;
    if($fld->usr_cnt > 0) {
      $tmp .= ",".number_format(($fld->cooperation_cnt / $fld->usr_cnt), 2);
    } else {
      $tmp .= ",0";
    }
    for ( $type = 1;$type < 4;$type++ ) {
      // 質問
      $tmp .= total_data_qi_data ( $type, $fld->ward );
      // アンケート
      $tmp .= total_data_en_data ( $type, $fld->ward );
    }


    // 回答データを集計
    //$TTL = 0;
    for ( $id1 = 1;$id1 < 7;$id1++ ) {
      $sql = "SELECT usr_ans.id1,usr_ans.id2,usr_ans.id3,usr_ans.id4,AVG(usr_ans.point)AS avg_point ".
        "FROM usr_ans,usr WHERE usr.uid = usr_ans.uid and usr.uid LIKE '".$fld->ward."%' AND usr_ans.id=".OUTCOME." AND usr_ans.id1=".$id1.
        " GROUP BY usr_ans.id1,usr_ans.id2,usr_ans.id3,usr_ans.id4 ORDER BY usr_ans.id1 ASC,usr_ans.id2 ASC,usr_ans.id3 ASC,usr_ans.id4 ASC";
      $rs1 = mysqli_query ( $db , $sql  );
      while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
        $tmp .= ",".number_format ( $fld1->avg_point, 2 );
      }
    }
    $tmp .= "\r\n";

    echo $tmp;
//error_log ( $tmp, 3, "/usr/home/kango2/public_html/07-ttl-avg.csv");

    $tmp = "";

  }

}


// 総合ファイルQIヘッダ部
function total_data_qi_header ( $type )
{
  global $db, $year, $utype;

  // QIヘッダ
  $rs = mysqli_unbuffered_query ( "SELECT id1 FROM item1 WHERE id=".$type, $db );
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $tmp .= ",".$utype[$type].$fld->id1."TTL";
  }
  $tmp .= ",".$utype[$type]."TTL";  // TTL
  mysqli_free_result ( $rs );

  return $tmp;

}


// 総合ファイルアンケートヘッダ部
function total_data_enq_header ( $type )
{
  global $db, $year, $utype;

  // アンケートカテゴリ取得
  $rs = mysqli_unbuffered_query ( "SELECT enquete.id FROM enquete GROUP BY enquete.id HAVING enquete.id LIKE '".$type."%' ORDER BY enquete.id", $db );
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $category[] = $fld->id;
  }
  mysqli_free_result ( $rs );

  // アンケートヘッダ
  for ( $i = 0;$i < sizeof ( $category );$i++ ) {
    $rs = mysqli_unbuffered_query ( "SELECT id1 FROM enquete WHERE id=".$category[$i]." AND csv=1 ORDER BY id1 ASC", $db );
    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      $tmp .= ",".$category[$i].$fld->id1;
    }
    mysqli_free_result ( $rs );
  }
  return $tmp;

}


// 総合ファイルアンケート集計部
function total_data_en_data ( $type, $ward )
{
  global $db, $year, $utype;

  // アンケートカテゴリ取得
  $rs = mysqli_unbuffered_query ( "SELECT enquete.id FROM enquete GROUP BY enquete.id HAVING enquete.id LIKE '".$type."%' ORDER BY enquete.id", $db );
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $category[] = $fld->id;
  }
  mysqli_free_result ( $rs );

  // アンケート
  for ( $i = 0;$i < sizeof ( $category );$i++ ) {
    $rs1 = mysqli_query (  $db , "SELECT id1 FROM enquete WHERE csv=1 AND id=".$category[$i]." ORDER BY id1 ASC");
    while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) {
      // 統合 ( csv=1 )
      $rs2 = mysqli_unbuffered_query (
        "SELECT AVG(enq_usr_ans.ans)AS avg_ans FROM usr,enq_usr_ans ".
        "WHERE enq_usr_ans.uid = usr.uid AND ".
        "enq_usr_ans.id=".$category[$i]." AND enq_usr_ans.id1=".$fld1->id1." AND ".
        "enq_usr_ans.uid LIKE '".$ward."-%' AND usr.comp=1 AND usr.del=1"
        , $db );
      while ( $fld2 = mysqli_fetch_object ( $rs2 ) ) {
        $tmp .= ",".$fld2->avg_ans;
      }
      mysqli_free_result ( $rs2 );
    }
    mysqli_free_result ( $rs1 );
  }
  return $tmp;

}


// 総合ファイルQIデータ集計部
function total_data_qi_data ( $type, $ward )
{
  global $db, $year, $utype;

  $rs = mysqli_query ( $db , "SELECT id1 FROM item1 WHERE id=".$type." ORDER BY id1 ASC" );
  while ( $fld = mysqli_fetch_object ( $rs ) ) {

    $rs1 = mysqli_query ( $db , "SELECT ROUND(AVG(point),1)AS avg_point FROM ans_total WHERE id='".$type."' AND id1='".$fld->id1."' AND uid LIKE '".$ward."%'" );
    if ( !mysqli_num_rows ( $rs1 ) ) { // 既存データなし
      $tmp .= ",";  // TTL
    } else {
      $fld1 = mysqli_fetch_object ( $rs1 );
      $tmp .= ",".$fld1->avg_point; // TTL
    }
    mysqli_free_result ( $rs1 );

  }
  mysqli_free_result ( $rs );

  $rs1 = mysqli_query (  $db  , "SELECT ROUND(AVG(point) * 6,1)AS avg_point FROM ans_total WHERE id='".$type."' AND uid LIKE '".$ward."%'");
  if ( !mysqli_num_rows ( $rs1 ) ) { // 既存データなし
    $tmp .= ",";  // TTL
  } else {
    $fld1 = mysqli_fetch_object ( $rs1 );
    $tmp .= ",".$fld1->avg_point; // TTL
  }
  mysqli_free_result ( $rs1 );

  return $tmp;

}


//===================================================================================================================================================
// $type=20
// テキスト回答アンケート一括ダウンロード
function create_total_enq ( )
{
  global $db, $year;

  $sql = 
    "SELECT item4.id1,item4.id2,item4.id3,item4.id4,".
    "item4.question,(item1.no)AS item1_no,(item2.no)AS item2_no,(item3.no)AS item3_no,(item4.no)AS item4_no ".
    "FROM (((category INNER JOIN item1 ON category.id = item1.id) ".
    "INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1)) ".
    "INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
    "INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id) ".
    "WHERE category.id=2 AND item4.qtype='2' ORDER BY item4.id1,item4.id2,item4.id3,item4.id4";

  $rs = mysqli_query ( $db , $sql  );
  while ( $fld = mysqli_fetch_object ( $rs ) ) { // アンケート
    echo "Q.".$fld->item1_no.".".$fld->item2_no.".".$fld->item3_no.".".$fld->item4_no." ".$fld->question."\r\n";
//error_log ( "Q.".$fld->item1_no.".".$fld->item2_no.".".$fld->item3_no.".".$fld->item4_no." ".$fld->question."\r\n", 3, "/usr/home/kango2/public_html/07-ttl-enq.csv");

    $rs1 = mysqli_unbuffered_query ( "SELECT usr_ans.uid,usr_ans.ans FROM usr_ans,usr WHERE usr.uid = usr_ans.uid and usr.uid LIKE '".$year."%' AND usr_ans.id='2' AND usr_ans.id1=".$fld->id1." AND usr_ans.id2=".$fld->id2." AND usr_ans.id3=".$fld->id3." AND usr_ans.id4=".$fld->id4." ORDER BY usr_ans.uid", $db );
    while ( $fld1 = mysqli_fetch_object ( $rs1 ) ) { // アンケート
      echo $fld1->uid.",\"".$fld1->ans."\"\r\n";
//error_log ( $fld1->uid.",\"".$fld1->ans."\"\r\n", 3, "/usr/home/kango2/public_html/07-ttl-enq.csv");

    }
    mysqli_free_result ( $rs1 );
  }

}

/*******************************************************************
getYear
  概要：現在の年度を取得します。
  引数：$db   データベースオブジェクト
  戻値：年度の配列
*******************************************************************/
function getYear()
{

  global $db;
  $sql = "SELECT year FROM year";
  $res = mysqli_query ( $db , $sql ,);
  $fld = mysqli_fetch_object ( $res );
  $year = $fld->year;
  return $year;
}

  require_once ( "setup.php" );

  $utype = array ( 1=>"S", 2=>"P", 3=>"O" );
  
  if(isset($argv[1])){
    $type = $argv[1];
    $year = '';
  }else{
    $year = $_REQUEST['year'];
    $type = $_REQUEST['type'];
  }
  $db = DB_CONNCET ( ); // データベース接続
  if($year == ''){
    $year = getYear();
  }
  
//  $year = "07";
//  $type = "10";



  // ファイル名作成
  $file_name = createFilaName ( $year, $type );

  // ヘッダー出力
  header ( "Cache-Control: public" );
  header ( "Pragma: public" );
  header ( "Content-Type: text/octet-stream" );
  header ( "Content-Disposition: attachment; filename=".$file_name );

  // CSVデータ出力
  if ( $type == 20 ) {  // アンケート総合データ

    create_total_enq ( );

  } elseif ( $type == 10 ) {  // 総合集計データ

    create_total_csv ( );

  } else {  // ( 1:構造 / 2:過程 / 3:アウトカム )カテゴリ別のCSV集計データ

    create_csv ( $type );

  }

  DB_DISCONNCET ( $db );  // データベース切断


?>
