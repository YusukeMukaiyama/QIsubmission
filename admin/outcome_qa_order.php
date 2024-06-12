<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Style-Type" content="text/css">
<link rel="stylesheet" href="./admin.css"> 
<title>アウトカム質問順序設定</title>
</head>
<body>

<div align='center'>

    <h1>QIシステム</h1>

    <table cellspacing='1' cellpadding='5'>
    <tr><th><a href='index.php'>メニュー</a> ≫ アウトカム質問順序設定</th></tr>
    <tr><td>
<?php

    require_once("setup.php");
    require_once("common.php");//共通関数を読み込み

    $db = Connection::connect(); // データベース接続

    // 公開 / 非公開取得
    $public = getPublicationStatus($db);
    
    if ($public == 1) {//開発中のため、強制的に表示させる(1はclose、2がopen)(正しくは2)
        echo sanitize("公開中のため設定変更できません。");
    } else {

        // 質問順序が存在するか確認
        $sql = "SELECT COUNT(order_no) FROM q_order";
        $res = mysqli_query($db, $sql);

        // 質問順序が無い場合(初回)またはリセットが要求された場合
        if ((!mysqli_num_rows($res)) || (isset($_GET['reset']) && $_GET['reset'] == 1)) {

            mysqli_query($db, "TRUNCATE TABLE q_order");

            $sql = "SELECT item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no, item1.id, item1.id1, item2.id2, item3.id3, item1.name AS item1_name, item2.name AS item2_name, item3.name AS item3_name
                    FROM ((item1
                    INNER JOIN item2 ON item1.id1 = item2.id1 AND item1.id = item2.id)
                    INNER JOIN item3 ON item2.id2 = item3.id2 AND item2.id1 = item3.id1 AND item2.id = item3.id)
                    WHERE item1.id = 3
                    ORDER BY item1.no, item2.no, item3.no";

            $res = mysqli_query($db, $sql);
            $order_no = 0;
            while ($fld = mysqli_fetch_object($res)) {
                $order_no++;
                $insert_sql = "INSERT INTO q_order (id, id1, id2, id3, order_no) VALUES ({$fld->id}, {$fld->id1}, {$fld->id2}, {$fld->id3}, $order_no)";
                mysqli_query($db, $insert_sql);
            }
        }

        // 表示順変更 (上に移動)
        if (isset($_GET['up']) && $_GET['up'] == 1) {
            $order_no = $_GET['ord'];
            adjust_order($db, $order_no, $order_no - 1);
        }

        // 表示順変更 (下に移動)
        if (isset($_GET['drop']) && $_GET['drop'] == 1) {
            $order_no = $_GET['ord'];
            adjust_order($db, $order_no, $order_no + 1);
        }

            // 表示順による質問のリストアップ
            $sql = "SELECT item4.id, item4.id1, item4.id2, item4.id3, item4.id4, item4.question, q_order.order_no
            FROM item4
            LEFT JOIN q_order ON item4.id4 = q_order.id4 AND item4.id3 = q_order.id3 AND item4.id2 = q_order.id2 AND item4.id1 = q_order.id1 AND item4.id = q_order.id
            WHERE item4.id=3
            ORDER BY q_order.order_no";
            $res = mysqli_query($db, $sql);
            $item_cnt = mysqli_num_rows($res);
            $current_order_no = 0;
            echo "<table cellspacing='1' cellpadding='5'>\n";
            echo "<tr><th>質問No.</th><th>質問</th><th>表示順</th></tr>";
            while ($fld = mysqli_fetch_object($res)) {
            $current_order_no++;
            // データベース内の order_no を現在のインデックスで更新する
            if ($fld->order_no != $current_order_no) {
                mysqli_query($db, "UPDATE q_order SET order_no=$current_order_no WHERE id=$fld->id AND id1=$fld->id1 AND id2=$fld->id2 AND id3=$fld->id3 AND id4=$fld->id4");
            }
            echo "<tr bgcolor='#ffffff'><td>".sanitize($fld->id.".".$fld->id1.".".$fld->id2.".".$fld->id3.".".$fld->id4)."</td><td>".nl2br(sanitize($fld->question))."</td><td align='center'>";
            if ($current_order_no > 1) {
                echo "<a href='".$_SERVER['PHP_SELF']."?ord=".$current_order_no."&up=1'>▲</a>";
            }
            if ($current_order_no < $item_cnt) {
                echo "<a href='".$_SERVER['PHP_SELF']."?ord=".$current_order_no."&drop=1'>▼</a>";
            }
            echo "</td></tr>\n";
            }
            echo "</table>\n";


        echo "<p><a href='".$_SERVER['PHP_SELF']."?reset=1'>表示順のリセット</a>　※リセットすると各項目のNo.順に戻ります。</p>";

    }

    // 順序調整関数
    function adjust_order($db, $current_order, $new_order) {
        $res = mysqli_query($db, "SELECT id, id1, id2, id3, id4 FROM q_order WHERE order_no = $current_order");
        $current = mysqli_fetch_object($res);
        $res = mysqli_query($db, "SELECT id, id1, id2, id3, id4 FROM q_order WHERE order_no = $new_order");
        $target = mysqli_fetch_object($res);
        mysqli_query($db, "UPDATE q_order SET order_no = $new_order WHERE id = $current->id AND id1 = $current->id1 AND id2 = $current->id2 AND id3 = $current->id3 AND id4 = $current->id4");
        mysqli_query($db, "UPDATE q_order SET order_no = $current_order WHERE id = $target->id AND id1 = $target->id1 AND id2 = $target->id2 AND id3 = $target->id3 AND id4 = $target->id4");
    }

?>
    </td></tr>
    </table>

</div>

</body>
</html>


