<?php
require_once('BasePDF.php');
require_once('DataHelper.php');

class PDF_Hosp extends BasePDF {
    // 病院ごとのPDFをブラウザで表示
    public function ViewChart($id) {
        $arr_avg = getAllPastEvaluationHospital($id, date('Y'), 1);
        $this->CreateChart($id, $arr_avg);
        $this->Output();
    }

    // 病院ごとのPDFを保存
    public function SaveChart($hosp, $filepath) {
        $arr_avg = getAllPastEvaluationHospital($hosp, date('Y'), 1);
        $this->CreateChart($hosp, $arr_avg);
        $this->Output($filepath, 'F');
    }

    // 病院ごとのPDFを作成
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
