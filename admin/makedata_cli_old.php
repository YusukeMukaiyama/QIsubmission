#!/usr/local/bin/php
<?php

/*
  makedata.php(CLI版)


arg)
  $argv[1]  $_REQUEST['ftype']
  $argv[2]  $_REQUEST['pref1']  $_REQUEST['pref2']
  $argv[3]  $_REQUEST['hos1'] $_REQUEST['hos2']
  $argv[4]  $_REQUEST['ward']

ex)
  makedata_cli.php pdf_w 02 0001 01


*/

require_once __DIR__ . '/../lib/chart_lib.php';
// MOD 2008/06/05
//require_once ( "../lib/chart_lib_hosp.php" );
//require_once ( "../lib/chart_lib_ward.php" );
// MOD START
require_once __DIR__ . '/../lib/chart_lib_hosp2.php';
require_once __DIR__ . '/../lib/chart_lib_ward2.php';

$_POST['ftype'] = "include";
require_once ( "makedata.php" );
// MOD END

/*-------------------------------------------------------------------------------------------------
file2zip
  概要：生成したPDFファイルをZIPする
  引数：$zip  zipファイル名
  戻値：なし
-------------------------------------------------------------------------------------------------*/
function file2zip ( $filename )
{
  exec ( 'rm -f ./pdf_bat/*.zip' ); // zipファイル削除
  exec ( 'rm -f ./dl/*' );
  exec ( '/usr/local/bin/zip ./dl/'.$filename.' ./pdf_bat/*.pdf' ); // PDFファイルをZIP圧縮
  exec ( 'rm -f ./pdf_bat/*.pdf' ); // PDFファイル削除
}

/*-------------------------------------------------------------------------------------------------
dir2zip
  概要：生成したPDFファイルをZIPする
  引数：$zip  zipファイル名
  戻値：なし
-------------------------------------------------------------------------------------------------*/
// MOD 2008/06/05
//function dir2zip ( $filename )
//{
//  exec ( '/usr/local/bin/zip -r ./pdf_bat/'.$filename.' ./pdf_bat/' );
//}
// MOD START
function dir2zip ( $filename, $mode = 'ui' )
{
  if($mode == 'cron'){
    exec ( '/usr/local/bin/zip -r ./cron/pdf.zip ./pdf_bat/' );
  }else{
    exec ( 'rm -f ./dl/*' );
    exec ( '/usr/local/bin/zip -r ./pdf_bat/'.$filename.' ./pdf_bat/' );
  }
}
// MOD END
// ADD 2008/05/27 CSV対応
function csvdir2zip ( $filename , $mode = 'ui' )
{
  if($mode == 'cron'){
    exec ( '/usr/local/bin/zip -r ./cron/csv.zip ./csv_bat/' );
  }else{
    exec ( 'rm -f ./dl/*' );
    exec ( '/usr/local/bin/zip -r ./dl/'.$filename.' ./csv_bat/' );
  }
}
// ADD END

// ------------------------------------------------------------------------------------------------
// 一括PDF作成    pdf_all
//      year.zip
//      year_h.zip
// 病院単位PDF作成  pdf_h
//      year-pref1-hos1.zip
// 病棟単位PDF作成  pdf_w
//      year-pref2-hos2-ward.zip
// ------------------------------------------------------------------------------------------------

// データベース接続
$db = Connection::connect();	// データベース接続

// 実行中プロセスが無いか確認
  $sql = "SELECT * FROM process";
  $rs = mysqli_query ( $db, $sql  );
  if ( mysqli_num_rows ( $rs ) ) exit ( "STOP:NOW WORKING." );

// プロセスの記録
  $sql = "INSERT INTO process(pid) VALUES(".posix_getpid().")";
  mysqli_query ( $db, $sql  );

// 年度取得
  $rs = mysqli_query ( $db, "SELECT year FROM year"  );
  if ( $fld = mysqli_fetch_object ( $rs ) ) {
    $year = $fld->year;
  }
  mysqli_free_result ( $rs );

// PDFファイル削除
  // exec ( "rm -f ./pdf_bat/*.pdf" );

  // ディレクトリごと削除、ディレクトリを再作成
  exec ( "rm -fR ./pdf_bat/" );
  mkdir ( "./pdf_bat/" );
  chmod ( "./pdf_bat/", 0777 );

  if ( $argv[1] == "pdf_all" ) {

    // 病院ディレクトリ作成
    $sql = "SELECT SUBSTRING(uid,4,7)AS hospital FROM usr WHERE uid LIKE '".$year."%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,4,7) ORDER BY SUBSTRING(uid,4,7)";
    // ADD 2008/05/26 進行状況
    $arr_hospital = array();
    $hospital_count = 1;
    // ADD END
    $rs = mysqli_query ( $db, $sql  );
    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      // ADD 2008/05/26 進行状況
      $arr_hospital[$fld->hospital] = $hospital_count;
      $hospital_count ++;
      // ADD END
      if ( !file_exists ( "./pdf_bat/".$fld->hospital ) ) {
        mkdir ( "./pdf_bat/".$fld->hospital ) or die ( "SYSTEM ERROR." );
        chmod ( "./pdf_bat/".$fld->hospital, 0777 ) or die ( "SYSTEM ERROR." );
      }

      // 病棟ディレクトリ作成
      $sql2 = "SELECT SUBSTRING(uid,12,2)AS ward FROM usr WHERE uid LIKE '".$year."-".$fld->hospital."%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,12,2) ORDER BY SUBSTRING(uid,12,2)";

      $rs2 = mysqli_query ( $db, $sql2  );
      while ( $fld2 = mysqli_fetch_object ( $rs2 ) ) {
        if ( !file_exists ( "./pdf_bat/".$fld->hospital."/".$fld2->ward ) ) {
          mkdir ( "./pdf_bat/".$fld->hospital."/".$fld2->ward );
          chmod ( "./pdf_bat/".$fld->hospital."/".$fld2->ward, 0777 );
        }
      }
    }

    // 対象ユーザ抽出
    $sql = "SELECT uid FROM usr WHERE uid LIKE '".$year."%' AND comp='1' AND del='1' ORDER BY uid";
    $rs = mysqli_query ( $db, $sql  );
    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      $arr_usr[] = $fld->uid;
    }

    // 全国平均取得(構造、過程、アウトカム)
    $avg_point = array();
    for ( $i = 1;$i <= 3;$i++ ) {
      $avg_point[$i] = getNowAverage ( $year ,$i );
    }

    // 全PDF作成
    $hosp = "";
    $ward = "";
    for ( $i = 0;$i < sizeof ( $arr_usr );$i++ ) {

      if ( $hosp != substr ( $arr_usr[$i], 3, 7 ) ) {
        // ADD 2008/05/26 進行状況
        if ( !file_exists ( "./status" ) ) {
          mkdir ( "./status" ) or die ( "SYSTEM ERROR." );
          chmod ( "./status", 0777 ) or die ( "SYSTEM ERROR." );
        }
        $fno = fopen("./status/status", 'w');
        fwrite($fno, "現在PDF生成中です。(" . sprintf("%d",$arr_hospital[$hosp] / $hospital_count * 100 )."%完了)<!-- ". $arr_hospital[$hosp] ." / ". $hospital_count ." -->");
        fclose($fno);
        chmod ( "./status/status", 0777 );
        // ADD END

        $hosp = substr ( $arr_usr[$i], 3, 7 );  // 都道府県-病院

        // 病院集計
        $pdf = new PDF_Hosp;
        $pdf->SaveChart ( $year."-".$hosp, "./pdf_bat/".$hosp."/" );

        $ward = substr ( $arr_usr[$i], 11, 2 ); // 病棟

        // 病棟集計
        $pdf = new PDF_Ward;
        $pdf->SaveChart ( $year."-".$hosp."-".$ward, "./pdf_bat/".$hosp."/".$ward."/" );

      } elseif ( $ward != substr ( $arr_usr[$i], 11, 2 ) ) {

        $ward = substr ( $arr_usr[$i], 11, 2 );

        // 病棟集計
        $pdf = new PDF_Ward;
        $pdf->SaveChart ( $year."-".$hosp."-".$ward, "./pdf_bat/".$hosp."/".$ward."/" );

      }

      // ユーザ毎にPDF作成
// /*** ユーザ毎PDF生成省略 ****
      $arr_uid = preg_split('/-/', $arr_usr[$i]);
      if(is_numeric($arr_uid[4]) && $arr_uid[4] > 50){
      }else{
        $pdf = new PDF;
        $type_no = getTypeNo ( substr ( $arr_usr[$i], 14 ) );
        $pdf->SaveChart ( $arr_usr[$i] ,$avg_point[$type_no], "./pdf_bat/".$hosp."/".$ward."/" ); // チャート作成
      }

// ****************************/
    }

    // MOD 2008/05/26 全CSVダウンロード
    // dir2zip ( $year );
    // MOD START
    dir2zip ( $year ,$argv[2]);
    exec ( "rm -fR ./status/" );
    mkdir ( "./status/" );
    chmod ( "./status/", 0777 );
    // MOD END
    // ADD 2008/05/26 全CSVダウンロード
  } elseif ( $argv[1] == "csv_all" ) {
    // ディレクトリごと削除、ディレクトリを再作成
    exec ( "rm -fR ./csv_bat/" );
    mkdir ( "./csv_bat/" );
    chmod ( "./csv_bat/", 0777 );

    // 病院ディレクトリ作成
    $sql = "SELECT SUBSTRING(uid,4,7)AS hospital FROM usr WHERE uid LIKE '".$year."%' AND comp='1' AND del='1' GROUP BY SUBSTRING(uid,4,7) ORDER BY SUBSTRING(uid,4,7)";
    $rs = mysqli_query ( $db, $sql  );
    
    $arr_hospital = array();
    $hospital_count = 1;
    while ( $fld = mysqli_fetch_object ( $rs ) ) {

      $arr_hospital[$fld->hospital] = $hospital_count;
      $hospital_count ++;
    }
    foreach($arr_hospital as $key => $val){
      if ( !file_exists ( "./status" ) ) {
        mkdir ( "./status" ) or die ( "SYSTEM ERROR." );
        chmod ( "./status", 0777 ) or die ( "SYSTEM ERROR." );
      }
      $fno = fopen("./status/status", 'w');
      fwrite($fno, "現在CSV生成中です。(" . sprintf("%d",$val / $hospital_count * 100 )."%完了)" );
      fclose($fno);
      chmod ( "./status/status", 0777 );
/* TODO
      
      $csv = createCSVData ( $year . "-" . $key );  // CSVデータ作成
      $fno = fopen("./csv_bat/" . $year . "-" . $key . ".csv", 'w');
      fwrite($fno, mb_convert_encoding($csv,'sjis-win','eucjp-win'));
      fclose($fno);

      $csv = CreateCSV_textonly( $year . "-" . $key );  // CSVデータ作成
      $fno = fopen("./csv_bat/" . $year . "-" . $key . "-textonly.csv", 'w');
      fwrite($fno, mb_convert_encoding($csv,'sjis-win','eucjp-win'));
      fclose($fno);
      
*/
      $sql = "SELECT SUBSTRING(uid,4,10)AS hospital FROM usr                ".
             "WHERE uid LIKE '".$year."-".$key."%' AND comp='1' AND del='1' ".
             "GROUP BY SUBSTRING(uid,4,10) ORDER BY SUBSTRING(uid,4,10)     ";
      $rs = mysqli_query ( $db, $sql  );

      while ( $fld = mysqli_fetch_object ( $rs ) ) {
        $csv = recom_csv( $year . "-" . $fld->hospital ); // CSVデータ作成
        $fno = fopen("./csv_bat/" . $year . "-" . $fld->hospital . "-recom.csv", 'w');
        fwrite($fno, mb_convert_encoding($csv,'sjis-win','eucjp-win'));
        fclose($fno);
      }
      /*
      $csv = recom_csv( $year . "-" . $key ); // CSVデータ作成
      $fno = fopen("./csv_bat/" . $year . "-" . $key . "-recom.csv", 'w');
      fwrite($fno, mb_convert_encoding($csv,'sjis-win','eucjp-win'));
      fclose($fno);
      */
    }
    
    csvdir2zip ( $year. "-csv" ,$argv[2]);
    exec ( "rm -fR ./status/" );
    mkdir ( "./status/" );
    chmod ( "./status/", 0777 );

    // ADD END
  } else {

    if ( $argv[1] == 'pdf_h' || $argv[1] == 'pdf_w' ) { // PDFファイル出力

      // ファイル名
      if ( $argv[1] == 'pdf_h' ) {  // 病院毎
        $usr = $year."-".$argv[2]."-".$argv[3];
      } elseif ( $argv[1] == 'pdf_w' ) {  // 病棟毎
        $usr = $year."-".$argv[2]."-".$argv[3]."-".$argv[4];
      }

      // 対象ユーザ抽出
      $sql = "SELECT uid FROM usr WHERE uid LIKE '".$usr."%' AND comp='1' AND del='1' ORDER BY uid";
      $rs = mysqli_query ( $db, $sql  );
      while ( $fld = mysqli_fetch_object ( $rs ) ) {
        $arr_usr[] = $fld->uid;
      }

      // 全国平均取得(構造、過程、アウトカム)
      $avg_point = array();
      for ( $i = 1;$i <= 3;$i++ ) {
        $avg_point[$i] = getNowAverage ( $year ,$i );
      }

      // ユーザ毎にPDF作成
      for ( $i = 0;$i < sizeof ( $arr_usr );$i++ ) {
        $pdf = new PDF;
        $type_no = getTypeNo ( substr ( $arr_usr[$i] ,14 ) );
        $pdf->SaveChart ( $arr_usr[$i] ,$avg_point[$type_no] ,"./pdf_bat/" ); // チャート作成
      }

      // ZIPファイル化
      file2zip ( $usr.".zip" );

    // --------------------------------------------------------------------------------------------
    // 病院単位集計PDF作成  pdf_ht
    //      year-pref1-hos1_total.zip
    // --------------------------------------------------------------------------------------------
    } elseif ( $argv[1] == 'pdf_ht' ) { // PDFファイル出力(病院単位での集計)

      // ファイル名
      $file_name = $year."-".$argv[2]."-".$argv[3];

      // PDF作成
      $pdf = new PDF_Hosp;
      $pdf->SaveChart ( $file_name );

      // ZIPファイル化
      file2zip ( $file_name."_total.zip" );


    // --------------------------------------------------------------------------------------------
    // 病棟単位集計PDF作成  pdf_wt
    //      year-pref2-hos2-ward_total.zip
    // --------------------------------------------------------------------------------------------
    } elseif ( $argv[1] == 'pdf_wt' ) { // PDFファイル出力(病棟単位での集計)

      // ファイル名
      $file_name = $year."-".$argv[2]."-".$argv[3]."-".$argv[4];

      // PDF作成
      $pdf = new PDF_Ward;
      $pdf->SaveChart ( $file_name );

      // ZIPファイル化
      file2zip ( $file_name."_total.zip" );

    // --------------------------------------------------------------------------------------------
    // 病院単位集計CSV作成  csv_h
    //      year-pref1-hos1_total.zip
    // --------------------------------------------------------------------------------------------
    } elseif ( $argv[1] == 'csv_h' ||$argv[1] == 'csv_t' ||$argv[1] == 'recom_h' ||
        $argv[1] == 'csv_w' ||$argv[1] == 'csv_wt' ||$argv[1] == 'recom_w' ) {

      $fno = fopen("./status/status", 'w');
      fwrite($fno, "現在CSV生成中です。<br>しばらくこのままでお待ちください" );
      fclose($fno);
      chmod ( "./status/status", 0777 );

      $filename = $year . "-";
      if( $argv[1] == 'csv_h' ||$argv[1] == 'csv_t' ||$argv[1] == 'recom_h'){
        $file_name = $year."-".$argv[2]."-".$argv[3];
      }else{
        $file_name = $year."-".$argv[2]."-".$argv[3]."-".$argv[4];
      }
      
      if($argv[1] == 'csv_h' ||$argv[1] == 'csv_w'){
        $csv = createCSVData ( $file_name );  // CSVデータ作成
      }
      if($argv[1] == 'csv_t' ||$argv[1] == 'csv_wt'){
        $csv = CreateCSV_textonly( $file_name );  // CSVデータ作成
      }
      if($argv[1] == 'recom_h' ||$argv[1] == 'recom_w'){
        $csv = recom_csv( $file_name ); // CSVデータ作成
      }

      exec ( 'rm -f ./dl/*' );

      $fno = fopen("./dl/" . $file_name . ".csv", 'w');
      fwrite($fno, mb_convert_encoding($csv,'sjis-win','eucjp-win'));
      fclose($fno);
    
      exec ( "rm -fR ./status/" );
      mkdir ( "./status/" );
      chmod ( "./status/", 0777 );

    } else {

      echo "warn:unknown error!\n";

    }

  }


  // 実行中プロセスの記録を削除
  $sql = "DELETE FROM process";
  mysqli_query ( $db, $sql  );


?>
