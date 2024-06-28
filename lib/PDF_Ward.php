<?php
require_once('BasePDF.php');
require_once('DataHelper.php');

class PDF_Ward extends BasePDF {
    // 病棟ごとのPDFをブラウザで表示
    public function ViewChart($id) {
        $arr_avg = getEvaluationWard($id);
        $this->CreateChart($id, $arr_avg);
        $this->Output();
    }

    // 病棟ごとのPDFを保存
    public function SaveChart($ward, $filepath) {
        $arr_avg = getEvaluationWard($ward);
        $this->CreateChart($ward, $arr_avg);
        $this->Output($filepath, 'F');
    }

    // 病棟ごとのPDFを作成
    public function CreateChart($id, $arr_avg) { // ここで $arr_avg を引数として追加
        $title_array = getLargeItem(date('Y'), 1);
        $max_array = getMaxPoint(date('Y'), 1);
        $this->createCell($title_array, $arr_avg, $max_array);
        $this->AddPage();
        $this->createChartBase(count($title_array), 100);
        $this->createRaderChartTitle($title_array);
        $this->createRaderChart($arr_avg, 100);
        $this->createLineDetails($arr_avg);
        $this->Output();
    }
}
?>
