<?php
require_once('BasePDF.php');
require_once('DataHelper.php');

class PDF extends BasePDF {
    // ユーザーごとのPDFをブラウザで表示
    public function ViewChart($id) {
        $arr_avg = getIndividualEvaluation($id);
        $this->CreateChart($id, $arr_avg, true);
        $this->Output();
    }

    // ユーザーごとのPDFを保存
    public function SaveChart($id, $arr_avg, $filepath) {
        $this->CreateChart($id, $arr_avg, false);
        $this->Output($filepath, 'F');
    }

    // ユーザーごとのPDFを作成
    public function CreateChart($id, $arr_avg, $view) {
        $title_array = getLargeItem(date('Y'), 1);
        $max_array = getMaxPoint(date('Y'), 1);
        $this->createCell($title_array, $arr_avg, $max_array);
        $this->AddPage();
        $this->createChartBase(count($title_array), 100);
        $this->createRaderChartTitle($title_array);
        $this->createRaderChart($arr_avg, 100);
        $this->createLineDetails($arr_avg);
        if ($view) {
            $this->Output();
        }
    }
}
?>
