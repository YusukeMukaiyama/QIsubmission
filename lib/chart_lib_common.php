<?php
/*-------------------------------------------------------------------------------------------------
chart_lib_common.php
ユーザ毎の結果PDF作成ライブラリにおける共通要項
-------------------------------------------------------------------------------------------------*/
require_once ( "../admin/setup.php" );
require_once('TCPDF-main/tcpdf.php');

$db = Connection::connect();	

class BasePDF extends TCPDF {
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
  protected $center_y = 140;
  protected $size = 40;
  protected $base_line_color = 192;
  protected $round_count = 4;
  protected $percent_font_size = 10;
  protected $item_font_size = 14;
  protected $data_line_color = array(array(192, 0, 0), array(0, 192, 0), array(0, 0, 192), array(192, 0, 192));
  protected $data_line_width = 0.5;
  protected $dash_array = array(array(5), array(), array(10, 3), array(10, 3, 3));
  protected $dash_space_length = 5;

  // レーダーチャートのベース部を作成
  function CreateChartBase($point_count, $max_chart) {
      $rad = 360 / $point_count;

      // 線の太さ
      $this->SetLineWidth(0.1);
      // 線の色
      $this->SetDrawColor($this->base_line_color);

      // 放射線を作成
      for ($i = 0; $i < $point_count; $i++) {
          $current_x = $this->size * sin(deg2rad($rad * $i));
          $current_y = $this->size * cos(deg2rad($rad * $i));
          $this->Line($this->center_x, $this->center_y, $this->center_x + $current_x, $this->center_y - $current_y);
      }

      // 外周の頂点を作成
      for ($j = 1; $j < $this->round_count + 1; $j++) {
          $top_array = array();
          for ($i = 0; $i < $point_count; $i++) {
              $radius = $this->size / $this->round_count * $j;
              $top_x = $this->center_x + $radius * sin(deg2rad($rad * $i));
              $top_y = $this->center_y - $radius * cos(deg2rad($rad * $i));
              $top_array[] = $top_x;
              $top_array[] = $top_y;
          }

          // 外周を描画
          $this->Polygon($top_array, "D");
      }

      // パーセント表示
      for ($j = 0; $j <= $this->round_count; $j++) {
          $interbal_size = $this->size / $this->round_count;
          $interbal_data = $max_chart / $this->round_count;
          $str = (string)($interbal_data * $j);
          $this->SetXY(($this->center_x - $this->GetStringWidth($str) - 2), ($this->center_y - $interbal_size * $j));
          $this->SetFontSize($this->percent_font_size);
          $this->Write(0, $str);
      }
  }

  // レーダーチャートのタイトル部を描画
  function CreateRaderChartTitle($title_array) {
      $this->SetFontSize($this->item_font_size);
      $title_count = count($title_array);
      if ($title_count == 0) {
          return;
      }

      $rad = 360 / $title_count;

      for ($i = 0; $i < $title_count; $i++) {
          $title_x = $this->center_x + $this->size * sin(deg2rad($rad * $i));
          $title_y = $this->center_y - $this->size * cos(deg2rad($rad * $i));

          if ($title_x == $this->center_x) {
              $title_x -= $this->GetStringWidth($title_array[$i]) / 2;
              if ($title_y < $this->center_y) {
                  $title_y -= $this->percent_font_size / 2;
              } else {
                  $title_y += 4;
              }
          } else if ($title_x < $this->center_x) {
              $title_x -= ($this->GetStringWidth($title_array[$i]) + 2);
          }

          $this->SetXY($title_x, $title_y);
          $this->Write(0, $title_array[$i]);
      }
  }

  // レーダーチャートのデータ部を描画
  function CreateRaderChart($point_array, $max) {
      if (!$max) {
          return;
      }

      $point_count = count($point_array);
      if ($point_count == 0) {
          return;
      }

      $top_data_array = array();
      $rad = 360 / $point_count;

      $this->SetLineWidth($this->data_line_width);

      for ($i = 0; $i < $point_count; $i++) {
          if (!isset($max[$i]) || $max[$i] == 0) {
              return;
          }

          if (!isset($point_array[$i]) || $point_array[$i] === "") {
              $point_array[$i] = 0;
          }

          $rate = $max[$i] / $this->size;
          if ($point_array[$i] == 0) {
              $top_data_array[] = $this->center_x + 0.1 * sin(deg2rad($rad * $i));
              $top_data_array[] = $this->center_y + 0.1 * cos(deg2rad($rad * $i));
          } else {
              $top_data_array[] = $this->center_x + $point_array[$i] / $rate * sin(deg2rad($rad * $i));
              $top_data_array[] = $this->center_y - $point_array[$i] / $rate * cos(deg2rad($rad * $i));
          }
      }

      $this->Polygon($top_data_array);
  }

  // 点線の設定を行う
  function SetDash($space, $line_length_array) {
      $array_count = count($line_length_array);

      if ($array_count > 0) {
          $str_define = "[";
          for ($i = 0; $i < $array_count; $i++) {
              $str_define .= sprintf("%.3f %.3f", $line_length_array[$i], $space);
              if ($i != $array_count - 1) {
                  $str_define .= " ";
              }
          }
          $str_define .= "] 0 d";
      } else {
          $str_define = "[] 0 d";
      }
      $this->_out($str_define);
  }
}
