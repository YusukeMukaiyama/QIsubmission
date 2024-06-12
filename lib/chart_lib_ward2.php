<?php
/*******************************************************************
chart.php
  PDF出力
*******************************************************************/
require_once ( "../admin/setup.php" );
require_once('TCPDF-main/tcpdf.php');

class PDF_Ward extends TCPDF {

  // レーダーチャートの頂点
  protected $top_count = 6;

  // ページの左マージン
  protected $margin = 10;

  // 平均/評価の文字列
  //protected $str_point = array("全国平均", "今回の結果", "前回の結果" ,"前々回の結果");
  protected $str_point = array("全国平均", "今年の結果", "前年の結果" ,"前々年の結果");

  /*** タイトル部 ***/
  // タイトルのフォントサイズ
  protected $title_font_size = 16;

  /*** セル ***/
  // セルのフォントサイズ
  protected $cell_font_size = 10;
  // セルの高さ ( ヘッダ )
  protected $cell_header_height = 8;
  // セルの高さ ( データ )
  protected $cell_data_height = 8;
  // セルの幅 ( 項目 )
  protected $cell_title_width = 50;
  // セルの幅 ( 点数 )
  protected $cell_point_width = 30;


  /*** レーダーチャート ***/
  // レーダーチャート表示位置のX座標
  protected $center_x = 90;
  // レーダーチャート表示位置のY座標
  protected $center_y = 140;
  // レーダーチャートの半径
  protected $size = 40;
  // レーダーチャートベースの線色 ( グレイスケール )
  protected $base_line_color = 192;
  // 外周の多重度
  protected $round_count = 4;
  // パーセント表示のフォントサイズ
  protected $percent_font_size = 10;
  // 項目タイトルのフォントサイズ
  protected $item_font_size = 14;

  // データ線色 ( R, G, B ) 全国の平均−今回の評価−前回の評価−前々回の評価
  protected $data_line_color = array ( array ( 192, 0, 0 ), array ( 0, 192, 0 ), array ( 0, 0, 192 ), array ( 192, 0, 192 ) );
  // データ線の太さ
  protected $data_line_width = 0.5;
  // 点線の実線部長の配列
  protected $dash_array = array ( array ( 5 ), array ( ), array ( 10, 3 ), array ( 10, 3, 3 ) );
  // 点線の空白長
  protected $dash_space_length = 5;

  /*******************************************************************
    PDFをブラウザで表示します
    概要：PDFをブラウザで表示
    引数：ファイル名、得点の配列、タイトルの配列、満点の配列、カテゴリ ( String )
    戻値：なし
  *******************************************************************/
  public function ViewChart($id)
    {
        
        $filename = $id . ".pdf"; // ファイル名
        $year = substr($id, 0, 2);
        $type = getTypeNo(substr($id, 14)); // カテゴリ
        $arr_avg = getNowAverage($year, $type, FALSE); // 全国の平均
        $this->CreateChart($id, $arr_avg); // PDF作成
        // PDFファイルをブラウザに出力する
        $pdfdata = $this->Output("", "S");
        // 出力バッファをクリア
        if (ob_get_length()) {
            ob_end_clean();
        }
        header("Cache-Control: public");
        header("Pragma: public");
        header("Content-type: application/pdf");
        header("Content-disposition: inline;filename=" . $filename);
        header("Content-length: " . strlen($pdfdata));

        echo($pdfdata);

        exit;
    }


  /*******************************************************************
    PDFデータを保存します
    概要：PDFデータを保存
    引数：ファイル名、得点の配列、タイトルの配列、満点の配列
    戻値：なし
  *******************************************************************/
  function SaveChart ( $ward, $filepath = "./pdf_bat/" )
  {
    global $db;
    // ファイル名
    $filename = $ward."_total.pdf";

    // PDF作成
    $this->CreateChart ( $ward );

    // PDFファイルを保存
    $this->Output ( $filepath.$filename, "F" );

  }

  /*******************************************************************
    PDFを作成します
    概要：PDFを作成
    引数：得点の配列、タイトルの配列、満点の配列
    戻値：なし
  *******************************************************************/
  function CreateChart ( $ID )
  {
    global $db;
    /***   DBデータ取得   ***/
    $year = substr ( $ID, 0, 2 );

    /*******************************************************************
      構造
    *******************************************************************/
    $type_str = 1;  // カテゴリ ( 構造 )
    $category_str = getCategoryStr ( $type_str ); // カテゴリ文字列取得 ( 構造 )
    $title_array_str = getLargeItem ( $year, $type_str ); // 大項目 ( 構造 )

    // 全国の平均 ( 構造 )
    $average_array_str = getNowAverage ( $year, $type_str );

    // 評価 ( 構造 )
    $point_array_str = getEvaluationWard ( $ID );

    // 評価に平均の配列を追加 ( 全国の平均/今回の評価/前回の評価/前々回の評価 )
    array_unshift ( $point_array_str, $average_array_str );

    // 満点 ( 構造 )
    $max_array_str = getMaxPoint ( $year, $type_str );

    $this->Open ( );
    $this->SetLeftMargin ( $this->margin );
    $this->AddPage ( );
    $this->SetFont ( "zenoldmincho", "", 12 );

    // フォントサイズの設定
    $this->SetFontSize ( $this->title_font_size );
    // セルのタイトル表示
    $str = "病棟ID　".$ID."　".$year."年度";
    $this->Write ( 8, $str );
    $this->Ln ( );

    // 病棟の病床区分:
    $this->SetFont ( "zenoldmincho", "", 8 );
    $str = "病棟の病床区分：";
    $sql = "SELECT (enq_ans.ans)AS type FROM enq_usr_ans,enq_ans WHERE enq_usr_ans.id = enq_ans.id AND enq_usr_ans.id1 = enq_ans.id1 AND enq_usr_ans.ans = enq_ans.id2 AND enq_usr_ans.id = 11 AND enq_usr_ans.id1 = 15 AND enq_usr_ans.uid LIKE '".$ID."%'";
    $rs = mysqli_query ( $db , $sql );
    if ( $fld = mysqli_fetch_object ( $rs ) ) {
      $str .= $fld->type;
    }
    $this->Write ( 8, $str );
    $this->Ln ( );

    // 病棟病床数：999
    $str = "病棟病床数：";
    $sql = "SELECT ans FROM enq_usr_ans WHERE id = 11 AND id1 = 16 AND uid LIKE '".$ID."%' ORDER BY ans DESC LIMIT 1";
    $rs = mysqli_query ( $db , $sql );
    if ( $fld = mysqli_fetch_object ( $rs ) ) {
      $str .= $fld->ans;
    }
    $this->Write ( 8, $str );
    $this->Ln ( );
    $this->SetFont ( "zenoldmincho", "", 12 );

    $str = "あなたの病棟の結果です。";
    $this->Write ( 8, $str );
    $this->Ln ( );
    $this->Ln ( );

    // フォントサイズの設定
    $this->SetFontSize ( $this->title_font_size );
    // セルのタイトル表示
    $str = $category_str."得点";
    $this->Write ( 8, $str );
    // フォントサイズの設定
    $this->SetFontSize ( 10 );
    $str = "注）全国平均とは今年度参加した全国の病棟の平均値です。";
    $this->Write ( 9, $str );

    // 改行
    $this->Ln ( );

    // セルヘッダ作成
    $this->createCell ( $title_array_str, $point_array_str, $max_array_str );
    $this->Ln ( );
    $this->Ln ( );


    /*******************************************************************
      過程
    *******************************************************************/

    // カテゴリ ( 過程 )
    $type_pro = 2;
    // カテゴリ文字列取得 ( 過程 )
    $category_str_pro = getCategoryStr ( $type_pro );
    // 大項目 ( 過程 )
    $title_array_pro = getLargeItem ( $year, $type_pro );
    // 全国の平均 ( 過程 )
    $average_array_pro = getNowAverage ( $year, $type_pro );

    // 評価 ( 過程 )
    $point_array_pro = getAllPastEvaluationHospital ( $ID, $year, $type_pro );

    // 評価に平均の配列を追加 ( 全国の平均/今回の評価/前回の評価/前々回の評価 )
    array_unshift ( $point_array_pro, $average_array_pro );

    // 満点 ( 過程 )
    $max_array_pro = getMaxPoint ( $year, $type_pro );

    // フォントサイズの設定
    $this->SetFontSize ( $this->title_font_size );
    // セルのタイトル表示
    $str = $category_str_pro."得点";
    $this->Write ( 8, $str );

    // 改行
    $this->Ln ( );

    // セルヘッダ作成
    $this->createCell ( $title_array_pro, $point_array_pro, $max_array_pro );
    $this->Ln ( );
    $this->Ln ( );



    /*******************************************************************
      アウトカム
    *******************************************************************/

    // カテゴリ ( アウトカム )
    $type_out = 3;
    // カテゴリ文字列取得 ( アウトカム )
    $category_str_out = getCategoryStr ( $type_out );
    // 大項目 ( アウトカム )
    $title_array_out = getLargeItem ( $year, $type_out );
    // 全国の平均 ( アウトカム )
    $average_array_out = getNowAverage ( $year, $type_out );
    // 評価 ( アウトカム )
    $point_array_out = getAllPastEvaluationHospital ( $ID, $year, $type_out );
    
    // 評価に平均の配列を追加 ( 全国の平均/今回の評価/前回の評価/前々回の評価 )
    array_unshift ( $point_array_out, $average_array_out );

    // 満点 ( アウトカム )
    $max_array_out = getMaxPoint ( $year, $type_out );

    // フォントサイズの設定
    $this->SetFontSize ( $this->title_font_size );
    // セルのタイトル表示
    $str = $category_str_out."（患者満足度）";

    $this->Write ( 8, $str );

    // 改行
    $this->Ln ( );

    // セルヘッダ作成
    $this->createCell ( $title_array_out, $point_array_out, $max_array_out );
    $this->Ln ( );
    $this->Ln ( );
    $this->AddPage ( );

    /*******************************************************************
      転倒/転落/褥瘡/院内感染/誤薬
    *******************************************************************/
    // フォントサイズの設定
    $this->SetFontSize ( $this->title_font_size );
    // セルのタイトル表示
    $str = $category_str_out."（患者1000人あたりインシデント発生率）";
    $this->Write ( 8, $str );

    // 改行
    $this->Ln ( );

    // アンケートの全国平均を取得
    $enq_average = getEncAverage ( $year );
    // アンケート結果取得
    $enq_result = getEncResult ( $year, $ID );

    // 評価に平均の配列を追加 ( 全国の平均/今回の評価/前回の評価/前々回の評価 )
    array_unshift ( $enq_result, $enq_average );

    // セルヘッダ作成
    $this->createCellOutCome ( $enq_result );
    $this->Ln ( );
    $this->Ln ( );
    $this->Ln ( );

    // レーダーチャート用の得点の配列を作成
    $point_array = array ( $point_array_str[1], $point_array_pro[1], $point_array_out[1] );
    // レーダーチャート用の満点の配列を作成
    $max_array = array ( $max_array_str[0], $max_array_pro[0], $max_array_out[0] );

    // フォントサイズの設定
    $this->SetFontSize ( $this->title_font_size );
    // レーダーチャートのタイトル表示
    // 文字列のエンコーディング変換
    $str = $category_str . "総合評価" ;
    $this->Write ( 8, $str );
    $this->Ln ( );

    // レーダーチャートのベース部を作成 ( 100%表示 )
    $this->CreateChartBase ( $this->top_count, 100 );

    // 項目タイトル部作成
    $this->CreateRaderChartTitle ( $title_array_str );

    for ( $i=0;$i<4;$i++ ) {
      $this->SetDrawColor ( $this->data_line_color[$i][0], $this->data_line_color[$i][1], $this->data_line_color[$i][2] );
      // 点線の設定を行います。
      $this->SetDash ( $this->dash_space_length, $this->dash_array[$i] );
      // レーダーチャート描画
      $this->CreateRaderChart ( $point_array[$i], $max_array[$i] );
    }

    $this->createLineDetails ( $point_array );

    $this->Ln ( );
    // フォントサイズの設定
    $this->SetFontSize ( 14 );
    // セルのタイトル表示
    $str = "当該領域で「回答しない」が１項目以上あった場合は0点として表示されます。";
    $this->Write(8, $str);    
    $this->Ln ( );
    $str = "※満点を100として%で表示しております。";
    $this->Write(8, $str);


  }

  /*******************************************************************
    テーブル ( 表 )を作成します
    概要：テーブル ( 表 )を作成
    引数：タイトルの配列、得点の配列
    戻値：なし
  *******************************************************************/
  function createCell ( $title_array, $point_array, $max_array )
  {
    global $db;

    // セルのフォントサイズの設定
    $this->SetFontSize ( $this->cell_font_size );

    // セルのヘッダ部を描画 ※項目（満点）
    $this->Cell($this->cell_title_width, $this->cell_header_height, "項目（満点）", 1, 0, "C");


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
      $tmp = mb_substr ( $title.str_repeat ( "　", 10 ), 0, 10 )." ( ".$max_array[0][$i]." )";  // 満点位置揃え$max_array[0]は今年度の参照ユーザタイプのカテゴリ毎の満点
      $this->MultiCell($this->cell_title_width, $this->cell_data_height, $tmp, 1, "L");


      $next_y = $this->GetY ( );
      $this->SetXY ( $curret_x + $this->cell_title_width, $curret_y );
      for ( $j = 0;$j < 4;$j++ ) {
        $tmp_point = $point_array[$j][$i];
        if ( !isset ( $tmp_point ) || $tmp_point === "" ) {
          $tmp_point = "-";
        } else {
          $tmp_point = sprintf ( $tmp_point, "%01.01f" ).
              " ( ".sprintf ( "%01.01f", ( $tmp_point / $max_array[0][$i] * 100 ) ) ."% )";
        }
        $this->Cell ( $this->cell_point_width, $next_y - $curret_y, $tmp_point, 1, 0, "R" );
      }
      $this->Ln ( );
    }
  }

  /*******************************************************************
    テーブル ( 表 )を作成します ( 転倒/転落/褥瘡/院内感染/誤薬 )
    概要：テーブル ( 表 )を作成
    引数：タイトルの配列、得点の配列
    戻値：なし
  *******************************************************************/
  function createCellOutCome($enq_result)
  {
    global $db;

    // セルのフォントサイズの設定
    $this->SetFontSize($this->cell_font_size);

    // セルのヘッダ部を描画
    $this->Cell($this->cell_title_width, $this->cell_header_height, "項目" , 1, 0, 'C');
    for ($i=0; $i<count($this->str_point); $i++) {
      $this->Cell($this->cell_point_width, $this->cell_header_height, $this->str_point[$i] , 1, 0, 'C');
    }
    $this->Ln();

    // セルのデータ部を描画
    for ( $i = 0;$i < 5;$i++ ) {
      // 列の高さ
      $curret_x = $this->GetX();
      $curret_y = $this->GetY();

      switch ($i) {
      case 0:
        $enq_title = "転倒";
        break;
      case 1:
        $enq_title = "転落";
        break;
      case 2:
        $enq_title = "褥瘡";
        break;
      case 3:
        $enq_title = "院内感染";
        break;
      case 4:
        $enq_title = "誤薬";
        break;
      default:
        break;
      }

      $this->MultiCell($this->cell_title_width, $this->cell_data_height, $enq_title, 1, 'L');


      $next_y = $this->GetY();
      $this->SetXY($curret_x + $this->cell_title_width, $curret_y);
      for ($j=0; $j<4; $j++) {
        $tmp_point = $enq_result[$j][$i];
        if (!isset($tmp_point) || $tmp_point === "") {
          $tmp_point = "-";
        }
        $this->Cell($this->cell_point_width, $next_y - $curret_y, strval($tmp_point), 1, 0, 'R');
      }
      $this->Ln();
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
    global $db;

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
    global $db;

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
  function CreateRaderChart ( $point_array, $max )
  {
    global $db;
    if ( !$max ) return;

    // データ数
    $point_count = count ( $point_array );
    if ( $point_count == 0 ) {
      return;
    }

    // データの頂点 ( 配列 )
    $top_data_array = array ( );
    // 放射線の間隔 ( 角度 )
    $rad = 360 / $point_count;


    // 線の太さを設定します。
    $this->SetLineWidth ( $this->data_line_width );

    // データ頂点の配列を作成
    for ( $i = 0;$i < $point_count;$i++ ) {
      if ( !isset ( $max[$i] ) || $max[$i] == 0 ) {
        return;
      }

      if ( !isset ( $point_array[$i] ) || $point_array[$i] === "" ) {
        $point_array[$i] = 0;
      }
      // データと実表示サイズの比率
      $rate = $max[$i] / $this->size;
      if ( $point_array[$i] == 0 ) {
        $top_data_array[] = $this->center_x + 0.1 * sin ( deg2rad ( $rad * $i ) );
        $top_data_array[] = $this->center_y + 0.1 * cos ( deg2rad ( $rad * $i ) );
      } else {
        $top_data_array[] = $this->center_x + $point_array[$i] / $rate * sin ( deg2rad ( $rad * $i ) );
        $top_data_array[] = $this->center_y - $point_array[$i] / $rate * cos ( deg2rad ( $rad * $i ) );
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
  function createLineDetails ( $point_array )
  {
    global $db;
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
    $str = "　　　　　　構造\n　　　　　　過程\n　　　　　　アウトカム";

    // 長方形をセルで表示
    $this->MultiCell($cell_details_width, $cell_details_height, $str, 1, "R");


    // 線の太さを設定します。
    $this->SetLineWidth ( $this->data_line_width );

    $current_x += 2;
    $current_y += $cell_details_height/2;

    for ( $i = 0;$i < count ( $point_array );$i++ ) {
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
    global $db;
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


/*******************************************************************
  今回/前回/前々回の評価を取得します。
  概要：今回/前回/前々回の評価を取得
  引数：ID
  戻値：今回/前回/前々回の評価
*******************************************************************/
function getEvaluationWard ( $uid )
{
  global $db;

  $category = getTypeNo ( substr ( $uid, 14 ) );  // カテゴリ

  $year[] = substr ( $uid, 0, 2 );  // 年度
  $year[] = sprintf ( "%02d", ( int )$year[0] - 1 );  // 昨年
  $year[] = sprintf ( "%02d", ( int )$year[0] - 2 );  // 一昨年

  // --------------------------------------------------------------------------------------------
  // 大項目ID取得
  $sql = "SELECT id1 FROM history WHERE year='".$year[0]."' AND id='".$category."' ORDER BY id1 ASC";
  $rs = mysqli_query ( $db , $sql );
  if ( !mysqli_num_rows ( $rs ) ) return NULL; // 既存データなし
  while ( $fld = mysqli_fetch_object ( $rs ) ) {
    $title_id_array[] = $fld->id1;
  }
  mysqli_free_result ( $rs );

  // 年度
  for ( $j = 0;$j < 3;$j++ ) {

    $wkUid = $year[$j].substr ( $uid, 2 );

    // --------------------------------------------------------------------------------------------
    // 平均の取得（小数点第一位まで表示）
    // --------------------------------------------------------------------------------------------
    // 集計一時テーブルの初期化
    //mysqli_unbuffered_query ( "TRUNCATE TABLE ans_total" ,$db );
    // 集計一時テーブルの初期化(非バッファクエリ)
    mysqli_real_query($db, "TRUNCATE TABLE ans_total");
    $result = mysqli_use_result($db); // この結果は使用されないが、バッファをクリアするために呼び出す
    if ($result) {
        mysqli_free_result($result);
    }
    

    // 集計結果を一時テーブルに保存
/*
    $sql = "INSERT INTO ans_total(id,id1,uid,point) ".
      "SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.uid ,SUM(usr_ans.point)AS sum_point ".
      "FROM item4,usr,usr_ans ".
      "WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND ".
      "item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND ".
      "item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$year[$j]."%' ".
      "GROUP BY usr_ans.id,usr_ans.id1,usr_ans.uid";
*/
    // ADD 2008/05/26 
    // 集計結果を一時テーブルに保存
/*
    $sql = "INSERT INTO ans_total(id,id1,uid,point) ".
      "SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.uid ,SUM(usr_ans.point)AS sum_point ".
      "FROM item4,usr LEFT JOIN usr_ans ".
      " ON usr.uid = usr_ans.uid AND usr.id = usr_ans.id ".
      "WHERE item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 ".
      " AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND item4.qtype = '1' ".
      " AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE '".$wkUid."%' ".
      "GROUP BY usr_ans.id,usr_ans.id1,usr_ans.uid";
*/
    // ADD END

    // ADD 2017/02/06 
    // 設問内容を変更した際に過去の点数が下がってしまう為、設問の紐づき関係なく単純集計に変更。
    $sql = "INSERT INTO ans_total(id,id1,uid,point) "
         . "SELECT usr_ans.id ,usr_ans.id1 ,usr_ans.uid ,SUM(usr_ans.point)AS sum_point "
         . "FROM usr, usr_ans "
         . "LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 "
         . "WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND usr.comp = '1' AND usr.del = '1' "
         . " AND CASE WHEN item4.qtype IS NULL THEN '1' ELSE item4.qtype END = '1' "
         . " AND usr.uid LIKE '".$wkUid."%' "
         . "GROUP BY usr_ans.id,usr_ans.id1,usr_ans.uid";
    // ADD END

    //error_log ( $sql."\n" , 3, "/home/hyougo2/public_html/lib/debug.log" );
    //mysqli_unbuffered_query ( $sql ,$db );
    // 集計一時テーブルの初期化(非バッファクエリ)
    mysqli_real_query($db, $sql);
    $result = mysqli_use_result($db); // この結果は使用されないが、バッファをクリアするために呼び出す
    if ($result) {
        mysqli_free_result($result);
    }
    

    // 大項目
    for ( $i = 0;$i < count ( $title_id_array );$i++ ) {

      $id1 = ( int )$title_id_array[$i];  // 大項目ID

      $sql = "SELECT ROUND(AVG(point),1)AS avg_point FROM ans_total WHERE id = '".$category."' AND id1='".$id1."' AND uid LIKE '".$wkUid."%'";
      //error_log ( $sql."\n" , 3, "/home/hyougo2/public_html/lib/debug.log" );
      $rs = mysqli_query ( $db , $sql );
      if ( !mysqli_num_rows ( $rs ) ) {  // 既存データなし
        $avg_point[$j][$i] = "";
      } else {
        $fld = mysqli_fetch_object ( $rs );
        $avg_point[$j][$i] = $fld->avg_point;
      }

      mysqli_free_result ( $rs );
    }

  }

  return $avg_point;

}


?>
