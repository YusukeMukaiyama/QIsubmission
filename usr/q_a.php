<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../admin/setup.php';

/*******************************************************************
q_a.php
	質問・回答
									(C)2005,University of Hyougo.
*******************************************************************/
$db = Connection::connect();

function make_qa($db = null) {
	$resume = false;

	if (!isset($_REQUEST['uid'])) {
		header("Location: ../index.html");
		exit();
	}

	if (isset($_REQUEST['logout'])) {
		header("Location: close.php?uid=" . ($_REQUEST['uid'] ?? ''));
		exit();
	}

	$uid = $_REQUEST['uid'];
	$id1 = $_REQUEST['id1'] ?? '';
	$id2 = $_REQUEST['id2'] ?? '';
	$id3 = $_REQUEST['id3'] ?? '';
	$id4 = $_REQUEST['id4'] ?? '';
	$no = $_REQUEST['no'] ?? '';

	require_once __DIR__ . '/../admin/setup.php';

	$db = Connection::connect();

	$uid = $_REQUEST['uid'];
	$id = UserClassification::GetUserType($uid);

	if ($id == Config::STRUCTURE) {
		echo "<!--STRUCTURE-->";
	} elseif ($id == Config::PROCESS) {
		echo "<!--PROCESS-->";
	} else {
		echo "<!--OUTCOME-->";
	}

	if (isset($_POST['next']) || isset($_POST['back'])) {
		if ($id == Config::OUTCOME) {
			$_REQUEST['ans'.$id4] = strip_tags($_REQUEST['ans'.$id4]);

			$ans_sql = "SELECT point FROM ans WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4 AND ans_id='".(int)$_REQUEST['ans'.$id4]."'";
			$ans_res = mysqli_query($db, $ans_sql);
			$point = (mysqli_num_rows($ans_res)) ? mysqli_fetch_object($ans_res)->point : 0;

			$exesql = "DELETE FROM usr_ans WHERE id=$id AND uid='$uid' AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4";
			mysqli_query($db, $exesql);

			$exesql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES($id,'$uid',$id1,$id2,$id3,$id4,'".$_REQUEST['ans'.$id4]."',".(int)$point.")";
			mysqli_query($db, $exesql);

			if (isset($_POST['back'])) header("Location: list.php?uid=$uid");

		} else {
			$sql = "SELECT id4, qtype, question, no FROM item4 WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 ORDER BY no ASC";
			$res = mysqli_query($db, $sql);

			while ($fld = mysqli_fetch_object($res)) {
				$_REQUEST['ans'.$fld->id4] = strip_tags($_REQUEST['ans'.$fld->id4]);

				$ans_sql = "SELECT point FROM ans WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=".$fld->id4." AND ans_id='".(int)$_REQUEST['ans'.$fld->id4]."'";
				$ans_res = mysqli_query($db, $ans_sql);
				$point = (mysqli_num_rows($ans_res)) ? mysqli_fetch_object($ans_res)->point : 0;

				$exesql = "DELETE FROM usr_ans WHERE id=$id AND uid='$uid' AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=".$fld->id4;
				mysqli_query($db, $exesql);

				$exesql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES($id,'$uid',$id1,$id2,$id3,".$fld->id4.",'".$_REQUEST['ans'.$fld->id4]."',".(int)$point.")";
				mysqli_query($db, $exesql);

				if (isset($_POST['back'])) header("Location: list.php?uid=$uid");
			}
		}

		if ($id == Config::OUTCOME) {
			$sql = "SELECT item1.id1, item2.id2, item3.id3, item4.id4, q_order.order_no,
					(item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no, (item4.no) AS item4_no,
					(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
					FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
					INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id))
					INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id))
					LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND (item4.id2 = q_order.id2)
					AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id)
					WHERE item1.id=$id AND q_order.order_no > $no ORDER BY q_order.order_no ASC LIMIT 1";

			$res = mysqli_query($db, $sql);
			if (!mysqli_num_rows($res)) {
				header("Location: list.php?uid=$uid");
			}
		} else {
			$sql = "SELECT item1.id AS id, item1.id1 AS id1, item2.id2 AS id2, item3.id3 AS id3,
					item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no, item4.no AS item4_no
					FROM item1
					LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
					LEFT JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2
					LEFT JOIN item4 ON item3.id = item4.id AND item3.id1 = item4.id1 AND item3.id2 = item4.id2 AND item3.id3 = item4.id3
					WHERE item1.id = $id AND item1.id1 = $id1 AND item2.id2 = $id2 AND item3.id3 = $id3 AND item4.id4 = $id4
					ORDER BY item1.no, item2.no, item3.no, item4.no";
			$res = mysqli_query($db, $sql);
			$fld = mysqli_fetch_object($res);
			$no1 = $fld->item1_no;
			$no2 = $fld->item2_no;
			$no3 = $fld->item3_no;
			$no4 = $fld->item4_no ?? 0;

			$sql = "SELECT item1.id1, item2.id2, item3.id3, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
					(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
					FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
					INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)
					WHERE item1.id = $id AND item1.id1 = $id1 AND item2.id2 = $id2 AND item3.no > $no3
					ORDER BY item1.no ASC, item2.no ASC, item3.no ASC LIMIT 1";

			$res = mysqli_query($db, $sql);
			if (!mysqli_num_rows($res)) {
				$sql = "SELECT item1.id1, item2.id2, item3.id3, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
						(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
						FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
						INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)
						WHERE item1.id = $id AND item1.id1 = $id1 AND item2.no > $no2
						ORDER BY item1.no ASC, item2.no ASC, item3.no ASC LIMIT 1";

				$res = mysqli_query($db, $sql);
				if (!mysqli_num_rows($res)) {
					$sql = "SELECT item1.id1, item2.id2, item3.id3, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
							(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
							FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
							INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)
							WHERE item1.id = $id AND item1.no > $no1
							ORDER BY item1.no ASC, item2.no ASC, item3.no ASC LIMIT 1";

					$res = mysqli_query($db, $sql);
					if (!mysqli_num_rows($res)) {
						header("Location: enq.php?uid=$uid");
					}
				}
			}
		}

	} elseif (isset($_REQUEST['edit'])) {
		if ($id == Config::OUTCOME) {
			$sql = "SELECT item1.id1, item3.id2, item4.id3, item4.id4, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no, (item4.no) AS item4_no,
					(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name, q_order.order_no
					FROM ((((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) INNER JOIN item3
					ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) INNER JOIN item4
					ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id))
					INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2)
					AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN q_order ON (usr_ans.id1 = q_order.id1)
					AND (usr_ans.id2 = q_order.id2) AND (usr_ans.id3 = q_order.id3) AND (usr_ans.id4 = q_order.id4) AND (usr_ans.id = q_order.id)
					WHERE item1.id = $id AND item1.id1 = $id1 AND item2.id2 = $id2 AND item3.id3 = $id3 AND item4.id4 = $id4";
		} else {
			$sql = "SELECT item1.id1, item2.id2, item3.id3, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
					(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
					FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
					INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)
					WHERE item1.id = $id AND item1.id1 = $id1 AND item2.id2 = $id2 AND item3.id3 = $id3";
		}
	} else {
		if ($id == Config::OUTCOME) {
			$sql = "SELECT item1.id1, item3.id2, item4.id3, item4.id4, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no, (item4.no) AS item4_no,
					(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name, q_order.order_no
					FROM ((((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id)) INNER JOIN item3
					ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)) INNER JOIN item4
					ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id))
					INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2)
					AND (item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)) LEFT JOIN q_order ON (usr_ans.id1 = q_order.id1)
					AND (usr_ans.id2 = q_order.id2) AND (usr_ans.id3 = q_order.id3) AND (usr_ans.id4 = q_order.id4) AND (usr_ans.id = q_order.id)
					WHERE uid='$uid' ORDER BY q_order.order_no DESC LIMIT 1";
		} else {
			$sql = "SELECT item1.id1, item3.id2, item4.id3, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
					(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
					FROM (((item1 INNER JOIN item2 ON (item1.id = item2.id) AND (item1.id1 = item2.id1))
					INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id))
					INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND (item3.id = item4.id))
					INNER JOIN usr_ans ON (item4.id4 = usr_ans.id4) AND (item4.id3 = usr_ans.id3) AND (item4.id2 = usr_ans.id2) AND
					(item4.id1 = usr_ans.id1) AND (item4.id = usr_ans.id)
					WHERE uid='$uid' ORDER BY item1.no DESC, item2.no DESC, item3.no DESC, item4.no DESC LIMIT 1";
		}

		$res = mysqli_query($db, $sql);
		if (mysqli_num_rows($res)) {
			$resume = TRUE;
		}

		if (!$resume) {
			if ($id == Config::OUTCOME) {
				$sql = "SELECT item1.id1, item2.id2, item3.id3, item4.id4, (item1.no) AS item1_no, q_order.order_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
						(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
						FROM (((item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
						INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id))
						INNER JOIN item4 ON (item3.id3 = item4.id3) AND (item3.id2 = item4.id2) AND (item3.id1 = item4.id1) AND
						(item3.id = item4.id)) LEFT JOIN q_order ON (item4.id4 = q_order.id4) AND (item4.id3 = q_order.id3) AND
						(item4.id2 = q_order.id2) AND (item4.id1 = q_order.id1) AND (item4.id = q_order.id)
						WHERE item1.id = $id ORDER BY q_order.order_no ASC LIMIT 1";
			} else {
				$sql = "SELECT item1.id1, item2.id2, item3.id3, (item1.no) AS item1_no, (item2.no) AS item2_no, (item3.no) AS item3_no,
						(item1.name) AS item1_name, (item2.name) AS item2_name, (item3.name) AS item3_name
						FROM (item1 INNER JOIN item2 ON (item1.id1 = item2.id1) AND (item1.id = item2.id))
						INNER JOIN item3 ON (item2.id2 = item3.id2) AND (item2.id1 = item3.id1) AND (item2.id = item3.id)
						WHERE item1.id = $id ORDER BY item1.no ASC, item2.no ASC, item3.no ASC LIMIT 1";
			}
		}
	}

	$res = mysqli_query($db, $sql);
	$fld = mysqli_fetch_object($res);
	$id1 = $fld->id1;
	$id2 = $fld->id2;
	$id3 = $fld->id3;
	$item1_no = $fld->item1_no;
	$item2_no = $fld->item2_no;
	$item3_no = $fld->item3_no;
	$item1_name = $fld->item1_name;
	$item2_name = $fld->item2_name;
	$item3_name = $fld->item3_name;

	if ($id == Config::OUTCOME) {
		$id4 = $fld->id4;
		$no = $fld->order_no;

		$sql = "SELECT COUNT(id4) AS q_ttl FROM item4 WHERE id=$id";
		$res = mysqli_query($db, $sql);
		$fld = mysqli_fetch_object($res);
		$TTL = $fld->q_ttl;

		$sql = "SELECT q_order.id1, q_order.id2, q_order.id3, q_order.id4 FROM item4, q_order
				WHERE (item4.id=q_order.id AND item4.id1=q_order.id1 AND item4.id2=q_order.id2 AND item4.id3=q_order.id3 AND item4.id4=q_order.id4) AND
				item4.id=$id ORDER BY q_order.order_no";
		$res = mysqli_query($db, $sql);
		$NOW = 0;
		while ($fld = mysqli_fetch_object($res)) {
			$NOW++;
			if ($fld->id1==$id1 && $fld->id2==$id2 && $fld->id3==$id3 && $fld->id4==$id4) {
				break;
			}
		}
	} else {
		$sql = "SELECT item1.id AS id, item1.id1 AS id1, item2.id2 AS id2, item3.id3 AS id3,
				item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no
				FROM item1
				LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
				LEFT JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2
				WHERE item1.id = $id AND item1.id1 = $id1 AND item2.id2 = $id2 AND item3.id3 = $id3
				ORDER BY item1.no, item2.no, item3.no";
		$res = mysqli_query($db, $sql);
		$fld = mysqli_fetch_object($res);
		$no1 = $fld->item1_no;
		$no2 = $fld->item2_no;
		$no3 = $fld->item3_no;
		$no4 = $fld->item4_no ?? 0;

		$sql = "SELECT COUNT(id3) AS q_ttl FROM item3 WHERE id=$id";
		$res = mysqli_query($db, $sql);
		$fld = mysqli_fetch_object($res);
		$TTL = $fld->q_ttl;

		$sql = "SELECT item1.id AS id, item1.id1 AS id1, item2.id2 AS id2, item3.id3 AS id3,
				item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no
				FROM item1
				LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
				LEFT JOIN item3 ON item2.id = item3.id AND item2.id1 = item3.id1 AND item2.id2 = item3.id2
				WHERE item1.id = $id
				ORDER BY item1.no, item2.no, item3.no";
		$res = mysqli_query($db, $sql);
		$NOW = 0;
		while ($fld = mysqli_fetch_object($res)) {
			$NOW++;
			if ($fld->item1_no==$no1 && $fld->item2_no==$no2 && $fld->item3_no==$no3) {
				break;
			}
		}
	}

	$buf = "現在".$NOW."問目／".$TTL."問中です";

	$contents = "";
	$fileName = ImageUtilities::getFileName($id, $id1, $id2, $id3);
	$contents .= $fileName ? "<table style='background:url(\"$fileName\") no-repeat;background-position: right bottom;width:100%;height:420px;padding:5px;' class='tbl03'><tr><td valign='top'>\n" : "<table style='width:100%;height:420px;padding:5px;'><tr><td valign='top'>\n";

	$contents .= ($id != Config::OUTCOME) ? "<table width='100%'><tr><td class='normal'><div align='right'><a href='".$_SERVER['PHP_SELF']."?logout=1&uid=$uid'>ログアウト</a></div></td></tr>\n" : "<table width='100%'>\n";
	$contents .= "<tr><td class='normal'><div align='right'>$buf</div></td></tr>\n";
	$contents .= "<tr><td class='large'>◆質問 $item1_no.$item2_no.$item3_no".(($id == Config::OUTCOME) ? ".$id4" : "")."</td></tr>\n";
	$contents .= "</table>";

	if ($id == Config::OUTCOME) {
		$sql = "SELECT item4.id4, item4.qtype, item4.question
				FROM item4 WHERE item4.id=$id AND item4.id1=$id1 AND item4.id2=$id2 AND item4.id3=$id3 AND item4.id4=$id4";
	} else {
		$sql = "SELECT id4, qtype, question, no FROM item4 WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 ORDER BY no ASC";
	}

	$res = mysqli_query($db, $sql);
	while ($fld = mysqli_fetch_object($res)) {
		$id4 = $fld->id4;
		$usrans_sql = "SELECT ans FROM usr_ans WHERE id=$id AND uid='$uid' AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4";
		$usrans_res = mysqli_query($db, $usrans_sql);
		$usrans = (mysqli_num_rows($usrans_res)) ? mysqli_fetch_object($usrans_res)->ans : "";

		$contents .= ($id != Config::OUTCOME) ? "<table width='600'>\n" : "<table width='215'>\n";
		$contents .= "<tr><td class='large'>".nl2br($fld->question)."</td></tr>\n";
		$contents .= "</table>\n";

		$contents .= ($id != Config::OUTCOME) ? "<table width='600'>\n" : "<table width='215'>\n";
		if ($fld->qtype == Config::SELECT) {
			$ans_sql = "SELECT ans_id, answer FROM ans WHERE id=$id AND id1=$id1 AND id2=$id2 AND id3=$id3 AND id4=$id4 ORDER BY no ASC";
			$ans_res = mysqli_query($db, $ans_sql);
			while ($ans_fld = mysqli_fetch_object($ans_res)) {
				$contents .= "<tr><td class='large'><input type='radio' name='ans$id4' value='".$ans_fld->ans_id."'".(($usrans == $ans_fld->ans_id) ? ' checked' : '')."> ".$ans_fld->answer."</td></tr>\n";
			}
			$contents .= "<tr><td class='large'><input type='radio' name='ans$id4' value='0'".((!$usrans) ? ' checked' : '')."> 回答しない<br><br></td></tr>\n";
		} else {
			$contents .= "<tr><td valign='top' class='large'>回答:</td><td><textarea rows='3' cols='40' name='ans$id4'>$usrans</textarea></td></tr>\n";
		}
		$contents .= "</table>\n";
	}


	$contents .= "<table>\n";
	$contents .= "<tr><td class='large'>\n";
	$contents .= "<input type='hidden' name='id' value='$id'>\n";
	$contents .= "<input type='hidden' name='id1' value='$id1'>\n";
	$contents .= "<input type='hidden' name='id2' value='$id2'>\n";
	$contents .= "<input type='hidden' name='id3' value='$id3'>\n";
	$contents .= "<input type='hidden' name='id4' value='$id4'>\n";
	$contents .= "<input type='hidden' name='no' value='$no'>\n";
	$contents .= "<input type='hidden' name='uid' value='$uid'>\n";
	$contents .= "<div class='spimg'><img src='$fileName'></div>";
	$contents .= "<input type='submit' name='next' value='≫次の質問へ'>\n";
	if (isset($_REQUEST['edit'])) $contents .= "　<input type='submit' name='back' value='≫編集して一覧へ戻る'>\n";
	$contents .= "<br>\n※回答しないを選択した場合は0点となります。";
	$contents .= "</td></tr>\n";
	$contents .= "</table>\n";
	$contents .= "</td></tr>\n";
	$contents .= "</table>\n";

	return $contents;
}

function make_form_start($db) {
	$contents = "<form method='POST' action='".$_SERVER['PHP_SELF']."'>\n";
	return $contents;
}

function make_form_end($db) {
	$contents = "</form>\n";
	return $contents;
}

$handle = fopen("template_qa.html", "r") or die("file open error\n");
$contents = "";
while (TRUE) {
	$data = fread($handle, 8192);
	if (strlen($data) == 0) break;
	$contents .= $data;
}

$contents = str_replace("<!-- QLIST -->", "<a href='./list.php?uid=".$_REQUEST['uid']."'>質問一覧へ</a>", $contents);
$contents = str_replace("<!-- FORMSTART -->", make_form_start($db), $contents);
$contents = str_replace("<!-- CONTENTS -->", make_qa($db), $contents);
$contents = str_replace("<!-- FORMEND -->", make_form_end($db), $contents);
echo $contents;
?>
