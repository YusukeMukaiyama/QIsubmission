<?php
require_once('TCPDF-main/tcpdf.php');
require_once('chart_lib_common.php');

class PDF_Ward extends BasePDF {

  public function __construct() {
    parent::__construct();
    $this->AddPage();
    $fontfile = '../lib/TCPDF-main/fonts/MSMINCHO.TTF';
    $fontname = TCPDF_FONTS::addTTFfont($fontfile, 'TrueTypeUnicode', '', 32);
    $this->SetFont($fontname, '', 12);
  }

  public function ViewChart($ID) {
    $filename = $ID."_total.pdf";
    $this->CreateChart($ID);
    $pdfdata = $this->Output("", "S");
    header("Cache-Control: public");
    header("Pragma: public");
    header("Content-type: application/pdf");
    header("Content-disposition: inline;filename=".$filename);
    header("Content-length: ".strlen($pdfdata));
    echo($pdfdata);
    exit;
  }

  public function SaveChart($ward, $filepath = "./pdf_bat/") {
    $filename = $ward."_total.pdf";
    $this->CreateChart($ward);
    $this->Output($filepath.$filename, "F");
  }

  public function CreateChart($ID) {
    global $db;
    $year = substr($ID, 0, 2);
    $type_str = 1;
    $category_str = getCategoryStr($type_str);
    $title_array_str = getLargeItem($year, $type_str);
    $average_array_str = getNowAverage($year, $type_str);
    $point_array_str = getEvaluationWard($ID);
    array_unshift($point_array_str, $average_array_str);
    $max_array_str = getMaxPoint($year, $type_str);
    $this->SetLeftMargin($this->margin);
    $this->SetFontSize($this->title_font_size);
    $str = "病棟ID　".$ID."　".$year."年度";
    $this->Write(8, $str);
    $this->Ln();
    $this->SetFontSize($this->title_font_size);
    $str = $category_str."得点　";
    $this->Write(8, $str);
    $this->SetFontSize(10);
    $str = "注）全国平均とは今年度参加した全国の病棟の平均値です。";
    $this->Write(9, $str);
    $this->Ln();
    $this->createCell($title_array_str, $point_array_str, $max_array_str);
    $this->Ln();
    $this->Ln();
    $type_pro = 2;
    $category_str_pro = getCategoryStr($type_pro);
    $title_array_pro = getLargeItem($year, $type_pro);
    $average_array_pro = getNowAverage($year, $type_pro);
    $point_array_pro = getAllPastEvaluationHospital($ID, $year, $type_pro);
    array_unshift($point_array_pro, $average_array_pro);
    $max_array_pro = getMaxPoint($year, $type_pro);
    $this->SetFontSize($this->title_font_size);
    $str = $category_str_pro."得点";
    $this->Write(8, $str);
    $this->Ln();
    $this->createCell($title_array_pro, $point_array_pro, $max_array_pro);
    $this->Ln();
    $this->Ln();
    $type_out = 3;
    $category_str_out = getCategoryStr($type_out);
    $title_array_out = getLargeItem($year, $type_out);
    $average_array_out = getNowAverage($year, $type_out);
    $point_array_out = getAllPastEvaluationHospital($ID, $year, $type_out);
    array_unshift($point_array_out, $average_array_out);
    $max_array_out = getMaxPoint($year, $type_out);
    $this->SetFontSize($this->title_font_size);
    $str = $category_str_out."（患者満足度）";
    $this->Write(8, $str);
    $this->Ln();
    $this->createCell($title_array_out, $point_array_out, $max_array_out);
    $this->Ln();
    $this->Ln();
    $this->AddPage();
    $this->SetFontSize($this->title_font_size);
    $str = $category_str_out."（患者1000人あたりインシデント発生率）";
    $this->Write(8, $str);
    $this->Ln();
    $enq_average = getEncAverage($year);
    $enq_result = getEncResult($year, $ID);
    array_unshift($enq_result, $enq_average);
    $this->createCellOutCome($enq_result);
    $this->Ln();
    $this->Ln();
    $this->Ln();
    $this->SetFontSize($this->title_font_size);
    $str = $category_str."総合評価";
    $this->Write(8, $str);
    $this->Ln();
    $max_array = array($max_array_str[0], $max_array_pro[0], $max_array_out[0]);
    $point_array = array($point_array_str[1], $point_array_pro[1], $point_array_out[1]);
    $this->CreateChartBase($this->top_count, 100);
    $this->CreateRaderChartTitle($title_array_str);
    for ($i = 0; $i < 4; $i++) {
      $this->SetDrawColor($this->data_line_color[$i][0], $this->data_line_color[$i][1], $this->data_line_color[$i][2]);
      $this->SetDash($this->dash_space_length, $this->dash_array[$i]);
      $this->CreateRaderChart($point_array[$i], $max_array[$i]);
    }
    $this->createLineDetails($point_array);
    $this->Ln();
    $this->SetFontSize(14);
    $str = "当該領域で「回答しない」が１項目以上あった場合は0点として表示されます。";
    $this->Write(8, $str);
    $this->Ln();
    $str = "※満点を100として%で表示しております。";
    $this->Write(8, $str);
  }

  public function createCell($title_array, $point_array, $max_array) {
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
      $tmp = mb_substr($title.str_repeat("　", 10), 0, 10)." ( ".$max_array[0][$i]." )";
      $this->MultiCell($this->cell_title_width, $this->cell_data_height, $tmp, 1, "L");
      $next_y = $this->GetY();
      $this->SetXY($curret_x + $this->cell_title_width, $curret_y);
      for ($j = 0; $j < 4; $j++) {
        $tmp_point = $point_array[$j][$i];
        if (!isset($tmp_point) || $tmp_point === "") {
          $tmp_point = "-";
        } else {
          $tmp_point = sprintf($tmp_point, "%01.01f").
              " ( ".sprintf("%01.01f", ($tmp_point / $max_array[0][$i] * 100))."% )";
        }
        $this->Cell($this->cell_point_width, $next_y - $curret_y, $tmp_point, 1, 0, "R");
      }
      $this->Ln();
    }
  }

  public function createCellOutCome($enq_result) {
    $this->SetFontSize($this->cell_font_size);
    $this->Cell($this->cell_title_width, $this->cell_header_height, "項目", 1, 0, 'C');
    for ($i=0; $i<count($this->str_point); $i++) {
      $this->Cell($this->cell_point_width, $this->cell_header_height, $this->str_point[$i], 1, 0, 'C');
    }
    $this->Ln();
    for ($i = 0; $i < 5; $i++) {
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
      for ($j = 0; $j < 4; $j++) {
        $tmp_point = $enq_result[$j][$i];
        if (!isset($tmp_point) || $tmp_point === "") {
          $tmp_point = "-";
        }
        $this->Cell($this->cell_point_width, $next_y - $curret_y, strval($tmp_point), 1, 0, 'R');
      }
      $this->Ln();
    }
  }

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
