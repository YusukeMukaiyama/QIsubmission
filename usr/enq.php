<?php
require_once __DIR__ . '/../admin/setup.php';

// SQLインジェクション対策のためにプレースホルダーを使用
function prepareStatement($db, $sql, $params) {
	$stmt = $db->prepare($sql);
	if ($stmt) {
			$types = str_repeat('s', count($params)); // すべてのパラメータを文字列として扱う
			$stmt->bind_param($types, ...$params);
			$stmt->execute();
			$result = $stmt->get_result();			
			return $result;
	} else {
			die('Query failed: ' . $db->error);
	}
}

// アンケートの回答を記録する関数
function recordAnswer($db, $uid, $id, $id1, $answer) {
    $sqlDelete = "DELETE FROM enq_usr_ans WHERE id=? AND id1=? AND uid=?";
    prepareStatement($db, $sqlDelete, [$id, $id1, $uid]);

    $sqlInsert = "INSERT INTO enq_usr_ans(id,id1,uid,ans) VALUES(?, ?, ?, ?)";
    prepareStatement($db, $sqlInsert, [$id, $id1, $uid, $answer]);
}

// 次のアンケートを取得する関数
function getNextEnquete($db, $utype, &$id, &$id1) {
    $sql = "SELECT id,id1 FROM enquete WHERE id = ? AND id1 > ? ORDER BY id ASC,id1 ASC LIMIT 1";
    $result = prepareStatement($db, $sql, [$id, $id1]);

    if ($result->num_rows) {
        $row = $result->fetch_object();
        $id = $row->id;
        $id1 = $row->id1;
    } else {
        $sql = "SELECT id,id1 FROM enquete WHERE id LIKE ? AND id > ? ORDER BY id ASC,id1 ASC LIMIT 1";
        $result = prepareStatement($db, $sql, [$utype . '%', $id]);

        if ($result->num_rows) {
            $row = $result->fetch_object();
            $id = $row->id;
            $id1 = $row->id1;
        } else {
            header("Location: " . ($utype != Config::OUTCOME ? "confirm.php" : "q_a.php") . "?uid=" . $_REQUEST['uid']);
            exit();
        }
    }
}

// アンケートの表示を行う関数
function viewEnquete($db, $id, $id1) {
	$sql = "SELECT * FROM enquete WHERE id=? AND id1=?";
	$result = prepareStatement($db, $sql, [$id, $id1]);
	
	$enquete = $result->fetch_object();
	
	$sql = "SELECT ans FROM enq_usr_ans WHERE uid=? AND id=? AND id1=?";
	$result = prepareStatement($db, $sql, [$_REQUEST['uid'], $id, $id1]);
	$answer = $result->num_rows ? $result->fetch_object()->ans : "";
	
	// HTMLコメントを削除
	$enqueteHtml = preg_replace('/<!--\{ex_[0-9]+\}-->/', '', $enquete->enq);
	$enqueteHtml = str_replace(["<!--", "-->"], '', $enqueteHtml);

	// JavaScriptを抽出
	preg_match('|<script[^>]*>(.+)</script>|Usi', $enqueteHtml, $script);
	$retstr = isset($script[0]) ? $script[0] : '';
	$scriptFlag = !empty($script);

	// JavaScript以外の文字列を全て抽出
	$htmlParts = preg_split('|<script[^>]*>(.+)</script>|Usi', $enqueteHtml);
	foreach ($htmlParts as $part) {
			$retstr .= nl2br(trim($part));
	}

	$retstr = "<table><tr><td>{$retstr}</td></tr><tr><td>";
	if ($enquete->type == Config::TEXT) {
			$retstr .= $scriptFlag ? "<input type=Config::TEXT name='ans' value='{$answer}' readonly>\n" : "<input type=Config::TEXT name='ans' value='{$answer}'>　{$enquete->unit}";
	} elseif ($enquete->type == Config::TEXTAREA) {
			$retstr .= "<textarea name='ans' cols='50' rows='5'>" . nl2br($answer) . "</textarea>";
	} else {
			$retstr .= "<table>";
			$sql = "SELECT * FROM enq_ans WHERE id=? AND id1=? ORDER BY id2 ASC";
			$result = prepareStatement($db, $sql, [$id, $id1]);
			$answers = $enquete->type == Config::CHECK ? explode('|', $answer) : [];

			while ($row = $result->fetch_object()) {
					if ($enquete->type == Config::CHECK) {
							$checked = in_array($row->id2, $answers) ? " checked" : "";
							$retstr .= "<tr><td><input type='checkbox' name='ans{$row->id2}' value='{$row->id2}'{$checked}>" . nl2br($row->ans) . "</td></tr>";
					} else {
							$checked = $row->id2 == $answer ? " checked" : "";
							$retstr .= "<tr><td><input type='radio' name='ans' value='{$row->id2}'{$checked}>" . nl2br($row->ans) . "</td></tr>";
					}
			}
			$retstr .= "</table>";
	}
	$retstr .= "</td></tr></table>";

	return $retstr;
}

// 次のアンケートを処理する関数
function nextEnquete($db, $uid, $utype, &$id, &$id1) {
    $error = "";

    if (isset($_POST['next']) || isset($_POST['edit'])) {
        $sql = "SELECT type, unit FROM enquete WHERE id=? AND id1=?";
        $result = prepareStatement($db, $sql, [$_POST['id'], $_POST['id1']]);
        $enquete = $result->fetch_object();

        if ($enquete->type == Config::TEXT && $enquete->unit) {
            $_POST['ans'] = StringUtilities::str2decimal($_POST['ans']);
            if ($_POST['ans'] === "") $error = "入力された値が正しくありません。";
        }

        if (!$error) {
            if ($enquete->type == Config::CHECK) {
                $sql = "SELECT * FROM enq_ans WHERE id=? AND id1=? ORDER BY id2 ASC";
                $result = prepareStatement($db, $sql, [$_POST['id'], $_POST['id1']]);
                $answerBuf = "";
                while ($row = $result->fetch_object()) {
                    if (isset($_POST['ans' . $row->id2])) $answerBuf .= "|" . $row->id2;
                }
                $_POST['ans'] = substr($answerBuf, 1);
            }

            recordAnswer($db, $uid, $_POST['id'], $_POST['id1'], $_POST['ans']);

            if (isset($_POST['edit'])) {
                header("Location: ./list.php?uid=" . $_REQUEST['uid']);
                exit();
            }
        }

        $id = $_POST['id'];
        $id1 = $_POST['id1'];

        if (!$error) {
            getNextEnquete($db, $utype, $id, $id1);
        }
    } else {
        $sql = "SELECT id,id1 FROM enq_usr_ans WHERE uid=? ORDER BY id DESC,id1 DESC LIMIT 1";
        $result = prepareStatement($db, $sql, [$uid]);
        if ($result->num_rows) {
            $row = $result->fetch_object();
            $id = $row->id;
            $id1 = $row->id1;
        } else {
            $sql = "SELECT id,id1 FROM enquete WHERE id LIKE ? ORDER BY id ASC,id1 ASC LIMIT 1";
            $result = prepareStatement($db, $sql, [$utype . '%']);
            if ($result->num_rows) {
                $row = $result->fetch_object();
                $id = $row->id;
                $id1 = $row->id1;
            } else {
                header("Location: " . ($utype != Config::OUTCOME ? "confirm.php" : "q_a.php") . "?uid=" . $_REQUEST['uid']);
                exit();
            }
        }
    }

    $html = viewEnquete($db, $id, $id1);
    if ($error) $html .= "<font color='red'>" . $error . "</font>";

    return $html;
}

// 初期処理
$db = Connection::connect();
$uid = $_REQUEST['uid'];
$utype = isset($_REQUEST['utype']) ? $_REQUEST['utype'] : UserClassification::getUserType($uid);

$html = isset($_GET['edit']) ? viewEnquete($db, $_GET['id'], $_GET['id1']) : nextEnquete($db, $uid, $utype, $id, $id1);

?>

<!DOCTYPE HTML>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
  <title>看護ケアの質評価・改善システム</title>
  <link href='liststyle.css' rel='stylesheet' type='text/css'>
  <style type="text/css">
    img { display: block; }
    td { padding: 0; }
  </style>
</head>
<body<?php if (isset($_POST['text1'])) echo " onload=\"document.enquete.text1.value='" . $_POST['text1'] . "'\""; ?>>
    <div align='center'>
        <table border='0' cellpadding='0' cellspacing='0' background='../usr_img/sub_main_bg.gif' class='tbl01'>
            <tr><td><img src='../usr_img/sub_head.gif' width='760' height='30' border='0' alt=''></td></tr>
            <tr><td><img src='../usr_img/sub_title.jpg' width='760' height='40' border='0' alt=''></td></tr>
            <tr class='spnon'><td><img src='../usr_img/spacer.gif' width='760' height='10' border='0' alt=''></td></tr>
            <tr><td background='../usr_img/sub_band.jpg'>
                <table width='100%'  border='0' cellspacing='0' cellpadding='0'>
                    <tr><td width='1'><img src='../img/spacer.gif' width='10' height='20'></td><td class='large'><font color='#FF6600'>≫</font>アンケート</td></tr>
                </table>
            </td></tr>
            <tr><td valign='top' style='padding:5px;'><div align='left'><br>
                <table width='100%' height='400'>
                    <tr><td valign='top'>
                        <form name='enquete' method='POST' action='<?php echo $_SERVER['PHP_SELF']; ?>'>
                            <a href='./list.php?uid=<?php echo $_REQUEST['uid']; ?>'>質問一覧へ</a>

                            <?php echo $html; ?>

                            <input type='hidden' name='uid' value='<?php echo $uid; ?>'>
                            <input type='hidden' name='utype' value='<?php echo $utype; ?>'>
                            <input type='hidden' name='id' value='<?php echo $id; ?>'>
                            <input type='hidden' name='id1' value='<?php echo $id1; ?>'>
                            <input type='submit' name='next' value='　次　へ　'<?php echo ($scriptFlag && mb_ereg("半角数字", $enq)) ? "onclick=\"if (document.enquete.ans.value=='') { alert('計算ボタンをクリックしてください。'); return false; }\"" : ""; ?>>
                            <?php if (isset($_GET['edit'])): ?>
                                <input type='submit' name='edit' value='　編集して一覧へ　'<?php echo ($scriptFlag && mb_ereg("半角数字", $enq)) ? "onclick=\"if (document.enquete.ans.value=='') { alert('計算ボタンをクリックしてください。'); return false; }\"" : ""; ?>>
                            <?php endif; ?>
                        </form>
                    </td></tr>
                </table>
            </td></tr>
            <tr><td><img src='../usr_img/sub_copyright.jpg' width='760' height='20' border='0' alt=''></td></tr>
            <tr><td><img src='../usr_img/sub_foot.gif' width='760' height='25' border='0' alt=''></td></tr>
        </table>
    </div>
</body>
</html>
