<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="admin.css" media="all">
    <title>質問前後のアンケート回答設定</title>
</head>
<body>
    <div align='center'>
        <h1>QIシステム</h1>
        <!-- フォームの定義。アクションとして現在のPHPスクリプトを指定 -->
        <form name='maintenance_enqete_ans' method='POST' action='<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>'>
            <table cellspacing='1' cellpadding='5'>
                <tr>
                    <th>
                        <a href='index.php'>メニュー</a> ≫ <a href='enquete.php?id=<?= htmlspecialchars($_REQUEST['id'], ENT_QUOTES, 'UTF-8') ?>'>質問前後のアンケート設定</a> ≫ 質問前後のアンケート回答設定
                    </th>
                </tr>
                <tr>
                    <td>
                        <?php
                        // 必要な設定ファイルの読み込み
                        require_once("setup.php");

                        // カテゴリの定義
                        $category = array(1 => "STRUCTURE", 2 => "PROCESS", 3 => "OUTCOME");
                        $category_jpn = array(1 => "構造", 2 => "過程", 3 => "アウトカム");

                        // 初期化
                        $ERR = "";
                        $ans = "";

                        // POSTデータの取得と初期化
                        $dat = isset($_POST['dat']) ? $_POST['dat'] : null;

                        if ($dat !== null) {
                            // $datを使った処理（必要に応じて追加）
                        }

                        // 質問前後のアンケート回答を列挙する関数
                        function enum_enq_ans($id, $id1)
                        {
                            global $db, $category, $dat;

                            // SQLクエリの構築
                            $sql = "SELECT id2, ans FROM enq_ans WHERE id=" . intval($id) . " AND id1='" . mysqli_real_escape_string($db, $id1) . "' ORDER BY id2";
                            $rs = mysqli_query($db, $sql);

                            // テーブルの生成とデータの表示
                            echo "<table cellspacing='1' cellpadding='5'>\n";
                            echo "<tr><th>選択　<input type='submit' name='itemreset' value='解除'></th><th>回答</th></tr>\n";
                            if (!mysqli_num_rows($rs)) {
                                echo "<tr><td colspan=2>現在登録されている回答はありません</td></tr>\n";
                            } else {
                                while ($fld = mysqli_fetch_object($rs)) {
                                    echo "<tr><td><input type='radio' name='dat' value='" . $fld->id2 . "' onclick='document.maintenance_enqete_ans.submit();'" .
                                        ($dat === $fld->id2 ? " checked" : "") . ">" . $fld->id2 . "</td><td>" . nl2br($fld->ans) . "</td></tr>\n";
                                }
                            }
                            echo "</table>\n";
                            echo "<br>\n";
                        }

                        // 新しい回答を追加する関数
                        function add_enq_ans()
                        {
                            global $db;
                            $id = intval($_POST['id']);
                            $id1 = mysqli_real_escape_string($db, $_POST['id1']);
                            $ans = mysqli_real_escape_string($db, $_POST['ans']);

                            // 最新のid2を取得
                            $sql = "SELECT id2 FROM enq_ans WHERE id=" . $id . " AND id1='" . $id1 . "' ORDER BY id2 DESC LIMIT 1";
                            $rs = mysqli_query($db, $sql);

                            $id2 = mysqli_num_rows($rs) ? (mysqli_fetch_object($rs)->id2 + 1) : 1;

                            // 新しい回答を挿入
                            $sql = "INSERT INTO enq_ans(id, id1, id2, ans) VALUES(" . $id . ", '" . $id1 . "', " . $id2 . ", '" . $ans . "')";
                            mysqli_query($db, $sql);
                        }

                        // 回答を編集する関数
                        function edit_enq_ans($id, $id1, $id2)
                        {
                            global $db;
                            $id = intval($id);
                            $id1 = mysqli_real_escape_string($db, $id1);
                            $id2 = intval($id2);
                            $ans = mysqli_real_escape_string($db, $_POST['ans']);

                            // 回答を更新
                            $sql = "UPDATE enq_ans SET ans = '" . $ans . "' WHERE id=" . $id . " AND id1='" . $id1 . "' AND id2=" . $id2;
                            mysqli_query($db, $sql);
                        }

                        // 回答を削除する関数
                        function delete_enq_ans($id, $id1, $id2)
                        {
                            global $db;
                            $id = intval($id);
                            $id1 = mysqli_real_escape_string($db, $id1);
                            $id2 = intval($id2);

                            // 回答を削除
                            $sql = "DELETE FROM enq_ans WHERE id=" . $id . " AND id1='" . $id1 . "' AND id2=" . $id2;
                            mysqli_query($db, $sql);
                        }

                        // 指定された回答を取得する関数
                        function get_enq_ans($id, $id1, $id2, &$ans)
                        {
                            global $db;
                            $id = intval($id);
                            $id1 = mysqli_real_escape_string($db, $id1);
                            $id2 = intval($id2);

                            // 回答を取得
                            $sql = "SELECT ans FROM enq_ans WHERE id=" . $id . " AND id1='" . $id1 . "' AND id2=" . $id2;
                            $rs = mysqli_query($db, $sql);
                            $fld = mysqli_fetch_object($rs);
                            $ans = $fld->ans;
                        }

                        // データベース接続の確立
                        $db = Connection::connect();

                        // リクエストデータの取得
                        $id = intval($_REQUEST['id']);
                        $id1 = mysqli_real_escape_string($db, $_REQUEST['id1']);

                        // フォームの処理
                        if (isset($_POST['delete'])) {
                            if (!isset($_POST['dat']) || !$_POST['dat']) {
                                $ERR = "削除するアイテムが選択されていません";
                            } else {
                                delete_enq_ans($id, $id1, $_POST['dat']);
                            }
                        } elseif (isset($_POST['edit'])) {
                            if (!$_POST['ans']) {
                                $ERR = "回答が入力されていません";
                            } else {
                                if (!isset($_POST['dat']) || !$_POST['dat']) {
                                    add_enq_ans();
                                } else {
                                    edit_enq_ans($id, $id1, $_POST['dat']);
                                }
                            }
                        } elseif ($dat) {
                            get_enq_ans($id, $id1, $dat, $ans);
                        }

                        // 編集画面の表示
                        echo "<table cellspacing='1' cellpadding='5'>\n";
                        echo "<tr><th>回答</th><td><textarea name='ans' cols='80' rows='7'>" . htmlspecialchars($ans, ENT_QUOTES, 'UTF-8') . "</textarea></td></tr>\n";
                        echo "<tr><td colspan='2'><div align='right' style='margin:5px;'><input type='reset' value='リセット'>　";

                        // ボタンの表示制御
                        $datCheck = $dat && !isset($_POST['itemreset']) && !isset($_POST['delete']) && !isset($_POST['edit']);
                        echo $datCheck ? "<input name='delete' type='submit' value='　削　除　'>　" : "";
                        echo "<input name='edit' type='submit' value='" . ($datCheck ? "　編　集　" : "　登　録　") . "'></div></td></tr>\n";
                        echo "</table>\n";
                        echo "<p><span style='color:red;'>" . htmlspecialchars($ERR, ENT_QUOTES, 'UTF-8') . "</span></p>\n";

                        // 質問前後のアンケート回答の列挙
                        enum_enq_ans($id, $id1);

                        // 隠しフィールドでIDを送信
                        echo "<input type='hidden' name='id' value='" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "'>";
                        echo "<input type='hidden' name='id1' value='" . htmlspecialchars($id1, ENT_QUOTES, 'UTF-8') . "'>";
                        ?>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>
