<?php

/*******************************************************************
ファイル名：makedata.php
概要　　　：管理者画面：CVS/PDF作成
									(C)2005,University of Hyougo.
*******************************************************************/

// 外部ファイルの読み込み
require_once "setup.php";
require_once __DIR__ . '/../lib/chart_lib.php';
require_once __DIR__ . '/../lib/chart_lib_hosp2.php';
require_once __DIR__ . '/../lib/chart_lib_ward2.php';

// データベース接続の確立
$db = Connection::connect();

/*******************************************************************
 * createCSVData
 * 概要：CSVデータを作成
 * 引数：$like 検索条件(LIKE文で使用する形式)
 * 戻値：なし
 *******************************************************************/
function createCSVData($like) {
    global $db;
    $ret = '';

    $sql = "SELECT id, category FROM category ORDER BY id";
    if ($res = mysqli_query($db, $sql)) {
        while ($row = mysqli_fetch_object($res)) {
            $category = $row->id;
            $ret .= $row->category . "\n\n";

            if ($category == 1 || $category == 2) {
                $ret .= "研究へのご協力のお願い\n";
                $ret .= "ID,回答\n";
                $sql = "SELECT usr.uid, usr.cooperation 
                        FROM usr 
                        WHERE usr.id = ? AND usr.uid LIKE ? AND usr.uid IS NOT NULL AND usr.comp = ?";
                if ($stmt = mysqli_prepare($db, $sql)) {
                    $likeParam = $like . '%';
                    $comp = Config::COMPLETE;
                    mysqli_stmt_bind_param($stmt, 'iss', $category, $likeParam, $comp);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $uid, $cooperation);
                    while (mysqli_stmt_fetch($stmt)) {
                        $ret .= $uid . "," . $cooperation . "\n";
                    }
                    mysqli_stmt_close($stmt);
                }
                $ret .= "\n";
            }

            $sql = "SELECT item1.no AS no1, item2.no AS no2, item3.no AS no3, item4.no AS no4, item4.id1, item4.id2, item4.id3, item4.id4, item4.question 
                    FROM item1, item2, item3, item4 
                    WHERE item1.id = item4.id AND item1.id1 = item4.id1 AND item2.id = item4.id AND item2.id1 = item4.id1 AND item2.id2 = item4.id2 
                    AND item3.id = item4.id AND item3.id1 = item4.id1 AND item3.id2 = item4.id2 AND item3.id3 = item4.id3 
                    AND item4.id = ? 
                    ORDER BY item1.no, item2.no, item3.no, item4.no";
            if ($stmt = mysqli_prepare($db, $sql)) {
                mysqli_stmt_bind_param($stmt, 'i', $category);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $no1, $no2, $no3, $no4, $id1, $id2, $id3, $id4, $question);
                while (mysqli_stmt_fetch($stmt)) {
                    $ret .= $no1 . $no2 . $no3 . " " . preg_replace("/\r\n|\n|\r/", "", $question) . "\n";
                    $ret .= "ID,回答,得点\n";
                    
                    $sql2 = "SELECT usr_ans.uid, ans.point, usr_ans.ans, ans.answer, item4.qtype 
                             FROM usr_ans 
                             LEFT JOIN item4 ON item4.id4 = usr_ans.id4 AND item4.id3 = usr_ans.id3 AND item4.id2 = usr_ans.id2 AND item4.id1 = usr_ans.id1 AND item4.id = usr_ans.id 
                             LEFT JOIN ans ON usr_ans.ans = ans.ans_id AND usr_ans.id4 = ans.id4 AND usr_ans.id3 = ans.id3 AND usr_ans.id2 = ans.id2 AND usr_ans.id1 = ans.id1 AND usr_ans.id = ans.id 
                             WHERE item4.id = ? AND usr_ans.id1 = ? AND usr_ans.id2 = ? AND usr_ans.id3 = ? AND usr_ans.id4 = ? AND usr_ans.uid LIKE ? AND usr.uid IS NOT NULL AND usr.comp = ? 
                             ORDER BY usr_ans.uid";
                    if ($stmt2 = mysqli_prepare($db, $sql2)) {
                        mysqli_stmt_bind_param($stmt2, 'iiiiiiss', $category, $id1, $id2, $id3, $id4, $likeParam, $comp);
                        mysqli_stmt_execute($stmt2);
                        mysqli_stmt_bind_result($stmt2, $uid, $point, $ans, $answer, $qtype);
                        while (mysqli_stmt_fetch($stmt2)) {
                            $answerText = $qtype == Config::SELECT ? ($ans === "0" ? "回答しない" : $answer) : preg_replace("/\r\n|\n|\r/", "", $ans);
                            $ret .= $uid . "," . $answerText . "," . ($qtype == Config::SELECT ? $point : "") . "\n";
                        }
                        mysqli_stmt_close($stmt2);
                    }
                    $ret .= "\n";
                }
                mysqli_stmt_close($stmt);
            }
        }
        mysqli_free_result($res);
    }
    return $ret;
}

/*******************************************************************
 * CreateCSV_textonly
 * 概要：テキストによる回答方法を持つ
 * 引数：$file_name
 * 戻値：CSVデータダウンロード
 *******************************************************************/
function CreateCSV_textonly($file_name) {
    global $db;
    $ret = '';

    $sql = "SELECT id, category FROM category ORDER BY id";
    if ($res = mysqli_query($db, $sql)) {
        while ($fld = mysqli_fetch_object($res)) {
            $id = $fld->id;
            $ret .= $fld->category . "\r\n\r\n";

            if ($id == 1 || $id == 2) {
                $ret .= "研究へのご協力のお願い\n";
                $ret .= "ID,回答\n";
                $sql = "SELECT usr.uid, usr.cooperation 
                        FROM usr 
                        WHERE usr.id = ? AND usr.uid LIKE ? AND usr.uid IS NOT NULL AND usr.comp = ?";
                if ($stmt = mysqli_prepare($db, $sql)) {
                    $likeParam = $file_name . '%';
                    $comp = Config::COMPLETE;
                    mysqli_stmt_bind_param($stmt, 'iss', $id, $likeParam, $comp);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $uid, $cooperation);
                    while (mysqli_stmt_fetch($stmt)) {
                        $ret .= $uid . "," . $cooperation . "\n";
                    }
                    mysqli_stmt_close($stmt);
                }
                $ret .= "\n";
            }

            $sql2 = "SELECT item4.id, item4.id1, item4.id2, item4.id3, MIN(item1.no) AS no1, MIN(item2.no) AS no2, MIN(item3.no) AS no3 
                     FROM item1 
                     LEFT JOIN item2 ON item2.id = item1.id AND item2.id1 = item1.id1 
                     LEFT JOIN item3 ON item3.id = item2.id AND item3.id1 = item2.id1 AND item3.id2 = item2.id2 
                     LEFT JOIN item4 ON item4.id = item3.id AND item4.id1 = item3.id1 AND item4.id2 = item3.id2 AND item4.id3 = item3.id3 
                     WHERE item4.qtype = '2' AND item1.id = ? 
                     GROUP BY item4.id, item4.id1, item4.id2, item4.id3 
                     ORDER BY no1, no2, no3";
            if ($q_res = mysqli_prepare($db, $sql2)) {
                mysqli_stmt_bind_param($q_res, 'i', $id);
                mysqli_stmt_execute($q_res);
                mysqli_stmt_bind_result($q_res, $q_id, $q_id1, $q_id2, $q_id3, $no1, $no2, $no3);
                while (mysqli_stmt_fetch($q_res)) {
                    $sql3 = "SELECT id4, question 
                             FROM item4 
                             WHERE id = ? AND id1 = ? AND id2 = ? AND id3 = ? 
                             ORDER BY id4";
                    if ($q4_rs = mysqli_prepare($db, $sql3)) {
                        mysqli_stmt_bind_param($q4_rs, 'iiii', $q_id, $q_id1, $q_id2, $q_id3);
                        mysqli_stmt_execute($q4_rs);
                        mysqli_stmt_bind_result($q4_rs, $q4_id4, $question);
                        while (mysqli_stmt_fetch($q4_rs)) {
                            $ret .= $no1 . $no2 . $no3 . " " . preg_replace("/\r\n|\n|\r/", "", $question) . "\n";
                            $ret .= "ID,回答,得点\r\n";

                            $sql4 = "SELECT usr_ans.uid, usr_ans.ans, ans.point 
                                     FROM usr_ans 
                                     LEFT JOIN item4 ON item4.id4 = usr_ans.id4 AND item4.id3 = usr_ans.id3 AND item4.id2 = usr_ans.id2 AND item4.id1 = usr_ans.id1 AND item4.id = usr_ans.id 
                                     LEFT JOIN ans ON usr_ans.ans = ans.ans_id AND usr_ans.id4 = ans.id4 AND usr_ans.id3 = ans.id3 AND usr_ans.id2 = ans.id2 AND usr_ans.id1 = ans.id1 AND usr_ans.id = ans.id 
                                     WHERE item4.id = ? AND item4.id1 = ? AND item4.id2 = ? AND item4.id3 = ? AND item4.id4 = ? AND usr_ans.uid LIKE ? 
                                     ORDER BY usr_ans.uid";
                            if ($a_rs = mysqli_prepare($db, $sql4)) {
                                $likeParam = $file_name . '%';
                                mysqli_stmt_bind_param($a_rs, 'iiiiis', $q_id, $q_id1, $q_id2, $q_id3, $q4_id4, $likeParam);
                                mysqli_stmt_execute($a_rs);
                                mysqli_stmt_bind_result($a_rs, $a_uid, $a_ans, $a_point);
                                while (mysqli_stmt_fetch($a_rs)) {
                                    $ret .= $a_uid . "," . preg_replace("/\r\n|\n|\r/", "", $a_ans) . "," . $a_point . "\r\n";
                                }
                                mysqli_stmt_close($a_rs);
                            }
                            $ret .= "\r\n";
                        }
                        mysqli_stmt_close($q4_rs);
                    }
                }
                mysqli_stmt_close($q_res);
            }
        }
        mysqli_free_result($res);
    }
    return $ret;
}

/*******************************************************************
 * getUserList
 * 概要：条件を指定してユーザ一覧を取得します。
 * 引数：$like
 * 戻値：ユーザ一覧
 *******************************************************************/
function getUserList($like) {
    global $db;
    $sql = "SELECT uid FROM usr WHERE uid LIKE ? AND comp = '1' AND del = '1' ORDER BY uid";
    $array_usr = [];
    if ($stmt = mysqli_prepare($db, $sql)) {
        $likeParam = $like . '%';
        mysqli_stmt_bind_param($stmt, 's', $likeParam);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $uid);
        while (mysqli_stmt_fetch($stmt)) {
            $array_usr[] = $uid;
        }
        mysqli_stmt_close($stmt);
    }
    return $array_usr;
}

/*******************************************************************
 * createFilaName
 * 概要：CVS/PDFファイル名を作成
 * 引数：なし
 * 戻値：CVS/PDFファイル名
 *******************************************************************/
function createFilaName() {
    if ($_POST['ftype'] == 'csv_a' || $_POST['ftype'] == 'pdf_a') {
        return getYear();
    } elseif ($_POST['ftype'] == 'csv_h' || $_POST['ftype'] == 'csv_t' || $_POST['ftype'] == 'pdf_h' || $_POST['ftype'] == 'pdf_ht' || $_POST['recom_h']) {
        return getYear() . "-" . $_POST['pref1'] . "-" . $_POST['hos1'];
    } elseif ($_POST['ftype'] == 'csv_w' || $_POST['ftype'] == 'csv_wt' || $_POST['ftype'] == 'pdf_w' || $_POST['ftype'] == 'pdf_wt' || $_POST['recom_w']) {
        return getYear() . "-" . $_POST['pref2'] . "-" . $_POST['hos2'] . "-" . $_POST['ward'];
    }
}

/*******************************************************************
 * getYear
 * 概要：現在年度取得
 * 引数：なし
 * 戻値：年度配列
 *******************************************************************/
function getYear() {
    global $db;
    $sql = "SELECT year FROM year";
    if ($res = mysqli_query($db, $sql)) {
        $fld = mysqli_fetch_object($res);
        $year = $fld->year;
        mysqli_free_result($res);
        return $year;
    }
    return null;
}

/*******************************************************************
 * downloadZip
 * 概要：生成したPDFファイルをZIPしてダウンロードさせる
 * 引数：$zip zipファイル名
 * 戻値：なし
 *******************************************************************/
function downloadZip($zip) {
		exec('rm -f ./pdf_temporarily_saved/*.zip'); // zipファイル削除
		exec('/usr/local/bin/zip ./pdf_temporarily_saved/' . escapeshellarg($zip) . ' ./pdf_temporarily_saved/*.pdf'); // PDFファイルをZIP圧縮
		exec('rm -f ./pdf_temporarily_saved/*.pdf'); // PDFファイル削除
    header("Location: ./pdf_temporarily_saved/" . $zip); // ZIPファイルダウンロード
}

/*
 * リコメンデーション
 * recom_csv($file_name);
 */
function recom_csv($file_name) {
    global $db, $year, $usrtype;
    $ret = '';

    $sql = "SELECT id1 FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
    if ($stmt = mysqli_prepare($db, $sql)) {
        mysqli_stmt_bind_param($stmt, 'si', $year, $usrtype);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id1);
        while (mysqli_stmt_fetch($stmt)) {
            $id1_array[] = $id1;
        }
        mysqli_stmt_close($stmt);
    }

    // 集計一時テーブルの初期化
    $sql = "CREATE TEMPORARY TABLE t_ans_total (id int(11) NOT NULL default '0', id1 int(11) NOT NULL default '0', id2 int(11) NOT NULL default '0', uid varchar(17) NOT NULL default '', point float NOT NULL default '0', PRIMARY KEY (id, id1, id2, uid))";
    mysqli_query($db, $sql);
    mysqli_query($db, "TRUNCATE t_ans_total");

    // 集計結果を一時テーブルに保存
    $sql = "INSERT INTO t_ans_total(id, id1, uid, point) 
            SELECT usr_ans.id, usr_ans.id1, usr_ans.uid, SUM(usr_ans.point) AS sum_point 
            FROM usr_ans 
            LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 
            LEFT JOIN usr ON usr.uid = usr_ans.uid AND usr.id = usr_ans.id 
            WHERE item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE ? 
            GROUP BY usr_ans.id, usr_ans.id1, usr_ans.uid";
    if ($stmt = mysqli_prepare($db, $sql)) {
        $likeParam = $file_name . '%';
        mysqli_stmt_bind_param($stmt, 's', $likeParam);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $ret .= "高得点基準以上の大項目\n";
    $ret .= "ID,ID1,高得点基準,平均点,項目\n";
    $sql = "SELECT t_ans_total.id, t_ans_total.id1, item1.name, ROUND(AVG(t_ans_total.point), 1) AS avg_point, item1.up_recommendation 
            FROM t_ans_total 
            LEFT JOIN item1 ON item1.id = t_ans_total.id AND item1.id1 = t_ans_total.id1 
            GROUP BY t_ans_total.id, t_ans_total.id1 
            HAVING avg_point >= item1.up_recommendation 
            ORDER BY t_ans_total.id, t_ans_total.id1";
    if ($res = mysqli_query($db, $sql)) {
        while ($fld = mysqli_fetch_object($res)) {
            $ret .= $fld->id . "," . $fld->id1 . "," . preg_replace("/\r|\n/", '', $fld->up_recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";
        }
        mysqli_free_result($res);
    }

    $ret .= "低得点基準以下の大項目\n";
    $ret .= "ID,ID1,低得点基準,平均点,項目\n";
    $sql = "SELECT t_ans_total.id, t_ans_total.id1, item1.name, ROUND(AVG(t_ans_total.point), 1) AS avg_point, item1.recommendation 
            FROM t_ans_total 
            LEFT JOIN item1 ON item1.id = t_ans_total.id AND item1.id1 = t_ans_total.id1 
            GROUP BY t_ans_total.id, t_ans_total.id1 
            HAVING avg_point <= item1.recommendation 
            ORDER BY t_ans_total.id, t_ans_total.id1";
    if ($res = mysqli_query($db, $sql)) {
        while ($fld = mysqli_fetch_object($res)) {
            $ret .= $fld->id . "," . $fld->id1 . "," . preg_replace("/\r|\n/", '', $fld->recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";
        }
        mysqli_free_result($res);
    }

    // 集計一時テーブルの初期化
    mysqli_query($db, "TRUNCATE t_ans_total");

    // 集計結果を一時テーブルに保存
    $sql = "INSERT INTO t_ans_total(id, id1, id2, uid, point) 
            SELECT usr_ans.id, usr_ans.id1, usr_ans.id2, usr_ans.uid, SUM(usr_ans.point) AS point 
            FROM usr_ans 
            LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 
            LEFT JOIN usr ON usr.uid = usr_ans.uid AND usr.id = usr_ans.id 
            WHERE item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE ? 
            GROUP BY usr_ans.id, usr_ans.id1, usr_ans.id2, usr_ans.uid";
    if ($stmt = mysqli_prepare($db, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $likeParam);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $ret .= "高得点基準以上の中項目\n";
    $ret .= "ID,ID1,ID2,高得点基準,平均点,項目\n";
    $sql = "SELECT t_ans_total.id, t_ans_total.id1, t_ans_total.id2, item2.name, ROUND(AVG(t_ans_total.point), 1) AS avg_point, item2.up_recommendation, MIN(item1.no) AS no1, MIN(item2.no) AS no2 
            FROM t_ans_total 
            LEFT JOIN item2 ON item2.id = t_ans_total.id AND item2.id1 = t_ans_total.id1 AND item2.id2 = t_ans_total.id2 
            LEFT JOIN item1 ON item1.id = item2.id AND item1.id1 = item2.id1 
            GROUP BY t_ans_total.id, t_ans_total.id1, t_ans_total.id2 
            HAVING avg_point >= item2.up_recommendation 
            ORDER BY t_ans_total.id, no1, no2";
    if ($res = mysqli_query($db, $sql)) {
        while ($fld = mysqli_fetch_object($res)) {
            $ret .= $fld->id . "," . $fld->no1 . "," . $fld->no2 . "," . preg_replace("/\r|\n/", '', $fld->up_recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";
        }
        mysqli_free_result($res);
    }

    $ret .= "低得点基準以下の中項目\n";
    $ret .= "ID,ID1,ID2,低得点基準,平均点,項目\n";
    $sql = "SELECT t_ans_total.id, t_ans_total.id1, t_ans_total.id2, item2.name, ROUND(AVG(t_ans_total.point), 1) AS avg_point, item2.recommendation, MIN(item1.no) AS no1, MIN(item2.no) AS no2 
            FROM t_ans_total 
            LEFT JOIN item2 ON item2.id = t_ans_total.id AND item2.id1 = t_ans_total.id1 AND item2.id2 = t_ans_total.id2 
            LEFT JOIN item1 ON item1.id = item2.id AND item1.id1 = item2.id1 
            GROUP BY t_ans_total.id, t_ans_total.id1, t_ans_total.id2 
            HAVING avg_point <= item2.recommendation 
            ORDER BY t_ans_total.id, no1, no2";
    if ($res = mysqli_query($db, $sql)) {
        while ($fld = mysqli_fetch_object($res)) {
            $ret .= $fld->id . "," . $fld->no1 . "," . $fld->no2 . "," . preg_replace("/\r|\n/", '', $fld->recommendation) . "," . $fld->avg_point . "," . preg_replace("/\r|\n/", '', $fld->name) . "\n";
        }
        mysqli_free_result($res);
    }

    // 質問単位のリコメンデーション
    mysqli_query($db, "TRUNCATE TABLE ans_total2");

    $sql = "INSERT INTO ans_total2(id, id1, id2, id3, id4, uid, point) 
            SELECT usr_ans.id, usr_ans.id1, usr_ans.id2, usr_ans.id3, usr_ans.id4, usr_ans.uid, SUM(usr_ans.point) AS point 
            FROM usr_ans 
            LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 
            LEFT JOIN usr ON usr.uid = usr_ans.uid AND usr.id = usr_ans.id 
            WHERE item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE ? 
            GROUP BY usr_ans.id, usr_ans.id1, usr_ans.id2, usr_ans.id3, usr_ans.id4, usr_ans.uid";
    if ($stmt = mysqli_prepare($db, $sql)) {
        mysqli_stmt_bind_param($stmt, 's', $likeParam);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $ret .= "基準点以下の質問\n";
    $ret .= "ID,ID1,ID2,ID3,ID4,基準点,点数,項目\n";
    $sql = "SELECT ans_total2.id, ans_total2.id1, ans_total2.id2, ans_total2.id3, ans_total2.id4, item3.name, ROUND(AVG(ans_total2.point), 1) AS avg_point, MIN(item1.no) AS no1, MIN(item2.no) AS no2, MIN(item3.no) AS no3, MIN(item4.no) AS no4 
            FROM ans_total2 
            LEFT JOIN item4 ON item4.id = ans_total2.id AND item4.id1 = ans_total2.id1 AND item4.id2 = ans_total2.id2 AND item4.id3 = ans_total2.id3 AND item4.id4 = ans_total2.id4 
            LEFT JOIN item3 ON item3.id = item4.id AND item3.id1 = item4.id1 AND item3.id2 = item4.id2 AND item3.id3 = item4.id3 
            LEFT JOIN item2 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2 
            LEFT JOIN item1 ON item1.id = item2.id AND item1.id1 = item2.id1 
            GROUP BY ans_total2.id, ans_total2.id1, ans_total2.id2, ans_total2.id3, ans_total2.id4 
            HAVING avg_point <= 1 
            ORDER BY ans_total2.id, no1, no2, no3, no4";
    if ($res = mysqli_query($db, $sql)) {
        while ($fld = mysqli_fetch_object($res)) {
            $ret .= $fld->id . "," . $fld->no1 . "," . $fld->no2 . "," . $fld->no3 . "," . $fld->no4 . ",1," . $fld->avg_point . ",\"" . preg_replace("/\r|\n/", '', $fld->name) . "\"\n";
        }
        mysqli_free_result($res);
    }
    return $ret;
}

if ($_POST['ftype'] == 'csv_a' || $_POST['ftype'] == 'csv_h' || $_POST['ftype'] == 'csv_w' || $_POST['ftype'] == 'csv_t' || $_POST['ftype'] == 'csv_wt' || $_POST['ftype'] == 'recom_h' || $_POST['ftype'] == 'recom_w') {
    $file_name = createFilaName();
    $csv = $file_name . ".csv";

    header("Cache-Control: public");
    header("Pragma: public");
    header("Content-Type: text/octet-stream");
    header("Content-Disposition: attachment; filename=" . $csv);

    if ($_POST['ftype'] == 'csv_t' || $_POST['ftype'] == 'csv_wt') {
        echo CreateCSV_textonly($file_name);
    } elseif ($_POST['recom_h'] || $_POST['recom_w']) {
        echo recom_csv($file_name);
    } else {
        echo createCSVData($file_name);
    }
} elseif ($_POST['ftype'] == 'include') {
    // Do nothing
} elseif ($_POST['ftype'] == 'pdf_a') {
    exec('rm -f ./pdf/*.pdf');

    $file_name = createFilaName();
    $zip = $file_name . ".zip";
    $user_list = getUserList($file_name);
    $year = getYear();
    $ave_arr = array();

    for ($i = 1; $i <= 3; $i++) {
        $ave_arr[$i] = getNowAverage($year, $i);
    }

    foreach ($user_list as $user) {
        $pdf = new PDF;
        $type_no = getTypeNo(substr($user, 14));
        $pdf->SaveChart($user, $ave_arr[$type_no]);
    }

    downloadZip($zip);
} elseif ($_POST['PDF_ID']) {
    exec('rm -f ./pdf/*.pdf');

    $split_id = preg_split('/-/', $_POST['uid']);
    $year = $split_id[0];
    $type = getTypeNo($split_id[4]);
    $zip = $_POST['uid'] . ".zip";

    $ave_arr = getNowAverage($year, $type);
    $pdf = new PDF;
    $pdf->SaveChart($_POST['uid'], $ave_arr);

    downloadZip($zip);
} else {
    echo "unkown error.";
}

?>
