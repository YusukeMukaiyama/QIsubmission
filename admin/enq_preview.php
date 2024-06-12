<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="admin.css" media="all">
<title>アンケートプレビュー</title>
<style>
    body {
        display: flex;
        justify-content: center;
        margin: 0;
        text-align: center;
    }
    .content {
        max-width: 100%;
        width: 100%;
    }
    .center-table {
        margin-left: auto;
        margin-right: auto;
    }
</style>
</head>
<body>

<div class="content">

    <h1>QIシステム</h1>

    <?php
    require_once("setup.php");

    $db = Connection::connect(); // データベース接続
    $id = $_REQUEST['id'];
    $id1 = $_REQUEST['id1'];

    $sql = "SELECT * FROM enquete WHERE id=? AND id1=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $id, $id1);
    $stmt->execute();
    $result = $stmt->get_result();
    $fld = $result->fetch_object();
    $enq = $fld->enq;

    if ($id == 10) {
        echo "<table class='center-table' cellspacing='1' cellpadding='5'>
                <tr><td>
                    在院患者総数（60日間の合計数）<input type='text' name='care_total_count' value=''>件
                    <br>
                    {$enq}（60日間の合計数）<input type='text' name='text2' value=''>件
                    <br>
                    <br><br><br><br><br><br>
                    <input type='submit' value='計算'>
                    <br><br>
                    1000床あたりの{$enq}　＊半角数字で入力して必ず<span style='color:#f00'>計算ボタンを押してください。</span>
                    <br>
                    <span style='font-size:9pt;color:#bbb;'>※参照中のアンケートには計算式が含まれています。<br>
                    ボタン等の押下時動作はこの画面では正常動作しません。</span>
                    <br>
                    <input type='hidden' name='id' value='{$id}'>
                    <input type='hidden' name='id1' value='{$id1}'>
                </td></tr>
              </table>";
    } else {
        echo "<form name='enquete'>\n";
        echo "<table class='center-table' cellspacing='1' cellpadding='5'>\n<tr><td>\n";
        echo nl2br($enq);
        echo "</td></tr>\n";
        echo "</table>\n";
        echo "</form>\n";
    }
    ?>

    <p><a href='#' onclick='window.close();'>[ 閉じる ]</a></p>

</div>

</body>
</html>
