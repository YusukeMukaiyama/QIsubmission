<?php
// エラーログ設定: 開発環境では有効、本番環境では無効またはログに記録
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
/*-------------------------------------------------------------------------------------------------
chart_lib.php
--ユーザ毎の結果PDF作成
-------------------------------------------------------------------------------------------------*/
require_once ( "../admin_new/setup.php" );
require_once('TCPDF-main/tcpdf.php');

$db = Connection::connect();	

class PDF extends TCPDF {
    protected $top_count = 6;
    protected $margin = 10;
    protected $str_point = array("全国平均", "今年の結果", "前年の結果", "前々年の結果");
    protected $title_font_size = 16;
    protected $cell_font_size = 10;
    protected $cell_header_height = 8;
    protected $cell_data_height = 8;
    protected $cell_title_width = 50;
    protected $cell_point_width = 30;
    protected $center_x = 90;
    protected $center_y = 170;
    protected $size = 40;
    protected $base_line_color = 192;
    protected $round_count = 4;
    protected $percent_font_size = 10;
    protected $item_font_size = 14;
    protected $data_line_color = array(array(192, 0, 0), array(0, 192, 0), array(0, 0, 192), array(192, 0, 192));
    protected $data_line_width = 0.5;
    protected $dash_array = array(array(5), array(), array(10, 3), array(10, 3, 3));
    protected $dash_space_length = 5;

    public function __construct() {
      parent::__construct();
      $this->AddPage();

      // 日本語の明朝体フォントを追加
      $this->SetFont('hminchomedium', '', 12);
    }

    /*******************************************************************
    PDFをブラウザで表示します
    概要：PDFをブラウザで表示
    引数：ファイル名、得点の配列、タイトルの配列、満点の配列、カテゴリ ( String )
    戻値：なし
    *******************************************************************/
    function ViewChart ( $id )
    {
      $filename = $id.".pdf"; // ファイル名
      $year = substr ( $id, 0, 2 );
      $type = getTypeNo ( substr ( $id, 14 ) ); // カテゴリ
      $arr_avg = getNowAverage ( $year, $type, FALSE ); // 全国の平均

      $this->CreateChart ( $id, $arr_avg ); // PDF作成

      // PDFファイルをブラウザに出力する
      $pdfdata = $this->Output ( "", "S" );
      header ( "Cache-Control: public" );
      header ( "Pragma: public" );
      header ( "Content-type: application/pdf" );
      header ( "Content-disposition: inline;filename=".$filename );
      header ( "Content-length: ".strlen ( $pdfdata ) );

      echo ( $pdfdata );

      exit;
    }

    /*******************************************************************
      PDFデータを保存します
      概要：PDFデータを保存
      引数：ファイル名、得点の配列、タイトルの配列、満点の配列
      戻値：なし
    *******************************************************************/
    function SaveChart ( $id, $arr_avg, $filepath = "./pdf/" )
    {
      $filename = $id.".pdf"; // ファイル名

      $this->CreateChart ( $id, $arr_avg, FALSE );  // PDF作成
      $this->Output ( $filepath.$filename, "F" ); // PDFファイルを保存
    }

    /*******************************************************************
      PDFを作成します
      概要：PDFを作成
      引数：得点の配列、タイトルの配列、満点の配列
      戻値：なし
    *******************************************************************/
    function CreateChart ( $id, $arr_avg,$view = TRUE )
    {
      $year = substr ( $id, 0, 2 );
      // カテゴリ
      $type = getTypeNo ( substr ( $id, 14 ) );
      // カテゴリ文字列取得
      $category_str = getCategoryStr ( $type );
      // 大項目
      $title_array = getLargeItem ( $year, $type );
      // 評価 ( 今回 / 前回 / 前々回 )
      $arr_pt = getIndividualEvaluation ( $id );
      // 評価に平均の配列を追加 ( 全国の平均 / 今回 / 前回 / 前々回 )
      // $arr_ptがnullまたは未定義の場合、空の配列で初期化
      if (!is_array($arr_pt)) {
        $arr_pt = [];
      }
      array_unshift ( $arr_pt, $arr_avg );

      // 満点 ( 今回 / 今回 / 前回 / 前々回 )
      $max_array = getMaxPoint ( $year, $type );
    
      // 既存のフォントを追加
      // PDFドキュメントを初期化し、新しいページを追加
      //$this->AddPage(); // PDFドキュメントに新しいページを追加
      // マージンを設定
      $this->SetLeftMargin($this->margin);
      // 新しいフォント設定
      $this->SetFont('hminchomedium', '', 12);

      // フォントサイズの設定
      $this->SetFontSize ( $this->title_font_size );
      // 新しい方法（エンコーディング変換が不要な場合）
      $str = $category_str."得点結果　";
      
      //$this->Write ( 8, $str );
      $this->Write(8, $str);

      // フォントサイズの設定
      $this->SetFontSize ( 10 );
      // 変更後
      $str = "注）全国平均とは".($view ? "昨" : "今")."年度参加した全国の病棟の平均値です。";
      $this->Write ( 9, $str );

      // 改行
      $this->Ln ( );
      $this->Ln ( );

      // セルヘッダ作成
      $this->createCell ( $title_array, $arr_pt, $max_array );
      $this->Ln ( );
      $this->Ln ( );
      $this->Ln ( );

      // フォントサイズの設定
      $this->SetFontSize ( $this->title_font_size );
      // レーダーチャートのタイトル表示
  
      $str = $category_str . "得点レーダー";

      $this->Write ( 8, $str );
      $this->Ln ( );

      // レーダーチャートのベース部を作成 ( 100%表示 )
      $this->CreateChartBase ( $this->top_count, 100 );

      // 項目タイトル部作成
      $this->CreateRaderChartTitle ( $title_array );

      for ( $i = 0;$i < 4;$i++ ) {
        $this->SetDrawColor ( $this->data_line_color[$i][0], $this->data_line_color[$i][1], $this->data_line_color[$i][2] );
        // 点線の設定を行います。
        $this->SetDash ( $this->dash_space_length, $this->dash_array[$i] );
        // レーダーチャート描画
        $this->CreateRaderChart ( $arr_pt[$i], $max_array[$i] );
      }

      $this->createLineDetails ( $arr_pt );

      $this->Ln ( );
      // フォントサイズの設定
      $this->SetFontSize ( 14 );
      // セルのタイトル表示
      $str = "当該領域で「回答しない」が１項目以上あった場合は0点として表示されます。";

      $this->Write ( 8, $str );
      $this->Ln ( );
      $str = "※満点を100として%で表示しております。";    
      $this->Write ( 8, $str );
    }

  /*******************************************************************
    テーブル ( 表 )を作成します
    概要：テーブル ( 表 )を作成
    引数：タイトルの配列、得点の配列
    戻値：なし
  *******************************************************************/
  function createCell ( $title_array, $arr_pt, $max_array )
  {
    // セルのフォントサイズの設定
    $this->SetFontSize ( $this->cell_font_size );
    // セルのヘッダ部を描画 ※項目（満点）
    $this->Cell($this->cell_title_width, $this->cell_header_height, "項目（満点）", 1, 0, "C");
    // ※全国平均 今回の結果 前回の結果 前々回の結果
    for ( $i = 0;$i < count ( $this->str_point );$i++ ) {
      $this->Cell($this->cell_point_width, $this->cell_header_height, $this->str_point[$i], 1, 0, "C");
    }
    $this->Ln ( );
    // セルのデータ部を描画
    for ( $i = 0;$i < $this->top_count;$i++ ) {
      // 列の高さ
      $curret_x = $this->GetX ( );
      $curret_y = $this->GetY ( );
      $title = $title_array[$i];

      if ( !isset ( $title ) ) $title = "-";
      // 項目（満点）
      $tmp = mb_substr ( $title_array[$i].str_repeat ( "　", 10 ), 0, 10 )." ( ".$max_array[0][$i]." )";  
      // 満点位置揃え$max_array[0]は今年度の参照ユーザタイプのカテゴリ毎の満点
      $this->MultiCell($this->cell_title_width, $this->cell_data_height, $tmp, 1, "L");

      $next_y = $this->GetY ( );
      $this->SetXY ( $curret_x + $this->cell_title_width, $curret_y );
      for ( $j = 0;$j < 4;$j++ ) {
        if ( !isset ( $arr_pt[$j][$i] ) || $arr_pt[$j][$i] === "" ) {
          $tmp_point = "-";
        } else {
          $tmp_point = sprintf ( $arr_pt[$j][$i], "%01.01f" ).
              " ( ".sprintf ( "%01.01f", ( $arr_pt[$j][$i] / $max_array[0][$i] * 100 ) ) ."% )";
        }
        $this->Cell ( $this->cell_point_width, $next_y - $curret_y, $tmp_point, 1, 0, "R" );
      }
      $this->Ln ( );
    }
  }


  /*******************************************************************
    レーダーチャートのベース部を作成します
    概要：レーダーチャートのベース部を作成
    引数：頂点数、レーダーチャートの最大値
    戻値：なし
  *******************************************************************/
  function CreateChartBase ( $point_count, $max_chart )
  {
    $rad = 360 / $point_count;
    // 線の太さ
    $this->SetLineWidth ( 0.1 );
    // 線の色
    $this->SetDrawColor ( $this->base_line_color );
    // 放射線を作成
    for ( $i = 0;$i < $point_count;$i++ ) {
      $current_x = $this->size * sin ( deg2rad ( $rad*$i ) );
      $current_y = $this->size * cos ( deg2rad ( $rad*$i ) );
      $this->Line ( $this->center_x, $this->center_y, $this->center_x + $current_x, $this->center_y - $current_y );
    }

    // 外周の頂点を作成
    for ( $j = 1;$j < $this->round_count+1;$j++ ) {
      $top_array = array ( );
      for ( $i = 0;$i < $point_count;$i++ ) {
        $radius  = $this->size / $this->round_count * $j;
        // 頂点のX座標
        $top_x = $this->center_x + $radius * sin ( deg2rad ( $rad*$i ) );
        // 頂点のY座標
        $top_y = $this->center_y - $radius * cos ( deg2rad ( $rad*$i ) );
        $top_array[] = $top_x;
        $top_array[] = $top_y;
      }

      // 外周を描画
      $this->Polygon ( $top_array, "D" );
    }

    // パーセント表示
    for ( $j = 0;$j <= $this->round_count;$j++ ) {
      // 実際の文字列間隔 ( Y座標 )
      $interbal_size = $this->size / $this->round_count;
      // データ間隔
      $interbal_data = $max_chart / $this->round_count;
      // 文字列設定
      $str = (string)($interbal_data * $j);
      // 座標設定
      $this->SetXY ( ( $this->center_x - $this->GetStringWidth ( $str ) - 2 ), ( $this->center_y - $interbal_size * $j ) );
      // フォントサイズの設定
      $this->SetFontSize ( $this->percent_font_size );
      // 文字書き込み
      $this->Write ( 0, $str );

    }

  }

  /*******************************************************************
    レーダーチャートのタイトル部を描画します
    概要：レーダーチャートのタイトル部を描画
    引数：タイトルの配列
    戻値：なし
  *******************************************************************/
  function CreateRaderChartTitle ( $title_array )
  {
    if (!is_array($title_array)) {
      return; // または $title_array = [];
    }
   
    // フォントサイズの設定
    $this->SetFontSize ( $this->item_font_size );
    // タイトル数
    $title_count = count ( $title_array );
    if ( $title_count == 0 ) {
      return;
    }

    // 放射線の間隔 ( 角度 )
    $rad = 360 / $title_count;

    // タイトル描画
    for ( $i = 0;$i < $title_count;$i++ ) {
      // 頂点のX座標
      $title_x = $this->center_x + $this->size * sin ( deg2rad ( $rad*$i ) );
      // 頂点のX座標
      $title_y = $this->center_y - $this->size * cos ( deg2rad ( $rad*$i ) );

      // タイトルがセンターの時は文字を中寄せ + Y座標微調整
      if ( $title_x == $this->center_x ) {
        $title_x -= $this->GetStringWidth ( $title_array[$i] ) / 2;
        // レーダチャートと重ならいようにY座標を微調整
        if ( $title_y < $this->center_y ) {
          $title_y -= $this->percent_font_size / 2;
        } else {
          $title_y += 4;
        }
      // タイトルがレーダチャートの左にある時に文字列位置の調整
      } else if ( $title_x  < $this->center_x ) {
        $title_x -= ( $this->GetStringWidth ( $title_array[$i] ) + 2 );
      }

      // 座標設定
      $this->SetXY ( $title_x, $title_y );
      // 文字書き込み
      $this->Write(0, $title_array[$i]);
    }
  }
  /*******************************************************************
    レーダーチャートのデータ部を描画します
    概要：レーダーチャートのデータ部を描画
    引数：得点の配列、最大値
    戻値：なし
  *******************************************************************/
  function CreateRaderChart ( $arr_pt, $max )
  {
    if ( !$max ) return;

    // データ数
    $point_count = count ( $arr_pt );
    if ( $point_count == 0 ) return;

    // データの頂点 ( 配列 )
    $top_data_array = array ( );

    // 放射線の間隔 ( 角度 )
    $rad = 360 / $point_count;

    // 線の太さを設定します。
    $this->SetLineWidth ( $this->data_line_width );

    // データ頂点の配列を作成
    for ( $i = 0;$i < $point_count;$i++ ) {
      if ( !isset ( $max[$i] ) || $max[$i] == 0 ) return;

      if ( !isset ( $arr_pt[$i] ) || $arr_pt[$i] === "" ) $arr_pt[$i] = 0;

      // データと実表示サイズの比率
      $rate = $max[$i] / $this->size;
      if ( $arr_pt[$i] == 0 ) {
        $top_data_array[] = $this->center_x + 0.1 * sin ( deg2rad ( $rad * $i ) );
        $top_data_array[] = $this->center_y + 0.1 * cos ( deg2rad ( $rad * $i ) );
      } else {
        $top_data_array[] = $this->center_x + $arr_pt[$i] / $rate * sin ( deg2rad ( $rad * $i ) );
        $top_data_array[] = $this->center_y - $arr_pt[$i] / $rate * cos ( deg2rad ( $rad * $i ) );
      }
    }

    // データ描画
    $this->Polygon ( $top_data_array );

  }

  /*******************************************************************
    レーダチャートの線の説明部を作成します
    概要：レーダチャートの線の説明部を作成
    引数：得点の配列
    戻値：なし
  *******************************************************************/
  function createLineDetails ( $arr_pt )
  {
    // 枠線の太さ
    $line_width = 0.1;

    // 説明部のフォントサイズ
    $font_details_size = 12;
    // 説明部の枠の幅
    $cell_details_width = 60;
    // 説明部の枠内の１行の高さ
    $cell_details_height = 8;
    // チャートとのオフセット ( Y軸 )
    $offset_y = 40;

    // フォントサイズの設定
    $this->SetFontSize ( $font_details_size );

    // 枠線の設定
    $this->SetLineWidth ( $line_width );
    $this->SetDrawColor ( 0, 0, 0 );
    $this->SetDash ( 0, array ( ) );

    // 座標調整
    $this->SetX ( -20 - $cell_details_width );
    $current_x = $this->GetX ( );
    $current_y = $this->center_y + $offset_y;
    $this->SetXY ( $current_x, $current_y );

    // 枠内表示文字列設定
    $str = "";

    for ( $i = 0;$i < count ( $arr_pt );$i++ ) {
      $str .= $this->str_point[$i];
      if ( $i != count ( $arr_pt ) - 1 ) {
        $str .= "\n";
      }
    }

    // 長方形をセルで表示
    $this->MultiCell($cell_details_width, $cell_details_height, $str, 1, "R");


    // 線の太さを設定します。
    $this->SetLineWidth ( $this->data_line_width );

    $current_x += 2;
    $current_y += $cell_details_height/2;

    for ( $i = 0;$i < count ( $arr_pt );$i++ ) {
      $this->SetDrawColor ( $this->data_line_color[$i][0], $this->data_line_color[$i][1], $this->data_line_color[$i][2] );
      // 点線の設定を行います。
      $this->SetDash ( $this->dash_space_length, $this->dash_array[$i] );
      
      $this->Line ( $current_x, $current_y, $current_x + 20, $current_y );
      $current_y += $cell_details_height;
    }
  }

  /*******************************************************************
    点線の設定を行います
    概要：点線の設定
    引数：空白部の長さ、実線部の長さ
    戻値：なし
  *******************************************************************/
  function SetDash ( $space, $line_length_array )
  {
    // 実線長の配列数
    $array_count = count ( $line_length_array );

    if ( $array_count > 0 ) {
      $str_define = "[";
      for ( $i = 0;$i < $array_count;$i++ ) {
        $str_define .= sprintf ( "%.3f %.3f", $line_length_array[$i], $space );
        if ( $i != $array_count-1 ) {
          $str_define .= " ";
        }
      }
      $str_define .= "] 0 d";
    } else {
      $str_define = "[] 0 d";
    }
    $this->_out ( $str_define );
  }

}

// 外部データ処理関数は、実際のデータベース接続や処理ロジックに基づく必要があります。
// 以下は、関数のプロトタイプです。

/*******************************************************************
  大項目を取得します。 ( PDF作成用 )
  概要：大項目を取得 ( １〜６ )
  引数：年度、ユーザタイプ ( 0:構造、1:過程 )
  戻値：大項目 ( 配列 )
*******************************************************************/
function getLargeItem($db, $year, $type) {
  $title_array = [];
  // 大項目取得
  $sql = "SELECT id1, name FROM history WHERE year=? AND id=? ORDER BY id1 ASC";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("ss", $year, $type);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($fld = $result->fetch_object()) {
    $title_array[] = $fld->name;
  }

  return $title_array;
}


/*******************************************************************
  今回の全国平均値を大項目毎に取得する
  概要：全国の平均を取得
  引数：年度、ユーザタイプ ( 0:構造、1:過程 )、大項目
  戻値：全国の平均
*******************************************************************/
function getNowAverage ( $year, $usrtype, $avg_calc = TRUE )
{

  global $db;

  $sql = "SELECT id1 FROM history WHERE year='".$year."' AND id='".$usrtype."' ORDER BY id1 ASC";
  $rs = mysqli_query ( $db, $sql );

  if ( !mysqli_num_rows ( $rs ) ) {  // 既存データなし

    return NULL;

  } else {

    while ( $fld = mysqli_fetch_object ( $rs ) ) {
      $id1[] = $fld->id1;
    }

    if ( $avg_calc ) {


      // --------------------------------------------------------------------------------------------
      // 平均の取得（小数点第一位まで表示）
      // --------------------------------------------------------------------------------------------
      // 集計一時テーブルの初期化
      //mysqli_unbuffered_query ( $db , "TRUNCATE TABLE ans_total"  );旧コード
      $truncateResult = mysqli_query($db, "TRUNCATE TABLE ans_total");
      if (!$truncateResult) {
          // エラー処理
          echo "テーブルの空にする際のエラー: " . mysqli_error($db);
      }

      // 集計結果を一時テーブルに保存
      $sql = "INSERT INTO ans_total(id,id1,uid,point) ".
        "SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.uid ,SUM(usr_ans.point)AS sum_point ".
        "FROM item4,usr,usr_ans ".
        "WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND ".
        "item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND ".
        "item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$year."%' ".
        "AND usr_ans.id='".$usrtype."' ".
        "GROUP BY usr_ans.id,usr_ans.id1,usr_ans.uid";
      //mysqli_unbuffered_query ( $sql ,$db );旧コード
      $insertResult = mysqli_query($db, $sql);
      if (!$insertResult) {
          // エラー処理
          echo "データ挿入時のエラー: " . mysqli_error($db);
      }

      // 大項目ごとに平均を計算
      for ( $i = 0;$i < count ( $id1 );$i++ ) {

        $sql = "SELECT ROUND(AVG(point),1)AS avg_point FROM ans_total WHERE id1='".$id1[$i]."'";

        $rs = mysqli_query ( $db, $sql );
        if ( !mysqli_num_rows ( $rs ) ) {  // 既存データなし
          $avg_point[] = "";
        } else {
          $fld = mysqli_fetch_object ( $rs );
          $avg_point[] = $fld->avg_point;
        }
        // --------------------------------------------------------------------------------------------

      }

    } else {

      // 大項目ごとに平均を取得
      for ( $i = 0;$i < count ( $id1 );$i++ ) {

        // --------------------------------------------------------------------------------------------
        // 平均データの取得（小数点第一位まで表示）
        // --------------------------------------------------------------------------------------------
        $sql = "SELECT ROUND(avg,1)AS point FROM dat_avg WHERE id1 = '".$id1[$i]."' and id = '".$usrtype."'";
        //error_log ( $sql."\n" , 3, "/home/hyougo2/public_html/lib/debug.log" );
        $rs = mysqli_query ( $db, $sql );
        if ( !mysqli_num_rows ( $rs ) ) {  // 既存データなし
          $avg_point[] = "";
        } else {
          $fld = mysqli_fetch_object ( $rs );
          $avg_point[] = $fld->point;
        }
        // --------------------------------------------------------------------------------------------

      }

    }

  }

  return $avg_point;
}


/*******************************************************************
  今回/前回/前々回の評価を取得します。
  概要：今回/前回/前々回の評価を取得
  引数：ID
  戻値：今回/前回/前々回の評価
*******************************************************************/

function getIndividualEvaluation ( $uid )
{

  global $db;
  $arr_uid = explode('-', $uid);
  // --------------------------------------------------------------------------------------------
  // 年度
  $year[] = substr ( $uid, 0, 2 );          // 今年
  if(is_numeric($arr_uid[4]) && $arr_uid[4] > 50){
  }else{
    $year[] = sprintf ( "%02d", ( int )$year[0] - 1 );  // 昨年
    $year[] = sprintf ( "%02d", ( int )$year[0] - 2 );  // 一昨年
  }
  // カテゴリ
  $category = getTypeNo ( substr ( $uid, 14 ) );
  // --------------------------------------------------------------------------------------------
  // 大項目ID取得
  $sql = "SELECT id1 FROM history WHERE year='".$year[0]."' AND id='".$category."' ORDER BY id1 ASC";

  $rs = mysqli_query ( $db, $sql );
  if ( !mysqli_num_rows ( $rs ) ) return NULL; // 既存データなし
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $arr_id1[] = $fld->id1;
  }
  mysqli_free_result ( $rs );

  // 今年度より 3年分取得
  for ( $i = 0;$i < 3;$i++ ) {

    $serch_id = sprintf ( "%02d%s", $year[$i], substr ( $uid, 2 ) );  //検索用ID

    // 07-01-0001-01-051
    // 06-01-0001-01-051
    // 05-01-0001-01-051

    // 大項目ごとに評価を取得
    for ( $j = 0;$j < count ( $arr_id1 );$j++ ) {

      $id1 = ( int )$arr_id1[$j]; // 大項目ID

      // 合計点
      /*
      $sql = "SELECT SUM(usr_ans.point)AS point FROM item4, usr, usr_ans ".
        "WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id ".
        "AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 ".
        "AND item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' ".
        "AND usr_ans.id = '".$category."' AND usr_ans.id1 = '".$id1."' AND usr.uid = '".$serch_id."'";
      */
      // ADD 2017/02/06 
      // 設問内容を変更した際に過去の点数が下がってしまう為、設問の紐づき関係なく単純集計に変更。
      $sql = "SELECT SUM(usr_ans.point)AS point "
           . "FROM usr, usr_ans "
           . "LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 "
           . "WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND usr.comp = '1' AND usr.del = '1' "
           . " AND CASE WHEN item4.qtype IS NULL THEN '1' ELSE item4.qtype END = '1'"
           . " AND usr_ans.id = '".$category."' AND usr_ans.id1 = '".$id1."' AND usr.uid = '".$serch_id."'";

      $rs = mysqli_query ( $db, $sql );
      if ( !mysqli_num_rows ( $rs ) ) {  // 既存データなし
        $avg_pt[$i][$j] = "";
      } else {
        $fld = mysqli_fetch_object ( $rs );
        $avg_pt[$i][$j] = sprintf ( "%01.1f", $fld->point );  // 小数点第一位まで表示
      }
    }
  }
  return $avg_pt;
}

/*******************************************************************
  今回/前回/前々回の満点を取得します。
  概要：今回/前回/前々回の満点を取得
  引数：年度、ユーザタイプ ( 0:構造、1:過程 )
  戻値：今回/前回/前々回の満点
*******************************************************************/
function getMaxPoint ( $year, $type )
{
  
  global $db;
  $max_array = array ( ); // 満点 ( 今回/今回/前回/前々回 )
  $iyear = $year; // 年度 ( int )
  for ( $i = 0;$i < 3;$i++ ) {
    $tmp_max = array ( ); // 一時格納用配列
    $serch_year = sprintf ( "%02d", $iyear ); //検索用年度
    // 満点取得
    $sql = "SELECT id1, point FROM history WHERE year='".$serch_year."' AND id='".$type."' ORDER BY id1 ASC";
    $rs = mysqli_query ( $db, $sql );

    if ( mysqli_num_rows ( $rs ) ) {
      while ( $fld = mysqli_fetch_object ( $rs ) ) {
        $tmp_max[] = ( int )$fld->point;
      }
    } else {
      if ( $i == 0 ) $max_array = array ( 0, 0 );
      return $max_array;
    }

    $max_array[] = $tmp_max;
    if ( $i == 0 ) $max_array[] = $tmp_max; // 今回については2回挿入

    $iyear--;

  }
  return $max_array;

}


/*******************************************************************
  カテゴリの文字列を取得します。
  概要：カテゴリの文字列を取得
  引数：カテゴリタイプ
  戻値：カテゴリの文字列
*******************************************************************/
function getCategoryStr ( $category )
{
  global $db;

  $sql = "SELECT category FROM category WHERE id=".$category;
  $rs = mysqli_query ( $db, $sql );
  $fld = mysqli_fetch_object ( $rs );

  return $fld->category;

}

/*******************************************************************
  ユーザタイプを取得します。
  概要：ユーザタイプを取得
  引数：IDより取得した枝番
  戻値：ユーザタイプ ( 0:構造、2:過程 )
*******************************************************************/
function getTypeNo ( $branch_no )
{
  // タイプ自動判別
  if ( ( int )$branch_no == 0 ) {
    $type = 1;// 0のID
  } elseif ( 1 <= ( int )$branch_no && ( int )$branch_no <= 50 ) {
    $type = 2;// 2〜50 までのID
  } else {
    $type = 3;// それ以上のID
  }

  return $type;

}


?>
