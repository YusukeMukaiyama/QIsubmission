<?php
// 必要なPHPファイルをインクルード
require_once("setup.php");

// データベースに接続
$db = Connection::connect();

// 初期値設定
$publicStatus = null;
$errorMessage = "";

// 公開 / 非公開設定の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pub'])) {
    $pubStatus = (int)$_POST['pub'];
    $stmt = $db->prepare("UPDATE public SET pub = ?");
    $stmt->bind_param("i", $pubStatus);
    $stmt->execute();

    if ($pubStatus === 1) {
        $result = $db->query("SELECT LEFT(uid, 2) AS year FROM usr GROUP BY LEFT(uid, 2) ORDER BY LEFT(uid, 2) DESC LIMIT 1");
        $year = $result->fetch_object()->year;

        $stmt = $db->prepare("DELETE FROM history WHERE year = ?");
        $stmt->bind_param("s", $year);
        $stmt->execute();

        $stmt = $db->prepare("INSERT INTO history (id, year, id1, no, name, point) SELECT id, ?, id1, no, name, point FROM item1");
        $stmt->bind_param("s", $year);
        $stmt->execute();
    }
}

// 公開 / 非公開の取得
$result = $db->query("SELECT pub FROM public");
if ($result && $result->num_rows > 0) {
    $publicStatus = $result->fetch_object()->pub;
} else {
    die("データの取得に失敗しました");
}

// エラーテキストを取得する関数
function getErrorText($id, $id1 = "", $id2 = "", $id3 = "")
{
    global $db;
    $categoryMap = [1 => "構造", 2 => "過程", 3 => "アウトカム"];
    $category = $categoryMap[$id] ?? "";

    $errText = "※カテゴリ-{$category}";

    $query = "";
    $params = [];
    $types = "";

    if ($id3) {
        $query = "SELECT item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no FROM item1
                  INNER JOIN item2 ON item1.id1 = item2.id1 AND item1.id = item2.id
                  INNER JOIN item3 ON item2.id2 = item3.id2 AND item2.id1 = item3.id1 AND item2.id = item3.id
                  WHERE item1.id = ? AND item1.id1 = ? AND item2.id2 = ? AND item3.id3 = ?";
        $params = [$id, $id1, $id2, $id3];
        $types = "iiii";
    } elseif ($id2) {
        $query = "SELECT item1.no AS item1_no, item2.no AS item2_no FROM item1
                  INNER JOIN item2 ON item1.id1 = item2.id1 AND item1.id = item2.id
                  WHERE item1.id = ? AND item1.id1 = ? AND item2.id2 = ?";
        $params = [$id, $id1, $id2];
        $types = "iii";
    } elseif ($id1) {
        $query = "SELECT no AS item1_no FROM item1 WHERE id = ? AND id1 = ?";
        $params = [$id, $id1];
        $types = "ii";
    }

    if ($query) {
        $stmt = $db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $errText .= "   大項目NO.-{$row->item1_no}";
        if (isset($row->item2_no)) $errText .= "   中項目NO.-{$row->item2_no}";
        if (isset($row->item3_no)) $errText .= "   小項目NO.-{$row->item3_no}";
    }

    return $errText;
}

// 公開前のデータチェック
if ($publicStatus === 2) {
    $resultCategory = $db->query("SELECT id FROM category ORDER BY id");
    if ($resultCategory && $resultCategory->num_rows > 0) {
        while ($rowCategory = $resultCategory->fetch_object()) {
            $id = $rowCategory->id;

            // 大項目と中項目間の整合性チェック
            $stmtItem2 = $db->prepare("SELECT item2.id1, item1.point, SUM(item2.point) AS point2 FROM item1
                                       INNER JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
                                       WHERE item1.id = ? GROUP BY item2.id1, item1.point");
            $stmtItem2->bind_param("i", $id);
            $stmtItem2->execute();
            $resultItem2 = $stmtItem2->get_result();

            while ($rowItem2 = $resultItem2->fetch_object()) {
                $id1 = $rowItem2->id1;
                if ($rowItem2->point != $rowItem2->point2) {
                    $errorMessage .= "大項目の配点と中項目の配点合計が一致していません。\n" . getErrorText($id, $id1);
                }

                // 中項目と小項目の整合性チェック
                $stmtItem3 = $db->prepare("SELECT item2.id2, item2.point, SUM(item3.point) AS point3 FROM item2
                                           INNER JOIN item3 ON item2.id2 = item3.id2 AND item2.id1 = item3.id1 AND item2.id = item3.id
                                           WHERE item2.id = ? AND item2.id1 = ? GROUP BY item2.id2, item2.point");
                $stmtItem3->bind_param("ii", $id, $id1);
                $stmtItem3->execute();
                $resultItem3 = $stmtItem3->get_result();

                while ($rowItem3 = $resultItem3->fetch_object()) {
                    $id2 = $rowItem3->id2;
                    if ($rowItem3->point != $rowItem3->point3) {
                        $errorMessage .= "中項目の配点と小項目配点合計が一致していません。\n" . getErrorText($id, $id1, $id2);
                    }

                    // 小項目と質問間の整合性チェック
                    $stmtItem4 = $db->prepare("SELECT id3 FROM item3 WHERE id = ? AND id1 = ? AND id2 = ?");
                    $stmtItem4->bind_param("iii", $id, $id1, $id2);
                    $stmtItem4->execute();
                    $resultItem4 = $stmtItem4->get_result();

                    while ($rowItem4 = $resultItem4->fetch_object()) {
                        $id3 = $rowItem4->id3;

                        // 小項目と回答の整合性をチェック
                        $stmtAnswer = $db->prepare("SELECT item4.id4, item3.point, MAX(ans.point) AS point_ans FROM item3
                                                    INNER JOIN item4 ON item3.id = item4.id AND item3.id1 = item4.id1 AND item3.id2 = item4.id2 AND item3.id3 = item4.id3
                                                    INNER JOIN ans ON item4.id = ans.id AND item4.id1 = ans.id1 AND item4.id2 = ans.id2 AND item4.id3 = ans.id3 AND item4.id4 = ans.id4
                                                    WHERE item3.id = ? AND item3.id1 = ? AND item3.id2 = ? AND item3.id3 = ?
                                                    GROUP BY item4.id4, item3.point");
                        $stmtAnswer->bind_param("iiii", $id, $id1, $id2, $id3);
                        $stmtAnswer->execute();
                        $resultAnswer = $stmtAnswer->get_result();

                        $sumPointAnswer = 0;
                        while ($rowAnswer = $resultAnswer->fetch_object()) {
                            $sumPointAnswer += $rowAnswer->point_ans;
                        }

                        if ($sumPointAnswer != $rowItem3->point) {
                            $errorMessage .= "小項目の配点と質問に対する回答選択肢の最高点数合計が一致していません。\n" . getErrorText($id, $id1, $id2, $id3);
                        }
                    }
                }
            }
        }
    } else {
        die("カテゴリデータがありません。");
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <style>
        table {
            margin: 0 auto; /* テーブルを中央揃えにする */
            align-items: center; /* 子要素を左右中央に配置 */
        }

        .center-align {
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* 子要素を上部に配置 */
            text-align: center; /* テキストを中央揃え */
        }

    </style>
    <title>公開設定</title>
</head>
<body>

<div class="center-align">
    <h1>QIシステム</h1>
    <form method="POST" action="">
        <table cellspacing="1" cellpadding="5">
            <tbody>
                <tr>
                    <th><a href="./index.php">メニュー</a> ≫ 公開設定</th>
                </tr>
                <tr>
                    <td>
                        <input type="radio" name="pub" value="1" <?= ($publicStatus == 1) ? "checked" : "" ?>> 公開する<br>
                        <input type="radio" name="pub" value="2" <?= ($publicStatus == 2) ? "checked" : "" ?>> 非公開にする(メンテナンス)<br>
                        <p>配点の変更、年次更新処理は非公開時のみ可能です。</p>
                        <?php if ($errorMessage) : ?>
                            <div class="error-message">配点チェックに不整合があるため公開できません。<br><?= nl2br(htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8')) ?></div>
                        <?php else : ?>
                            <input type="submit" name="set" value="設定">
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>

</body>
</html>
