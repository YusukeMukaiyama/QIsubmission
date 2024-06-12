<?php
/*******************************************************************
list.php
    アンケート一覧
                                    (C)2005,University of Hyougo.
*******************************************************************/

// 正規ログイン以外はログイン画面へリダイレクト
if (!isset($_REQUEST['uid']) || empty($_REQUEST['uid'])) {
    header("Location: index.php");
    exit();
}

require_once("./setup.php");

$db = Connection::connect(); // データベース接続
$uid = $_REQUEST['uid']; // ユーザID

$id = UserClassification::GetUserType($uid);

function enum_enquete($db, $id, $uid)
{
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $stmt = $db->prepare("SELECT id, id1, enq, type, unit, csv FROM enquete WHERE id LIKE ? ORDER BY id, id1");
        if (!$stmt) {
            die('Prepare failed: ' . $db->error);
        }

        $likeId = $id . '%';
        $stmt->bind_param("s", $likeId);
        if (!$stmt->execute()) {
            die('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            die('Get result failed: ' . $stmt->error);
        }

        while ($fld = $result->fetch_object()) {
            if (!$fld) {
                die('Fetch object failed: ' . $stmt->error);
            }
            $deleteStmt = $db->prepare("DELETE FROM enq_usr_ans WHERE id = ? AND id1 = ? AND uid = ?");
            if (!$deleteStmt) {
                die('Prepare failed: ' . $db->error);
            }
            $insertStmt = $db->prepare("INSERT INTO enq_usr_ans(id, id1, uid, ans) VALUES (?, ?, ?, ?)");
            if (!$insertStmt) {
                die('Prepare failed: ' . $db->error);
            }

            if (in_array($fld->type, [Config::TEXT, Config::TEXTAREA, Config::RADIO])) {
                $deleteStmt->bind_param("iis", $fld->id, $fld->id1, $uid);
                if (!$deleteStmt->execute()) {
                    die('Execute failed: ' . $deleteStmt->error);
                }

                $ans = $_POST['enq_' . $fld->id . '_' . $fld->id1] ?? ''; // 修正: NULL対策
                $insertStmt->bind_param("iiis", $fld->id, $fld->id1, $uid, $ans);
                if (!$insertStmt->execute()) {
                    die('Execute failed: ' . $insertStmt->error);
                }
            } elseif ($fld->type == Config::CHECK) {
                $ans_sql = "SELECT * FROM enq_ans WHERE id=? AND id1=? ORDER BY id2 ASC";
                $ans_stmt = $db->prepare($ans_sql);
                if (!$ans_stmt) {
                    die('Prepare failed: ' . $db->error);
                }
                $ans_stmt->bind_param("ii", $fld->id, $fld->id1);
                if (!$ans_stmt->execute()) {
                    die('Execute failed: ' . $ans_stmt->error);
                }

                $ans_result = $ans_stmt->get_result();
                if (!$ans_result) {
                    die('Get result failed: ' . $ans_stmt->error);
                }

                $ans_buf = [];
                while ($ans_fld = $ans_result->fetch_object()) {
                    if (!$ans_fld) {
                        die('Fetch object failed: ' . $ans_stmt->error);
                    }
                    if (isset($_POST['enq_' . $fld->id . '_' . $fld->id1 . '_' . $ans_fld->id2])) {
                        $ans_buf[] = $ans_fld->id2;
                    }
                }
                $ans_str = implode("|", $ans_buf);

                $deleteStmt->bind_param("iis", $fld->id, $fld->id1, $uid);
                if (!$deleteStmt->execute()) {
                    die('Execute failed: ' . $deleteStmt->error);
                }

                $insertStmt->bind_param("iiis", $fld->id, $fld->id1, $uid, $ans_str);
                if (!$insertStmt->execute()) {
                    die('Execute failed: ' . $insertStmt->error);
                }
            }
        }
    }

    $html = "<br>\n<table width='500'><tr><td>アンケート</td></tr></table><br>\n";
    $stmt = $db->prepare("SELECT id, id1, enq, type, unit, csv FROM enquete WHERE id LIKE ? ORDER BY id, id1");
    if (!$stmt) {
        die('Prepare failed: ' . $db->error);
    }
    $likeId = $id . '%';
    $stmt->bind_param("s", $likeId);
    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    if (!$result) {
        die('Get result failed: ' . $stmt->error);
    }

    while ($fld = $result->fetch_object()) {
        if (!$fld) {
            die('Fetch object failed: ' . $stmt->error);
        }
        $html .= "<table width='500'>\n";
        $html .= "<tr><td>" . nl2br(preg_replace('<\!--\{ex_([0-9]+)\}-->', '', $fld->enq)) . "</td></tr>\n";
        $html .= "<tr><td>";

        $ans_stmt = $db->prepare("SELECT ans FROM enq_usr_ans WHERE id = ? AND id1 = ? AND uid = ?");
        if (!$ans_stmt) {
            die('Prepare failed: ' . $db->error);
        }
        $ans_stmt->bind_param("iis", $fld->id, $fld->id1, $uid);
        if (!$ans_stmt->execute()) {
            die('Execute failed: ' . $ans_stmt->error);
        }
        $ans_result = $ans_stmt->get_result();
        if (!$ans_result) {
            die('Get result failed: ' . $ans_stmt->error);
        }
        $ans_fld = $ans_result->fetch_object();
        if (!$ans_fld) {
            die('Fetch object failed: ' . $ans_stmt->error);
        }

        switch ($fld->type) {
            case Config::TEXT:
                $html .= "<input type='text' name='enq_" . $fld->id . "_" . $fld->id1 . "' value='" . htmlspecialchars($ans_fld->ans, ENT_QUOTES, 'UTF-8') . "'>　" . htmlspecialchars($fld->unit, ENT_QUOTES, 'UTF-8');
                break;
            case Config::TEXTAREA:
                $html .= "<textarea name='enq_" . $fld->id . "_" . $fld->id1 . "' cols='65' rows='5'>" . htmlspecialchars($ans_fld->ans, ENT_QUOTES, 'UTF-8') . "</textarea>";
                break;
            case Config::CHECK:
                $q_stmt = $db->prepare("SELECT id2, ans FROM enq_ans WHERE id = ? AND id1 = ? ORDER BY id2 ASC");
                if (!$q_stmt) {
                    die('Prepare failed: ' . $db->error);
                }
                $q_stmt->bind_param("ii", $fld->id, $fld->id1);
                if (!$q_stmt->execute()) {
                    die('Execute failed: ' . $q_stmt->error);
                }
                $q_result = $q_stmt->get_result();
                if (!$q_result) {
                    die('Get result failed: ' . $q_stmt->error);
                }
                $ans_buf = explode("|", $ans_fld->ans);
                while ($q_fld = $q_result->fetch_object()) {
                    if (!$q_fld) {
                        die('Fetch object failed: ' . $q_stmt->error);
                    }
                    $html .= "<input type='checkbox' name='enq_" . $fld->id . "_" . $fld->id1 . "_" . $q_fld->id2 . "'" . (in_array($q_fld->id2, $ans_buf) ? " checked" : "") . ">" . nl2br(htmlspecialchars($q_fld->ans, ENT_QUOTES, 'UTF-8')) . "<br>\n";
                }
                break;
            case Config::RADIO:
                $q_stmt = $db->prepare("SELECT id2, ans FROM enq_ans WHERE id = ? AND id1 = ? ORDER BY id2 ASC");
                if (!$q_stmt) {
                    die('Prepare failed: ' . $db->error);
                }
                $q_stmt->bind_param("ii", $fld->id, $fld->id1);
                if (!$q_stmt->execute()) {
                    die('Execute failed: ' . $q_stmt->error);
                }
                $q_result = $q_stmt->get_result();
                if (!$q_result) {
                    die('Get result failed: ' . $q_stmt->error);
                }
                while ($q_fld = $q_result->fetch_object()) {
                    if (!$q_fld) {
                        die('Fetch object failed: ' . $q_stmt->error);
                    }
                    $html .= "<input type='radio' name='enq_" . $fld->id . "_" . $fld->id1 . "' value='" . $q_fld->id2 . "'" . ($q_fld->id2 == $ans_fld->ans ? " checked" : "") . ">" . nl2br(htmlspecialchars($q_fld->ans, ENT_QUOTES, 'UTF-8')) . "<br>\n";
                }
                break;
        }

        $html .= "</td></tr>\n";
        $html .= "</table><br>\n";
    }

    return $html;
}



?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta http-equiv='Content-Style-Type' content='text/css'>
<link type='text/css' rel='stylesheet' href='./admin.css'>
<title>回答済データ編集</title>
</head>
<body>

<input type='button' name='close' value='閉じる' onclick='window.close();'>
<br><br>

<?php
echo "<form method='POST' action='./list.php?uid=" . htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') . "'>\n";
echo "ユーザID : " . htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') . "\n";

$item1_no = 0;
$item2_no = 0;
$item3_no = 0;
$item4_no = 0;

if ($id == Config::OUTCOME) {
    echo enum_enquete($db, $id, $uid);

    $qsql = "SELECT item1.id1, item2.id2, item3.id3, item4.id4, q_order.order_no, ".
            "(item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no, (item4.no) AS item4_no, ".
            "(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name, item4.question ".
            "FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
            "INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
            "INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id)) ".
            "LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND (item4.id2 = q_order.id2) ".
            "AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id) ".
            "WHERE item1.id=? ORDER BY q_order.order_no ASC";
    $qstmt = $db->prepare($qsql);

    // クエリの準備に失敗した場合のエラーハンドリング
    if (!$qstmt) {
        die('Prepare failed: ' . $db->error);
    }

    $qstmt->bind_param("i", $id);

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        if (!$qstmt->execute()) {
            die('Query execution failed: ' . $qstmt->error);
        }
        $qresult = $qstmt->get_result();

        while ($qfld = $qresult->fetch_object()) {
            $item1_no = $qfld->item1_no;
            $item2_no = $qfld->item2_no;
            $item3_no = $qfld->item3_no;
            $item4_no = $qfld->item4_no;
            $id1 = $qfld->id1;
            $id2 = $qfld->id2;
            $id3 = $qfld->id3;
            $id4 = $qfld->id4;

            $_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $id4] = strip_tags($_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $id4]);

            $asql = "SELECT point FROM ans WHERE id=? AND id1=? AND id2=? AND id3=? AND id4=? AND ans_id=?";
            $astmt = $db->prepare($asql);
            $ans_id = (int)$_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $id4];
            $astmt->bind_param("iiiiii", $id, $id1, $id2, $id3, $id4, $ans_id);
            $astmt->execute();
            $aresult = $astmt->get_result();
            $afld = $aresult->fetch_object();

            $point = $aresult->num_rows ? $afld->point : 0;

            $deleteStmt = $db->prepare("DELETE FROM usr_ans WHERE id=? AND uid=? AND id1=? AND id2=? AND id3=? AND id4=?");
            $deleteStmt->bind_param("isiiii", $id, $uid, $id1, $id2, $id3, $id4);
            $deleteStmt->execute();

            $insertStmt = $db->prepare("INSERT INTO usr_ans(id, uid, id1, id2, id3, id4, ans, point) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("isiiisii", $id, $uid, $id1, $id2, $id3, $id4, $_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $id4], $point);
            $insertStmt->execute();
        }
    }

    if (!$qstmt->execute()) {
        die('Query execution failed: ' . $qstmt->error);
    }
    $qresult = $qstmt->get_result();
    while ($qfld = $qresult->fetch_object()) {
        $item1_no = $qfld->item1_no;
        $item2_no = $qfld->item2_no;
        $item3_no = $qfld->item3_no;
        $item4_no = $qfld->item4_no;
        $id1 = $qfld->id1;
        $id2 = $qfld->id2;
        $id3 = $qfld->id3;
        $id4 = $qfld->id4;

        echo "<br>\n";
        echo "<br>\n";
        echo "<table width='500'>\n";
        echo "<tr><td>" . htmlspecialchars($item1_no, ENT_QUOTES, 'UTF-8') . "." . htmlspecialchars($item2_no, ENT_QUOTES, 'UTF-8') . "." . htmlspecialchars($item3_no, ENT_QUOTES, 'UTF-8') . ". " . htmlspecialchars($qfld->item3_name, ENT_QUOTES, 'UTF-8') . "</td></tr>\n";
        echo "<tr><td>" . nl2br(htmlspecialchars($qfld->question, ENT_QUOTES, 'UTF-8')) . "</td></tr>\n";

        $vsql = "SELECT ans FROM usr_ans WHERE id=? AND uid=? AND id1=? AND id2=? AND id3=? AND id4=?";
        $vstmt = $db->prepare($vsql);
        $vstmt->bind_param("isiiii", $id, $uid, $id1, $id2, $id3, $id4);
        $vstmt->execute();
        $vresult = $vstmt->get_result();
        $vfld = $vresult->fetch_object();

        echo "<tr><td>";
        if ($qfld->qtype == Config::TEXT) {
            echo "<textarea name='Q_" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id2, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id3, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id4, ENT_QUOTES, 'UTF-8') . "' cols='65' rows='5'>" . htmlspecialchars($vfld->ans, ENT_QUOTES, 'UTF-8') . "</textarea>";
        } else {
            $asql = "SELECT ans_id, answer FROM ans WHERE id=? AND id1=? AND id2=? AND id3=? AND id4=? ORDER BY ans_id";
            $astmt = $db->prepare($asql);
            $astmt->bind_param("iiiii", $id, $id1, $id2, $id3, $qfld->id4);
            $astmt->execute();
            $aresult = $astmt->get_result();
            while ($afld = $aresult->fetch_object()) {
                echo "<input type='radio' name='Q_" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id2, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id3, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($qfld->id4, ENT_QUOTES, 'UTF-8') . "' value='" . htmlspecialchars($afld->ans_id, ENT_QUOTES, 'UTF-8') . "'" . ($vfld->ans == $afld->ans_id ? " checked" : "") . ">" . htmlspecialchars($afld->answer, ENT_QUOTES, 'UTF-8') . "<br>\n";
            }
            echo "<input type='radio' name='Q_" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id2, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id3, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($qfld->id4, ENT_QUOTES, 'UTF-8') . "' value='0'" . ($vfld->ans == 0 ? " checked" : "") . ">回答しない<br>\n";
        }
        echo "</td></tr>\n";
        echo "</table>\n";
    }
} else { // 構造・過程
    $sql = "SELECT (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no, (item1.id) AS id, (item1.id1) AS id1, (item2.id2) AS id2, (item3.id3) AS id3, ".
           "(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name ".
           "FROM ((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) ".
           "INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) ".
           "WHERE item1.id=? ORDER BY item1.no ASC, item2.no ASC, item3.no ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $exesql = "UPDATE usr SET cooperation=? WHERE id=? AND uid=?";
        $exestmt = $db->prepare($exesql);
        $exestmt->bind_param("sis", $_POST['cooperation'], $id, $uid);
        $exestmt->execute();

        $stmt->execute();
        $result = $stmt->get_result();

        while ($fld = $result->fetch_object()) {
            if ($item1_no != $fld->item1_no) {
                $item1_no = $fld->item1_no;
                $item2_no = "";
                $item3_no = "";
                $item4_no = "";
                $id1 = $fld->id1;
                $id2 = $fld->id2;
                $id3 = $fld->id3;
            }
            if ($item2_no != $fld->item2_no) {
                $item2_no = $fld->item2_no;
                $item3_no = "";
                $item4_no = "";
            }
            if ($item3_no != $fld->item3_no) {
                $item3_no = $fld->item3_no;
                $item4_no = "";
            }

            $qsql = "SELECT id4, qtype, question, no FROM item4 WHERE id=? AND id1=? AND id2=? AND id3=? ORDER BY no ASC";
            $qstmt = $db->prepare($qsql);
            $qstmt->bind_param("iiii", $id, $id1, $id2, $id3);
            $qstmt->execute();
            $qresult = $qstmt->get_result();

            while ($qfld = $qresult->fetch_object()) {
                $_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $qfld->id4] = strip_tags($_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $qfld->id4]);

                $asql = "SELECT point FROM ans WHERE id=? AND id1=? AND id2=? AND id3=? AND id4=? AND ans_id=?";
                $astmt = $db->prepare($asql);
                $ans_id = (int)$_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $qfld->id4];
                $astmt->bind_param("iiiiii", $id, $id1, $id2, $id3, $qfld->id4, $ans_id);
                $astmt->execute();
                $aresult = $astmt->get_result();
                $afld = $aresult->fetch_object();

                $point = $aresult->num_rows ? $afld->point : 0;

                $deleteStmt = $db->prepare("DELETE FROM usr_ans WHERE id=? AND uid=? AND id1=? AND id2=? AND id3=? AND id4=?");
                $deleteStmt->bind_param("isiiii", $id, $uid, $id1, $id2, $id3, $qfld->id4);
                $deleteStmt->execute();

                $insertStmt = $db->prepare("INSERT INTO usr_ans(id, uid, id1, id2, id3, id4, ans, point) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("isiiisii", $id, $uid, $id1, $id2, $id3, $qfld->id4, $_POST["Q_" . $id . "_" . $id1 . "_" . $id2 . "_" . $id3 . "_" . $qfld->id4], $point);
                $insertStmt->execute();
            }
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($fld = $result->fetch_object()) {
        if ($item1_no != $fld->item1_no) {
            $item1_no = $fld->item1_no;
            $item2_no = "";
            $item3_no = "";
            $item4_no = "";
            $id1 = $fld->id1;
            $id2 = $fld->id2;
            $id3 = $fld->id3;
        }
        if ($item2_no != $fld->item2_no) {
            $item2_no = $fld->item2_no;
            $item3_no = "";
            $item4_no = "";
        }
        if ($item3_no != $fld->item3_no) {
            $item3_no = $fld->item3_no;
            $item4_no = "";
            echo "<br>\n";
            echo "<br>\n";
            echo "<table width='500'>\n";
            echo "<tr><td>" . htmlspecialchars($item1_no, ENT_QUOTES, 'UTF-8') . "." . htmlspecialchars($item2_no, ENT_QUOTES, 'UTF-8') . "." . htmlspecialchars($item3_no, ENT_QUOTES, 'UTF-8') . ". " . htmlspecialchars($fld->item3_name, ENT_QUOTES, 'UTF-8') . "</td></tr>\n";
        }

        $qsql = "SELECT id4, qtype, question, no FROM item4 WHERE id=? AND id1=? AND id2=? AND id3=?";
        $qstmt = $db->prepare($qsql);
        $qstmt->bind_param("iiii", $id, $id1, $id2, $id3);
        $qstmt->execute();
        $qresult = $qstmt->get_result();

        while ($qfld = $qresult->fetch_object()) {
            echo "<tr><td>" . nl2br(htmlspecialchars($qfld->question, ENT_QUOTES, 'UTF-8')) . "</td></tr>\n";

            $vsql = "SELECT ans FROM usr_ans WHERE id=? AND uid=? AND id1=? AND id2=? AND id3=? AND id4=?";
            $vstmt = $db->prepare($vsql);
            $vstmt->bind_param("isiiii", $id, $uid, $id1, $id2, $id3, $qfld->id4);
            $vstmt->execute();
            $vresult = $vstmt->get_result();
            $vfld = $vresult->fetch_object();

            echo "<tr><td>";
            if ($qfld->qtype == Config::TEXT) {
                echo "<textarea name='Q_" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id2, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id3, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($qfld->id4, ENT_QUOTES, 'UTF-8') . "' cols='65' rows='5'>" . htmlspecialchars($vfld->ans, ENT_QUOTES, 'UTF-8') . "</textarea>";
            } else {
                $asql = "SELECT ans_id, answer FROM ans WHERE id=? AND id1=? AND id2=? AND id3=? AND id4=? ORDER BY ans_id";
                $astmt = $db->prepare($asql);
                $astmt->bind_param("iiiii", $id, $id1, $id2, $id3, $qfld->id4);
                $astmt->execute();
                $aresult = $astmt->get_result();
                while ($afld = $aresult->fetch_object()) {
                    echo "<input type='radio' name='Q_" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id2, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id3, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($qfld->id4, ENT_QUOTES, 'UTF-8') . "' value='" . htmlspecialchars($afld->ans_id, ENT_QUOTES, 'UTF-8') . "'" . ($vfld->ans == $afld->ans_id ? " checked" : "") . ">" . htmlspecialchars($afld->answer, ENT_QUOTES, 'UTF-8') . "<br>\n";
                }
                echo "<input type='radio' name='Q_" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id2, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($id3, ENT_QUOTES, 'UTF-8') . "_" . htmlspecialchars($qfld->id4, ENT_QUOTES, 'UTF-8') . "' value='0'" . ($vfld->ans == 0 ? " checked" : "") . ">回答しない<br>\n";
            }
            echo "</td></tr>\n";
        }
        echo "</table>\n";
    }

    echo enum_enquete($db, $id, $uid);
}

echo "<br>\n";
echo "<input type='submit' name='edit' value='　変　更　'>";
?>

</form>
<br>
<input type='button' name='close' value='閉じる' onclick='window.close();'>

</body>
</html>
