<?php
require_once('TCPDF-main/tcpdf.php');

class BasePDF extends TCPDF {
    // プロパティ
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

    protected $log_file;


    // 初期化メソッド
    public function __construct() {
        parent::__construct();
        $this->AddPage();
        $this->SetFont('zenoldmincho', '', 12);
    }

    // エラーログ設定
    public function setUpErrorLogging() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $log_file = '/home/xs728645/tuneup-works.com/public_html/qisystem/lib/liberror.log';
        ini_set('log_errors', 1);
        ini_set('error_log', $log_file);
    }

    // エラーログ記録
    public function logError($message) {
        error_log($message, 3, $this->log_file);
    }

    // セル作成
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
            $title = $title_array[$i] ?? "-";
            $tmp = mb_substr($title_array[$i] . str_repeat("　", 10), 0, 10) . " ( " . $max_array[0][$i] . " )";
            $this->MultiCell($this->cell_title_width, $this->cell_data_height, $tmp, 1, "L");
            $next_y = $this->GetY();
            $this->SetXY($curret_x + $this->cell_title_width, $curret_y);
            for ($j = 0; $j < 4; $j++) {
                $tmp_point = $arr_pt[$j][$i] ?? "-";
                if ($tmp_point !== "-") {
                    $tmp_point = sprintf($arr_pt[$j][$i], "%01.01f") . " ( " . sprintf("%01.01f", ($arr_pt[$j][$i] / $max_array[0][$i] * 100)) . "% )";
                }
                $this->Cell($this->cell_point_width, $next_y - $curret_y, $tmp_point, 1, 0, "R");
            }
            $this->Ln();
        }
    }

    // 転倒/転落/褥瘡/院内感染/誤薬のセル作成
    public function createCellOutCome($enq_result) {
        $this->SetFontSize($this->cell_font_size);
        $this->Cell($this->cell_title_width, $this->cell_header_height, "項目", 1, 0, 'C');
        for ($i = 0; $i < count($this->str_point); $i++) {
            $this->Cell($this->cell_point_width, $this->cell_header_height, $this->str_point[$i], 1, 0, 'C');
        }
        $this->Ln();
        for ($i = 0; $i < 5; $i++) {
            $curret_x = $this->GetX();
            $curret_y = $this->GetY();
            $enq_title = ["転倒", "転落", "褥瘡", "院内感染", "誤薬"][$i] ?? "";
            $this->MultiCell($this->cell_title_width, $this->cell_data_height, $enq_title, 1, 'L');
            $next_y = $this->GetY();
            $this->SetXY($curret_x + $this->cell_title_width, $curret_y);
            for ($j = 0; $j < 4; $j++) {
                $tmp_point = $enq_result[$j][$i] ?? "-";
                $this->Cell($this->cell_point_width, $next_y - $curret_y, strval($tmp_point), 1, 0, 'R');
            }
            $this->Ln();
        }
    }

    // レーダーチャートのベース作成
    public function createChartBase($point_count, $max_chart) {
       $rad = $point_count != 0 ? 360 / $point_count : 0;
        $this->SetLineWidth(0.1);
        $this->SetDrawColor($this->base_line_color);
        for ($i = 0; $i < $point_count; $i++) {
            $current_x = $this->size * sin(deg2rad($rad * $i));
            $current_y = $this->size * cos(deg2rad($rad * $i));
            $this->Line($this->center_x, $this->center_y, $this->center_x + $current_x, $this->center_y - $current_y);
        }
        for ($j = 1; $j < $this->round_count + 1; $j++) {
            $top_array = [];
            for ($i = 0; $i < $point_count; $i++) {
                $radius = $this->size / $this->round_count * $j;
                $top_x = $this->center_x + $radius * sin(deg2rad($rad * $i));
                $top_y = $this->center_y - $radius * cos(deg2rad($rad * $i));
                $top_array[] = $top_x;
                $top_array[] = $top_y;
            }
            $this->Polygon($top_array, "D");
        }
        for ($j = 0; $j <= $this->round_count; $j++) {
            $interbal_size = $this->size / $this->round_count;
            $interbal_data = $max_chart / $this->round_count;
            $str = (string)($interbal_data * $j);
            $this->SetXY(($this->center_x - $this->GetStringWidth($str) - 2), ($this->center_y - $interbal_size * $j));
            $this->SetFontSize($this->percent_font_size);
            $this->Write(0, $str);
        }
    }

    // レーダーチャートのタイトル描画
    public function createRaderChartTitle($title_array) {
        $this->SetFontSize($this->item_font_size);
        $title_count = count($title_array);
        if ($title_count == 0) return;
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

    // レーダーチャートのデータ描画
    public function createRaderChart($arr_pt, $max) {
        $this->SetLineWidth($this->data_line_width);
        $point_count = count($arr_pt[0]);
        $rad = $point_count != 0 ? 360 / $point_count : 0;
        for ($j = 0; $j < 4; $j++) {
            $top_array = [];
            $this->SetDrawColorArray($this->data_line_color[$j]);
            $this->SetDash(0, $this->dash_array[$j]);
            for ($i = 0; $i < $point_count; $i++) {
                $top_x = $this->center_x + $this->size * ($arr_pt[$j][$i] / $max) * sin(deg2rad($rad * $i));
                $top_y = $this->center_y - $this->size * ($arr_pt[$j][$i] / $max) * cos(deg2rad($rad * $i));
                $top_array[] = $top_x;
                $top_array[] = $top_y;
            }
            $this->Polygon($top_array, 'D');
        }
    }

    // レーダーチャートの線の説明作成
    public function createLineDetails($arr_pt) {
        $point_count = count($arr_pt[0]);
        for ($j = 0; $j < 4; $j++) {
            $this->SetXY(150, 40 + 10 * $j);
            $this->SetDrawColorArray($this->data_line_color[$j]);
            $this->SetLineWidth(2);
            $this->SetDash(0, $this->dash_array[$j]);
            $this->Line(150, 45 + 10 * $j, 160, 45 + 10 * $j);
            $this->SetDash(0, []);
            $this->SetLineWidth(0.1);
            $this->SetXY(160, 42 + 10 * $j);
            $this->Write(0, $this->str_point[$j]);
        }
    }

    // 点線の設定
    public function setDash($space, $line_length_array) {
        if ($space !== 0) {
            $dash_space_length = $space;
        }
        if (is_array($line_length_array)) {
            $dash_array = $line_length_array;
        } else {
            $dash_array = [];
        }
    }
}
?>
