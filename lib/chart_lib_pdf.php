<?php
require_once ( "../admin/setup.php" );
require_once('TCPDF-main/tcpdf.php');
require_once('chart_lib_common.php');

$db = Connection::connect();

class PDF extends BasePDF {

  public function __construct() {
    parent::__construct();
    $this->AddPage();
    $fontfile = '../lib/TCPDF-main/fonts/ZenOldMincho-Regular.ttf';
    $fontname = TCPDF_FONTS::addTTFfont($fontfile, 'TrueTypeUnicode', '', 32);
    $this->SetFont($fontname, '', 12);
  }

  public function ViewChart($id) {
    $filename = $id.".pdf";
    $year = substr($id, 0, 2);
    $type = getTypeNo(substr($id, 14));
    $arr_avg = getNowAverage($year, $type, FALSE);
    $this->CreateChart($id, $arr_avg);
    $pdfdata = $this->Output("", "S");
    if (ob_get_length()) {
      ob_end_clean();
    }
    header("Cache-Control: public");
    header("Pragma: public");
    header("Content-type: application/pdf");
    header("Content-disposition: inline;filename=".$filename);
    header("Content-length: ".strlen($pdfdata));
    echo($pdfdata);
    exit;
  }

  public function SaveChart($id, $arr_avg, $filepath = "./pdf/") {
    $filename = $id.".pdf";
    $this->CreateChart($id, $arr_avg, FALSE);
    $this->Output($filepath.$filename, "F");
  }

  public function CreateChart($id, $arr_avg, $view = TRUE) {
    global $db;
    $year = substr($id, 0, 2);
    $type = getTypeNo(substr($id, 14));
    $category_str = getCategoryStr($type);
    $title_array = getLargeItem($year, $type);
    $arr_pt = getIndividualEvaluation($id);
    if (!is_array($arr_pt)) {
      $arr_pt = [];
    }
    array_unshift($arr_pt, $arr_avg);
    $max_array = getMaxPoint($year, $type);
    $this->SetLeftMargin($this->margin);
    $this->SetFontSize($this->title_font_size);
    $str = $category_str."得点結果　";
    $this->Write(8, $str);
    $this->SetFontSize(10);
    $str = "注）全国平均とは".($view ? "昨" : "今")."年度参加した全国の病棟の平均値です。";
    $this->Write(9, $str);
    $this->Ln();
    $this->Ln();
    $this->createCell($title_array, $arr_pt, $max_array);
    $this->Ln();
    $this->Ln();
    $this->Ln();
    $this->SetFontSize($this->title_font_size);
    $str = $category_str . "得点レーダー";
    $this->Write(8, $str);
    $this->Ln();
    $this->CreateChartBase($this->top_count, 100);
    $this->CreateRaderChartTitle($title_array);
    for ($i = 0; $i < 4; $i++) {
      $this->SetDrawColor($this->data_line_color[$i][0], $this->data_line_color[$i][1], $this->data_line_color[$i][2]);
      $this->SetDash($this->dash_space_length, $this->dash_array[$i]);
      $this->CreateRaderChart($arr_pt[$i], $max_array[$i]);
    }
    $this->createLineDetails($arr_pt);
    $this->Ln();
    $this->SetFontSize(14);
    $str = "当該領域で「回答しない」が１項目以上あった場合は0点として表示されます。";
    $this->Write(8, $str);
    $this->Ln();
    $str = "※満点を100として%で表示しております。";
    $this->Write(8, $str);
  }

  public function createCell($title_array, $arr_pt, $max_array) {
    $this->SetFontSize($this->cell_font_size);
    $this->Cell($this->cell_title_width, $this->cell_header_height, "項目（満点）", 1, 0, "C");
    for ($i = 0; $i < count($this->str_point); $i++) {
      $this->Cell($this->cell_point_width, $this->cell_header_height, $this->str_point[$i], 1, 0, "C");
    }
    $this->Ln();
    for ($i = 0; $i < $this->top_count; $i++) {
      $curret_x = $this->GetX();
      $curret_y = $this->GetY();
      $title = $title_array[$i];
      if (!isset($title)) $title = "-";
      $tmp = mb_substr($title_array[$i].str_repeat("　", 10), 0, 10)." ( ".$max_array[0][$i]." )";
      $this->MultiCell($this->cell_title_width, $this->cell_data_height, $tmp, 1, "L");
      $next_y = $this->GetY();
      $this->SetXY($curret_x + $this->cell_title_width, $curret_y);
      for ($j = 0; $j < 4; $j++) {
        if (!isset($arr_pt[$j][$i]) || $arr_pt[$j][$i] === "") {
          $tmp_point = "-";
        } else {
          $tmp_point = sprintf($arr_pt[$j][$i], "%01.01f").
              " ( ".sprintf("%01.01f", ($arr_pt[$j][$i] / $max_array[0][$i] * 100))."% )";
        }
        $this->Cell($this->cell_point_width, $next_y - $curret_y, $tmp_point, 1, 0, "R");
      }
      $this->Ln();
    }
  }

  // レーダーチャートの線の説明部を作成
  public function createLineDetails($point_array) {
    $line_width = 0.1;
    $font_details_size = 12;
    $cell_details_width = 60;
    $cell_details_height = 8;
    $offset_y = 40;
    $this->SetFontSize($font_details_size);
    $this->SetLineWidth($line_width);
    $this->SetDrawColor(0, 0, 0);
    $this->SetDash(0, array());
    $this->SetX(-20 - $cell_details_width);
    $current_x = $this->GetX();
    $current_y = $this->center_y + $offset_y;
    $this->SetXY($current_x, $current_y);
    $str = "　　　　　　構造\n　　　　　　過程\n　　　　　　アウトカム";
    $this->MultiCell($cell_details_width, $cell_details_height, $str, 1, "L");
    $this->SetLineWidth($this->data_line_width);
    $current_x += 2;
    $current_y += $cell_details_height/2;
    for ($i = 0; $i < count($point_array); $i++) {
      $this->SetDrawColor($this->data_line_color[$i][0], $this->data_line_color[$i][1], $this->data_line_color[$i][2]);
      $this->SetDash($this->dash_space_length, $this->dash_array[$i]);
      $this->Line($current_x, $current_y, $current_x + 20, $current_y);
      $current_y += $cell_details_height;
    }
  }
}
?>
