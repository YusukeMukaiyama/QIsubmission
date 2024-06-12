<?php
/******************************************************************
#!/usr/local/bin/php

  total_csv.php
  集計データ ( CSV )ダウンロード
                 ( C )2005-2006, University of Hyougo.
******************************************************************/

require_once("./setup.php");
require_once __DIR__ . '/../lib/chart_lib.php';
require_once __DIR__ . '/../lib/chart_lib_hosp2.php';
require_once __DIR__ . '/../lib/chart_lib_ward2.php';

/**
 * ファイル名生成関数
 * 
 * @param string $year 年度
 * @param int $type ファイルタイプ
 * @return string 生成されたファイル名
 */
function createFileName($year, $type)
{
    switch ($type) {
        case 1:
            return $year . "-structure-ttl.csv";
        case 2:
            return $year . "-process-ttl.csv";
        case 3:
            return $year . "-outcome-ttl.csv";
        case 10:
            return $year . "-ttl-avg.csv";
        case 20:
            return $year . "-ttl-enq.csv";
        default:
            die("error!");
    }
}

/**
 * カテゴリ別CSVデータ作成関数
 * 
 * @param int $type カテゴリタイプ
 */
function create_csv($type)
{
    global $db, $year, $utype;

    // アンケートカテゴリ取得
    $categories = getCategories($type);

    // CSVヘッダ作成
    $header = createCSVHeader($type, $categories);
    echo $header;

    // 回答データ集計と出力
    $data = collectCSVData($type, $categories);
    echo $data;
}

/**
 * カテゴリ取得関数
 * 
 * @param int $type カテゴリタイプ
 * @return array カテゴリIDの配列
 */
function getCategories($type)
{
    global $db;
    $query = "SELECT enquete.id FROM enquete WHERE enquete.id LIKE '$type%' GROUP BY enquete.id ORDER BY enquete.id";
    $result = mysqli_query($db, $query);
    $categories = [];
    while ($row = mysqli_fetch_object($result)) {
        $categories[] = $row->id;
    }
    mysqli_free_result($result);
    return $categories;
}

/**
 * CSVヘッダ作成関数
 * 
 * @param int $type カテゴリタイプ
 * @param array $categories カテゴリIDの配列
 * @return string CSVヘッダ
 */
function createCSVHeader($type, $categories)
{
    global $db, $utype;
    $header = "\"ID\",,,,"; // EXCELが不正なSYLK形式と判断するため、\"\"でデータを開始

    if ($type == 1 || $type == 2) {
        $header .= ",\"承諾\"";
    }

    for ($id1 = 1; $id1 < 7; $id1++) {
        $query = "SELECT item4.id1, item4.id2, item4.id3, item4.id4, item1.no AS no1, item2.no AS no2, item3.no AS no3, item4.no AS no4
                  FROM item1
                  LEFT JOIN item2 ON item2.id=item1.id AND item2.id1=item1.id1
                  LEFT JOIN item3 ON item3.id=item2.id AND item3.id1=item2.id1 AND item3.id2=item2.id2
                  LEFT JOIN item4 ON item4.id=item3.id AND item4.id1=item3.id1 AND item4.id2=item3.id2 AND item4.id3=item3.id3
                  WHERE item1.id=$type AND item1.id1=$id1
                  ORDER BY item1.no, item2.no, item3.no, item4.no";
        $result = mysqli_query($db, $query);
        while ($row = mysqli_fetch_object($result)) {
            $header .= "," . $utype[$type] . $row->no1 . $row->no2 . $row->no3 . $row->no4;
        }
        $header .= "," . $utype[$type] . $id1 . "TTL";
        mysqli_free_result($result);
    }
    $header .= "," . $utype[$type] . "TTL";

    foreach ($categories as $category) {
        $query = "SELECT id1 FROM enquete WHERE id=$category ORDER BY id1 ASC";
        $result = mysqli_query($db, $query);
        while ($row = mysqli_fetch_object($result)) {
            $header .= "," . $category . $row->id1;
        }
        mysqli_free_result($result);
    }

    return $header . "\r\n";
}

/**
 * CSVデータ集計関数
 * 
 * @param int $type カテゴリタイプ
 * @param array $categories カテゴリIDの配列
 * @return string CSVデータ
 */
function collectCSVData($type, $categories)
{
    global $db, $year, $utype;
    $data = "";

    $extraFields = getExtraFields();
    $extraFieldIds = array_column($extraFields, 'id1');

    $query = "SELECT usr.id AS usr_id, usr.uid AS usr_uid, usr.cooperation, usr_ans.id1 AS usr_ans_id1, usr_ans.id2 AS usr_ans_id2, usr_ans.id3 AS usr_ans_id3, usr_ans.id4 AS usr_ans_id4, 
                     usr_ans.ans AS usr_ans_ans, usr_ans.point AS usr_ans_point
              FROM usr
              LEFT JOIN usr_ans ON usr.uid = usr_ans.uid
              LEFT JOIN item4 ON item4.id=usr_ans.id AND item4.id1=usr_ans.id1 AND item4.id2=usr_ans.id2 AND item4.id3=usr_ans.id3 AND item4.id4=usr_ans.id4
              WHERE usr.id=$type AND usr.uid LIKE '$year-%' AND usr.comp='1' AND usr.del='1'
              ORDER BY usr.uid, item4.id1, item4.id2, item4.id3, item4.id4";
    $result = mysqli_query($db, $query);

    $currentUid = "";
    $total = 0;
    $idTotals = array_fill(1, 6, 0);
    $list = array_fill(1, 6, "");

    while ($row = mysqli_fetch_object($result)) {
        if ($currentUid !== $row->usr_uid) {
            if ($currentUid !== "") {
                $data .= formatCSVRow($currentUid, $total, $idTotals, $list, $categories, $extraFields) . "\r\n";
            }
            $currentUid = $row->usr_uid;
            $total = 0;
            $idTotals = array_fill(1, 6, 0);
            $list = array_fill(1, 6, "");

            $data .= formatCSVHeader($currentUid, $type, $row->cooperation);
        }

        if ($row->usr_ans_id1 >= 1 && $row->usr_ans_id1 <= 6) {
            $total += $row->usr_ans_point;
            $idTotals[$row->usr_ans_id1] += $row->usr_ans_point;
            $list[$row->usr_ans_id1] .= "," . $row->usr_ans_point;
        }
    }

    if ($currentUid !== "") {
        $data .= formatCSVRow($currentUid, $total, $idTotals, $list, $categories, $extraFields) . "\r\n";
    }

    mysqli_free_result($result);
    return $data;
}

/**
 * 追加フィールド取得関数
 * 
 * @return array 追加フィールドの配列
 */
function getExtraFields()
{
    global $db, $year;
    $query = "SELECT enq_usr_ans_ex.id, enq_usr_ans_ex.id1, enq_usr_ans_ex.name 
              FROM usr, enq_usr_ans_ex 
              WHERE usr.uid = enq_usr_ans_ex.uid AND usr.uid LIKE '$year-%' AND usr.comp='1' AND usr.del='1' 
              GROUP BY enq_usr_ans_ex.id, enq_usr_ans_ex.id1 
              ORDER BY enq_usr_ans_ex.id, enq_usr_ans_ex.id1";
    $result = mysqli_query($db, $query);
    $fields = [];
    while ($row = mysqli_fetch_object($result)) {
        $fields[] = ['id' => $row->id, 'id1' => $row->id1, 'name' => $row->name];
    }
    mysqli_free_result($result);
    return $fields;
}

/**
 * CSV行フォーマット関数
 * 
 * @param string $uid ユーザーID
 * @param int $total 総合計
 * @param array $idTotals 各IDの合計
 * @param array $list 各リスト
 * @param array $categories カテゴリIDの配列
 * @param array $extraFields 追加フィールドの配列
 * @return string フォーマットされたCSV行
 */
function formatCSVRow($uid, $total, $idTotals, $list, $categories, $extraFields)
{
    global $db, $year;
    $row = "=\"" . str_replace("-", "\",=\"", $uid) . "\"";
    foreach ($idTotals as $id1 => $total) {
        $row .= $list[$id1] . "," . $total;
    }
    $row .= "," . $total;

    foreach ($categories as $category) {
        $query = "SELECT ans FROM enq_usr_ans WHERE id=$category AND uid='$uid' ORDER BY id1 ASC";
        $result = mysqli_query($db, $query);
        while ($ans = mysqli_fetch_object($result)) {
            $row .= "," . str_replace(["\r", "\n"], "", $ans->ans);
        }
        mysqli_free_result($result);
    }

    foreach ($extraFields as $field) {
        $row .= "," . (isset($field['ans']) ? $field['ans'] : "");
    }

    return $row;
}

/**
 * CSVヘッダフォーマット関数
 * 
 * @param string $uid ユーザーID
 * @param int $type カテゴリタイプ
 * @param string $cooperation 協力状況
 * @return string フォーマットされたCSVヘッダ
 */
function formatCSVHeader($uid, $type, $cooperation)
{
    $header = "=\"" . str_replace("-", "\",=\"", $uid) . "\"";
    if ($type == 1 || $type == 2) {
        $header .= ",=\"" . ($cooperation == "同意する" || $cooperation == "諾" ? "1" : "0") . "\"";
    }
    return $header;
}


/*******************************************************************
getYear
  概要：現在の年度を取得します。
  引数：$db   データベースオブジェクト
  戻値：年度の配列
*******************************************************************/
function getYear()
{
  global $db;
  $sql = "SELECT year FROM year";
  $res = mysqli_query($db, $sql);
  $fld = mysqli_fetch_object($res);
  $year = $fld->year;
  return $year;
}


$utype = [1 => "S", 2 => "P", 3 => "O"];

if (isset($argv[1])) {
    $type = $argv[1];
    $year = '';
} else {
    $year = $_REQUEST['year'];
    $type = $_REQUEST['type'];
}


$db = Connection::connect(); // データベース接続

if ($year == '') {
    $year = getYear();
}

// ファイル名作成
$file_name = createFileName($year, $type);

// ヘッダー出力
header("Cache-Control: public");
header("Pragma: public");
header("Content-Type: text/octet-stream");
header("Content-Disposition: attachment; filename=$file_name");

// CSVデータ出力
if ($type == 20) {  // アンケート総合データ
    create_total_enq();
} elseif ($type == 10) {  // 総合集計データ
    create_total_csv();
} else {  // ( 1:構造 / 2:過程 / 3:アウトカム )カテゴリ別のCSV集計データ
    create_csv($type);
}

?>
