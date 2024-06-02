<?php
// 必要なPHPファイルをインクルード
require_once("setup.php");

// データベースに接続
$db = Connection::connect();

// 初期値設定
$publicStatus = null;
$errorMessage = "";

// 公開 / 非公開設定
$pubStatus = $_POST['pub'] ?? null;
if ($pubStatus !== null) {
    // 公開/非公開のステータスを更新
    $queryUpdate = "UPDATE public SET pub = ?";
    $stmt = $db->prepare($queryUpdate);
    $stmt->bind_param("s", $pubStatus);
    $stmt->execute();

    // 公開時、最新の年度データを履歴に保存
    if ($pubStatus ==  1) {
        $queryYear = "SELECT LEFT(uid, 2) AS year FROM usr GROUP BY LEFT(uid, 2) ORDER BY LEFT(uid, 2) DESC LIMIT 1";
        $result = $db->query($queryYear);
        $year = $result->fetch_object()->year;

        // 古い履歴を削除
        $queryDeleteHistory = "DELETE FROM history WHERE year = ?";
        $stmt = $db->prepare($queryDeleteHistory);
        $stmt->bind_param("s", $year);
        $stmt->execute();

        // 新しい履歴を追加
        $queryInsertHistory = "INSERT INTO history (id, year, id1, no, name, point) SELECT id, ?, id1, no, name, point FROM item1";
        $stmt = $db->prepare($queryInsertHistory);
        $stmt->bind_param("s", $year);
        $stmt->execute();
    }
}

// 公開 / 非公開取得
$queryGetPublic = "SELECT pub FROM public";
$result = $db->query($queryGetPublic);

if (!$result || $result->num_rows == 0) {
    die("データの取得に失敗しました -2-1");
}
$publicStatus = $result->fetch_object()->pub;

// エラーテキストを取得する関数
function getErrorText($id, $id1 = "", $id2 = "", $id3 = "", $id4 = "")
{
    global $db;
    $category = "";

    // カテゴリを定義
    switch ($id) {
        case 1: $category = "構造"; break;
        case 2: $category = "過程"; break;
        case 3: $category = "アウトカム"; break;
        default: break;
    }

    $errText = "※カテゴリ-{$category}";
    // 大項目、中項目、小項目のエラーメッセージを生成
    if ($id3) {
        $query = "SELECT item1.no AS item1_no, item2.no AS item2_no, item3.no AS item3_no 
                  FROM (item1 INNER JOIN item2 ON item1.id1 = item2.id1 AND item1.id = item2.id) 
                  INNER JOIN item3 ON item2.id2 = item3.id2 AND item2.id1 = item3.id1 AND item2.id = item3.id 
                  WHERE item1.id = ? AND item1.id1 = ? AND item2.id2 = ? AND item3.id3 = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iiii", $id, $id1, $id2, $id3);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $errText .= "   大項目NO.-{$row->item1_no}   中項目NO.-{$row->item2_no}   小項目NO.-{$row->item3_no}";
    } elseif ($id2) {
        $query = "SELECT item1.no AS item1_no, item2.no AS item2_no 
                  FROM item1 INNER JOIN item2 ON item1.id1 = item2.id1 AND item1.id = item2.id 
                  WHERE item1.id = ? AND item1.id1 = ? AND item2.id2 = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("iii", $id, $id1, $id2);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $errText .= "   大項目NO.-{$row->item1_no}   中項目NO.-{$row->item2_no}";
    } elseif ($id1) {
        $query = "SELECT no AS item1_no FROM item1 WHERE id = ? AND id1 = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ii", $id, $id1);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $errText .= "   大項目NO.-{$row->item1_no}";
    }
    return $errText;
}

// 公開前のデータチェック
if ($publicStatus == 2) {
    // カテゴリの取得
    $queryCategory = "SELECT id FROM category ORDER BY id";
    $resultCategory = $db->query($queryCategory);

    if ($resultCategory && $resultCategory->num_rows > 0) {
        while ($rowCategory = $resultCategory->fetch_object()) {
            $id = $rowCategory->id;

            // 大項目と中項目間の整合性チェック
            $queryItem2 = "SELECT item2.id1, item1.point, SUM(item2.point) AS point2 
                           FROM item1 INNER JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1 
                           WHERE item1.id = ? GROUP BY item2.id1, item1.point";
            $stmtItem2 = $db->prepare($queryItem2);
            $stmtItem2->bind_param("i", $id);
            $stmtItem2->execute();
            $resultItem2 = $stmtItem2->get_result();

            while ($rowItem2 = $resultItem2->fetch_object()) {
                $id1 = $rowItem2->id1;

                if ($rowItem2->point != $rowItem2->point2) {
                    $errorMessage .= "大項目の配点と中項目の配点合計が一致していません。\n" . getErrorText($id, $id1);
                }

                // 中項目と小項目の整合性チェック
                $queryItem3 = "SELECT item2.id2, item2.point, SUM(item3.point) AS point3 
                               FROM item2 INNER JOIN item3 ON item2.id2 = item3.id2 AND item2.id1 = item3.id1 AND item2.id = item3.id 
                               WHERE item2.id = ? AND item2.id1 = ? 
                               GROUP BY item2.id2, item2.point";
                $stmtItem3 = $db->prepare($queryItem3);
                $stmtItem3->bind_param("ii", $id, $id1);
                $stmtItem3->execute();
                $resultItem3 = $stmtItem3->get_result();

                while ($rowItem3 = $resultItem3->fetch_object()) {
                    $id2 = $rowItem3->id2;

                    if ($rowItem3->point != $rowItem3->point3) {
                        $errorMessage .= "中項目の配点と小項目配点合計が一致していません。\n" . getErrorText($id, $id1, $id2);
                    }

                    // 小項目と質問間の整合性チェック
                    $queryItem4 = "SELECT id3 FROM item3 WHERE id = ? AND id1 = ? AND id2 = ?";
                    $stmtItem4 = $db->prepare($queryItem4);
                    $stmtItem4->bind_param("iii", $id, $id1, $id2);
                    $stmtItem4->execute();
                    $resultItem4 = $stmtItem4->get_result();

                    while ($rowItem4 = $resultItem4->fetch_object()) {
                        $id3 = $rowItem4->id3;

                        // 小項目と回答の整合性をチェック
                        $queryAnswer = "SELECT item4.id4, item3.point, MAX(ans.point) AS point_ans 
                                        FROM (item3 INNER JOIN item4 ON item3.id = item4.id AND item3.id1 = item4.id1 AND item3.id2 = item4.id2 AND item3.id3 = item4.id3) 
                                        INNER JOIN ans ON item4.id = ans.id AND item4.id1 = ans.id1 AND item4.id2 = ans.id2 AND item4.id3 = ans.id3 AND item4.id4 = ans.id4 
                                        WHERE item3.id = ? AND item3.id1 = ? AND item3.id2 = ? AND item3.id3 = ? 
                                        GROUP BY item4.id4, item3.point";
                        $stmtAnswer = $db->prepare($queryAnswer);
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

// HTMLテンプレートファイルをインクルードし、データを渡す
include 'templates/public_template.php';

// データベース接続を閉じる
$db->close();
