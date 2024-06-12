<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="./admin.css" media="all">
<title>質問前後のアンケート設定</title>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('click', function() {
            item_select(this.value);
        });
    });
});

function item_select(item) {
    document.forms.maintenance_enqete.id.value = item;
    document.forms.maintenance_enqete.submit();
}
</script>
</head>
<body>
<div align='center'>
    <h1>QIシステム</h1>
    <table cellspacing='1' cellpadding='5'>
        <tr><th><a href='index.php'>メニュー</a> ≫ 質問前後のアンケート設定</th></tr>
        <tr><td>
            <form name="maintenance_enqete" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <?php
                /*******************************************************************
                enquete.php
                    質問前後のアンケート設定
                                            (C)2005-2006,University of Hyougo.
                *******************************************************************/

                require_once("setup.php");

                $db = Connection::connect(); // データベース接続

                $id = $_REQUEST['id'] ?? '1'; // これを保持する
                if (!isset($_REQUEST['id']) || $_REQUEST['id'] === '') {
                    echo "IDが未指定です。";
                    $id = '1';
                } else {
                    $id = $_REQUEST['id'];
                }

                $dat = $_REQUEST['dat'] ?? null;
                $enq = $_REQUEST['enq'] ?? ''; // 最初の設定でデフォルト値を与えます。
                $unit = $_REQUEST['unit'] ?? '';
                $csv = $_REQUEST['csv'] ?? '0';
                $ERR = ''; // ERRはこの時点で初期化

                $uid = $_REQUEST['uid'] ?? '';
                $uids = $uid !== '' ? explode("\n", $uid) : [];
                $type = ''; // 適切なデフォルト値に設定してください。

                if (!empty($dat)) {
                    get_enquete($id, $dat, $enq, $type, $unit, $csv);
                }

                $category = array (
                    10=>"構造(インシデント)",
                    11=>"構造(概要調査)",
                    12=>"構造(アンケート)",
                    20=>"過程(入力看護)",
                    21=>"過程(アンケート)",
                    30=>"アウトカム(アンケート)"
                );

                function getQuestion($id, $id1) {
                    global $db;
                    $sql = "SELECT enq FROM enquete WHERE id = ? AND id1 = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("ii", $id, $id1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    return $result->fetch_assoc();
                }

                function calc($total, $count) {
                    if (!is_numeric($total) || !is_numeric($count)) {
                        return "数値以外が含まれています。";
                    }

                    if ($total == '' || $count == '') {
                        return "未入力項目があります。ご確認の上、もう一度「計算」ボタンをクリックしてください。";
                    } elseif ($total == '0') {
                        return 0;
                    } else {
                        return round(($count / $total * 100000) / 100, 2);
                    }
                }

                function enum_enquete($id)
                {
                    global $db;
                    if ($id === null || $id === '') {
                        echo "IDが未指定です。";
                        return;
                    }

                    if (is_numeric($id)) {
                        $sql = "SELECT * FROM enquete WHERE id = ? ORDER BY id1";
                        if ($stmt = $db->prepare($sql)) {
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                        } else {
                            echo "SQLクエリの準備に失敗しました: " . $db->error;
                            return;
                        }
                    } else {
                        echo "IDが無効です。";
                        return;
                    }

                    if ($result === false) {
                        echo "SQLクエリの実行に失敗しました: " . $db->error;
                        return;
                    }

                    echo "<table cellspacing='1' cellpadding='5'>\n";
                    echo "<tr><th>選択　<input type='submit' name='itemreset' value='解除'></th><th>アンケート</th><th>回答方法</th><th>単位</th><th>CSVデータ統合</th></tr>\n";
                    if (mysqli_num_rows($result) == 0) {
                        echo "<tr><td colspan=5>現在登録されているアンケートはありません</td></tr>\n";
                    } else {
                        while ($fld = mysqli_fetch_object($result)) {
                            echo "<tr>";
                            $checkedAttribute = isset($_REQUEST['dat']) && $_REQUEST['dat'] == $fld->id1 && !isset($_REQUEST['itemreset']) && !isset($_REQUEST['edit']) ? " checked" : "";
                            echo "<td><input type='radio' name='dat' value='".$fld->id1."' onclick='item_select(".$id.")'".$checkedAttribute.">".$fld->id1."</td>";

                            echo "<td>";
                            if ($id == 10) {
                                $question = getQuestion($id, $fld->id1);
                                echo "<a href='./enq_preview.php?id=".$id."&id1=".$fld->id1."' target='_blank'>
                                    1000床あたりの" . htmlspecialchars($question['enq']) . "</a>
                                    <br>
                                    <span style='font-size:9pt;color:#bbb;'>※このアンケートには計算式が含まれています。</span></td>";
                            } else {
                                echo "<a href='./enq_preview.php?id=".$id."&id1=".$fld->id1."' target='_blank'>".nl2br($fld->enq)."</a></td>";
                            }

                            echo "<td>";
                            if ($fld->type == Config::TEXT) {
                                echo "値";
                            } elseif ($fld->type == Config::TEXTAREA) {
                                echo "文章";
                            } elseif ($fld->type == Config::CHECK) {
                                echo "<a href='./enquete_ans.php?id=".$id."&id1=".$fld->id1."'>複数選択</a>";
                            } elseif ($fld->type == Config::RADIO) {
                                echo "<a href='./enquete_ans.php?id=".$id."&id1=".$fld->id1."'>単一選択</a>";
                            }
                            echo "</td>";
                            echo "<td>".(($fld->unit) ? $fld->unit : "-")."</td>";
                            echo "<td>".(($fld->csv == 1) ? "統合" : "-")."</td>";
                            echo "</tr>\n";
                        }
                    }

                    echo "</table>\n";
                }

                if ($dat): ?>
                    <div>
                        <h3>アンケート内容</h3>
                        在院患者総数（60日間の合計数）<input type='text' name='care_total_count' value=''>件
                        <br>
                        <?= htmlspecialchars($question['enq']) ?>（60日間の合計数）<input type='text' name='text2' value=''>件
                        <br>
                        <br><br><br><br><br><br><br><br><br>
                        1000床あたりの<?= htmlspecialchars($question['enq']) ?>　＊半角数字で入力して必ず<span style='color:#f00'>計算ボタンを押してください。</span>
                        <br>
                        <span style='font-size:9pt;color:#bbb;'>※参照中のアンケートには計算式が含まれています。<br>
                        ※ボタン等の押下時動作はこの画面では正常動作しません。</span>
                        <br>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="id1" value="<?= $dat ?>">
                        <input type='submit' value='計算'>
                    </div>
                <?php endif;

                // アンケートの追加
                function add_enquete()
                {
                    global $db;
                    $id = $_REQUEST['id'];

                    $enq = mysqli_real_escape_string($db, $_REQUEST['enq']);
                    $type = mysqli_real_escape_string($db, $_REQUEST['type']);
                    $unit = mysqli_real_escape_string($db, $_REQUEST['unit']);
                    $csv = isset($_REQUEST['csv']) ? "1" : "2";

                    $sql = "SELECT id1 FROM enquete WHERE id=" . intval($id) . " ORDER BY id1 DESC LIMIT 1";
                    $rs = mysqli_query($db, $sql);
                    if (mysqli_num_rows($rs)) {
                        $fld = mysqli_fetch_object($rs);
                        $id1 = ($fld->id1) + 1;
                    } else {
                        $id1 = 1;
                    }
                    $sql = "INSERT INTO enquete(id, id1, enq, type, unit, csv) VALUES(" . intval($id) . "," . intval($id1) . ",'$enq','$type','$unit','$csv')";
                    $rs = mysqli_query($db, $sql);
                }

                // アンケートの編集
                function edit_enquete($id,$id1)
                {
                    global $db;
                    $sql = "UPDATE enquete SET enq = '".$_REQUEST['enq']."',type = '".$_REQUEST['type']."',unit = '".$_REQUEST['unit']."',csv='".(isset($_REQUEST['csv']) ? "1" : "2")."' WHERE id=".$id." AND id1=".$id1;
                    $rs = mysqli_query ( $db ,$sql );
                    if (($_REQUEST['type']==2) || ($_REQUEST['type']==5)) {
                        $sql = "DELETE FROM enq_ans WHERE id=".$id." AND id1=".$id1;
                        $rs = mysqli_query ( $db ,$sql );
                    }
                }

                // アンケートの削除
                function delete_enquete($id,$id1)
                {
                    global $db;
                    $sql = "DELETE FROM enquete WHERE id=".$id." AND id1=".$id1;
                    $rs = mysqli_query ( $db ,$sql );
                    $sql = "DELETE FROM enq_ans WHERE id=".$id." AND id1=".$id1;
                    $rs = mysqli_query ( $db ,$sql );
                }

                // 選択されたデータの各値を取得
                function get_enquete($id,$id1,&$enq,&$type,&$unit,&$csv)
                {
                    global $db;
                    $sql = "SELECT enq,type,unit,csv FROM enquete WHERE id=".$id." AND id1=".$id1;
                    $rs = mysqli_query ( $db ,$sql );
                    $fld = mysqli_fetch_object ( $rs );
                    $enq = $fld->enq;    $type = $fld->type;    $unit = $fld->unit;    $csv = $fld->csv;
                }

                if (isset($_REQUEST['delete'])) {
                    if (empty($dat)) {
                        $ERR = "削除するアイテムが選択されていません";
                    } else {
                        delete_enquete($_REQUEST['id'],$_REQUEST['dat']);
                    }

                } elseif (isset($_REQUEST['edit'])) {
                    if (empty($dat)) {
                        if (empty($enq)) {
                            $ERR = "アンケート内容が入力されていません";
                        } else {
                            add_enquete();
                        }
                    } else {
                        edit_enquete($_REQUEST['id'],$_REQUEST['dat']);
                    }
                } elseif (isset($_REQUEST['add'])) {
                } elseif (!empty($dat)) {
                    get_enquete($_REQUEST['id'],$_REQUEST['dat'],$enq,$type,$unit,$csv);
                }

                echo "<table cellspacing='1' cellpadding='5'>\n";
                echo "<tr><th>対象カテゴリ</th><td><select name='id' onchange='document.maintenance_enqete.submit();'>";
                echo "<optgroup label='構造'>\n";
                echo "<option value='10'".(($_REQUEST['id'] == 10) ? " selected" : "").">インシデント</option>";
                echo "<option value='11'".(($_REQUEST['id'] == 11) ? " selected" : "").">概要調査</option>";
                echo "<option value='12'".(($_REQUEST['id'] == 12) ? " selected" : "").">アンケート</option>";
                echo "<optgroup label='過程'>\n";
                echo "<option value='20'".(($_REQUEST['id'] == 20) ? " selected" : "").">入力看護</option>";
                echo "<option value='21'".(($_REQUEST['id'] == 21) ? " selected" : "").">アンケート</option>";
                echo "<optgroup label='アウトカム'>\n";
                echo "<option value='30'".(($_REQUEST['id'] == 30) ? " selected" : "").">アンケート</option>";
                echo "</select>";
                echo "</td></tr>\n";

                echo "<tr><th>アンケート内容</th><td><textarea name='enq' cols='90' rows='15'>" . htmlspecialchars($enq) . "</textarea></td></tr>\n";

                echo "<tr><th>回答方法</th><td><select name='type'>";
                echo "<option value='2'".(($type == 2) ? " selected" : "").">値(テキスト)</option>";
                echo "<option value='5'".(($type == 5) ? " selected" : "").">文章(テキストエリア)</option>";
                echo "<option value='4'".(($type == 4) ? " selected" : "").">単一選択(ラジオ)</option>";
                echo "<option value='3'".(($type == 3) ? " selected" : "").">複数選択(チェック)</option>";
                echo "</select>";
                echo "</td></tr>\n";

                echo "<tr><th>単位（必要な場合）</th><td><input type='text' name='unit' value='" . htmlspecialchars($unit) . "'></td></tr>\n";
                echo "<tr><th>CSVデータ統合</th><td><input type='checkbox' name='csv'" . ($csv == '1' ? " checked" : "") . ">CSVデータ統合する</td></tr>\n";
                echo "<tr><td colspan='2'><div align='right' style='margin:5px;'><input type='reset' value='リセット'>　";
                $datExists = isset($_REQUEST['dat']) && $_REQUEST['dat'];
                if ($datExists && !isset($_REQUEST['itemreset']) && !isset($_REQUEST['delete']) && !isset($_REQUEST['edit'])) {
                    echo "<input name='delete' type='submit' value='　削　除　'>　";
                }

                $editButtonLabel = $datExists && !isset($_REQUEST['itemreset']) && !isset($_REQUEST['delete']) && !isset($_REQUEST['edit']) ? "　編　集　" : "　登　録　";
                echo "<input name='edit' type='submit' value='".$editButtonLabel."'></div></td></tr>\n";

                echo "</table>\n";

                echo "<font color='red'>" . htmlspecialchars($ERR) . "</font><br>\n";

                $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '10';
                echo "<p>".$category[$id]."</p>\n";

                enum_enquete($id);

                ?>
            </form>
        </td></tr>
    </table>
</div>
</body>
</html>
