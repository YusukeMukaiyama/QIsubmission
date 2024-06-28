<?php
// データベース接続関数
require_once __DIR__ . '/../admin/setup.php';

$db = Connection::connect();;

// 大項目を取得する関数
function getLargeItem($year, $type) {
    $db = Connection::connect();;
    $sql = "SELECT id1, name FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $year, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $title_array = [];
    while ($row = $result->fetch_assoc()) {
        $title_array[] = $row['name'];
    }
    $stmt->close();
    $db->close();
    return $title_array;
}

// 全国平均を取得する関数
function getNowAverage($year, $usrtype, $avg_calc = TRUE) {
    $db = Connection::connect();;
    $sql = "SELECT id1 FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $year, $usrtype);
    $stmt->execute();
    $result = $stmt->get_result();
    $id1 = [];
    while ($row = $result->fetch_assoc()) {
        $id1[] = $row['id1'];
    }
    $stmt->close();

    $avg_point = [];
    if ($avg_calc) {
        $db->query("TRUNCATE TABLE ans_total");
        $sql = "INSERT INTO ans_total(id, id1, uid, point)
                SELECT usr_ans.id, usr_ans.id1, usr_ans.uid, SUM(usr_ans.point) AS sum_point
                FROM item4, usr, usr_ans
                WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4 AND item4.qtype = '1' AND usr.comp = '1' AND usr.del = '1' AND usr.uid LIKE ? AND usr_ans.id = ?
                GROUP BY usr_ans.id, usr_ans.id1, usr_ans.uid";
        $stmt = $db->prepare($sql);
        $search_id = "$year%";
        $stmt->bind_param("ss", $search_id, $usrtype);
        $stmt->execute();
        $stmt->close();

        foreach ($id1 as $id) {
            $sql = "SELECT ROUND(AVG(point), 1) AS avg_point FROM ans_total WHERE id1 = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $avg_point[] = $row ? $row['avg_point'] : "";
            $stmt->close();
        }
    } else {
        foreach ($id1 as $id) {
            $sql = "SELECT ROUND(avg, 1) AS point FROM dat_avg WHERE id1 = ? AND id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $id, $usrtype);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $avg_point[] = $row ? $row['point'] : "";
            $stmt->close();
        }
    }
    $db->close();
    return $avg_point;
}

// 個別評価を取得する関数
function getIndividualEvaluation($uid) {
    $db = Connection::connect();;
    $year = substr($uid, 0, 2);
    $arr_uid = explode('-', $uid);
    $category = getTypeNo(substr($uid, 14));

    $years = [$year];
    if (is_numeric($arr_uid[4]) && $arr_uid[4] <= 50) {
        $years[] = sprintf("%02d", (int)$year - 1);
        $years[] = sprintf("%02d", (int)$year - 2);
    }

    $title_id_array = [];
    $sql = "SELECT id1 FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $year, $category);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $title_id_array[] = $row['id1'];
    }
    $stmt->close();

    $avg_point = [];
    foreach ($years as $year) {
        $search_id = sprintf("%02d%s", $year, substr($uid, 2));
        $db->query("TRUNCATE TABLE ans_total");
        $sql = "INSERT INTO ans_total(id, id1, uid, point)
                SELECT usr_ans.id, usr_ans.id1, usr_ans.uid, SUM(usr_ans.point) AS sum_point
                FROM usr, usr_ans
                LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4
                WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND usr.comp = '1' AND usr.del = '1' AND CASE WHEN item4.qtype IS NULL THEN '1' ELSE item4.qtype END = '1' AND usr.uid LIKE ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $search_id);
        $stmt->execute();
        $stmt->close();

        $year_avg = [];
        foreach ($title_id_array as $id1) {
            $sql = "SELECT ROUND(AVG(point), 1) AS avg_point FROM ans_total WHERE id1 = ? AND uid LIKE ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $id1, "$search_id%");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $year_avg[] = $row ? $row['avg_point'] : "";
            $stmt->close();
        }
        $avg_point[] = $year_avg;
    }

    $db->close();
    return $avg_point;
}

// 満点を取得する関数
function getMaxPoint($year, $type) {
    $db = Connection::connect();;
    $max_array = [];
    for ($i = 0; $i < 3; $i++) {
        $serch_year = sprintf("%02d", $year - $i);
        $sql = "SELECT point FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", $serch_year, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $points = [];
        while ($row = $result->fetch_assoc()) {
            $points[] = (int)$row['point'];
        }
        $stmt->close();
        if ($i == 0) {
            $max_array[] = $points;
        }
        $max_array[] = $points;
    }
    $db->close();
    return $max_array;
}

// カテゴリ文字列を取得する関数
function getCategoryStr($category) {
    $db = Connection::connect();;
    $sql = "SELECT category FROM category WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $category_str = $result->fetch_assoc()['category'];
    $stmt->close();
    $db->close();
    return $category_str;
}

// タイプ番号を取得する関数
function getTypeNo($branch_no) {
    if ($branch_no == 0) {
        return 1;
    } elseif ($branch_no >= 1 && $branch_no <= 50) {
        return 2;
    } else {
        return 3;
    }
}

// 病棟単位の評価を取得する関数
function getAllPastEvaluationHospital($hosp_id, $arg_year, $category) {
    $db = Connection::connect();;
    $years = [$arg_year, sprintf("%02d", $arg_year - 1), sprintf("%02d", $arg_year - 2)];

    $sql = "SELECT id1 FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $arg_year, $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $title_id_array = [];
    while ($row = $result->fetch_assoc()) {
        $title_id_array[] = $row['id1'];
    }
    $stmt->close();

    $avg_point = [];
    foreach ($years as $year) {
        $search_id = sprintf("%02d%s", $year, substr($hosp_id, 2));
        $db->query("TRUNCATE TABLE ans_total");

        $sql = "INSERT INTO ans_total(id, id1, uid, point)
                SELECT usr_ans.id, usr_ans.id1, usr_ans.uid, SUM(usr_ans.point) AS sum_point
                FROM usr, usr_ans
                LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4
                WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND usr.comp = '1' AND usr.del = '1' AND CASE WHEN item4.qtype IS NULL THEN '1' ELSE item4.qtype END = '1' AND usr.uid LIKE ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $search_id);
        $stmt->execute();
        $stmt->close();

        $year_avg = [];
        foreach ($title_id_array as $id1) {
            $sql = "SELECT ROUND(AVG(point), 1) AS avg_point FROM ans_total WHERE id1 = ? AND uid LIKE ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $id1, "$search_id%");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $year_avg[] = $row ? $row['avg_point'] : "";
            $stmt->close();
        }
        $avg_point[] = $year_avg;
    }
    $db->close();
    return $avg_point;
}

// アンケートの全国平均を取得する関数
function getEncAverage($year) {
    $db = Connection::connect();;
    $average_array = [];
    for ($i = 1; $i <= 5; $i++) {
        $sql = "SELECT COUNT(u.uid) AS cnt FROM enq_usr_ans e, usr u WHERE e.uid = u.uid AND u.uid LIKE ? AND u.comp = '1' AND u.del = '1' AND e.id = '10' AND e.id1 = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ss", "$year%", $i);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cnt_enq = $row['cnt'];
        $stmt->close();
        if ($cnt_enq) {
            $sql = "SELECT SUM(e.ans) AS sum_ans FROM enq_usr_ans e, usr u WHERE e.uid = u.uid AND u.uid LIKE ? AND u.comp = '1' AND u.del = '1' AND e.id = '10' AND e.id1 = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", "$year%", $i);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $sum_ans = $row['sum_ans'];
            $average_array[] = $sum_ans ? round($sum_ans / $cnt_enq, 2) : 0;
            $stmt->close();
        } else {
            $average_array[] = "";
        }
    }
    $db->close();
    return $average_array;
}

// 病院ごとのアンケート結果を取得する関数
function getEncResult($year, $ID) {
    $db = Connection::connect();;
    $enq_arr = [];
    for ($i = 0; $i < 3; $i++) {
        $iyear = $year - $i;
        $search_id = sprintf("%02d%s", $iyear, substr($ID, 2));
        $tmp_arr = [];
        for ($j = 1; $j <= 5; $j++) {
            $sql = "SELECT COUNT(u.uid) AS cnt FROM enq_usr_ans e, usr u WHERE e.uid = u.uid AND u.uid LIKE ? AND u.comp = '1' AND u.del = '1' AND e.id = '10' AND e.id1 = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", "$search_id%", $j);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $cnt_enq = $row['cnt'];
            $stmt->close();
            if ($cnt_enq) {
                $sql = "SELECT SUM(e.ans) AS sum_ans FROM enq_usr_ans e, usr u WHERE e.uid = u.uid AND u.uid LIKE ? AND u.comp = '1' AND u.del = '1' AND e.id = '10' AND e.id1 = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ss", "$search_id%", $j);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $sum_ans = $row['sum_ans'];
                $tmp_arr[] = $sum_ans ? round($sum_ans / $cnt_enq, 2) : 0;
                $stmt->close();
            } else {
                $tmp_arr[] = "";
            }
        }
        $enq_arr[] = $tmp_arr;
    }
    $db->close();
    return $enq_arr;
}

// 病棟単位の評価を取得する関数
function getEvaluationWard($uid) {
    $db = Connection::connect();;
    $category = getTypeNo(substr($uid, 14));
    $years = [substr($uid, 0, 2)];
    for ($i = 1; $i <= 2; $i++) {
        $years[] = sprintf("%02d", $years[0] - $i);
    }
    $sql = "SELECT id1 FROM history WHERE year = ? AND id = ? ORDER BY id1 ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $years[0], $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $title_id_array = [];
    while ($row = $result->fetch_assoc()) {
        $title_id_array[] = $row['id1'];
    }
    $stmt->close();

    $avg_point = [];
    foreach ($years as $year) {
        $search_id = sprintf("%02d%s", $year, substr($uid, 2));
        $db->query("TRUNCATE TABLE ans_total");

        $sql = "INSERT INTO ans_total(id, id1, uid, point)
                SELECT usr_ans.id, usr_ans.id1, usr_ans.uid, SUM(usr_ans.point) AS sum_point
                FROM usr, usr_ans
                LEFT JOIN item4 ON item4.id = usr_ans.id AND item4.id1 = usr_ans.id1 AND item4.id2 = usr_ans.id2 AND item4.id3 = usr_ans.id3 AND item4.id4 = usr_ans.id4
                WHERE usr.uid = usr_ans.uid AND usr.id = usr_ans.id AND usr.comp = '1' AND usr.del = '1' AND CASE WHEN item4.qtype IS NULL THEN '1' ELSE item4.qtype END = '1' AND usr.uid LIKE ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $search_id);
        $stmt->execute();
        $stmt->close();

        $year_avg = [];
        foreach ($title_id_array as $id1) {
            $sql = "SELECT ROUND(AVG(point), 1) AS avg_point FROM ans_total WHERE id1 = ? AND uid LIKE ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $id1, "$search_id%");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $year_avg[] = $row ? $row['avg_point'] : "";
            $stmt->close();
        }
        $avg_point[] = $year_avg;
    }
    $db->close();
    return $avg_point;
}
?>
