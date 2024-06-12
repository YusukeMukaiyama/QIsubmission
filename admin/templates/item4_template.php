<?php
// フォームデータを安全に取得するための関数
function safeGet($key, $default = '') {
    return $_POST[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="admin.css"> 
    <title>質問編集</title>
</head>
<body>
<div align='center'>
    <h1>QIシステム</h1>
    <form method='POST' action='<?= $_SERVER['PHP_SELF'] ?>' enctype="multipart/form-data">
        <table cellspacing='1' cellpadding='5'>
            <tr>
                <th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?= $id ?>'>大項目編集</a> ≫ <a href='item2.php?id=<?= $id ?>&id1=<?= $id1 ?>'>中項目編集</a> ≫ <a href='item3.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $id2 ?>'>小項目編集</a> ≫ 質問編集</th>
            </tr>
            <tr><td><?= $category ?></td></tr>
            <tr><th><?= $item1_no ?>. <?= $item1_name ?></th></tr>
            <tr><th><?= $item1_no ?>. <?= $item2_no ?>. <?= $item2_name ?></th></tr>
            <tr><th><?= $item1_no ?>. <?= $item2_no ?>. <?= $item3_no ?>. <?= $item3_name ?></th></tr>
            <tr><td>
                <!-- Error messages -->
                <?php if ($adderrstr): ?>
                    <div style="color: red;"><?= $adderrstr ?></div>
                <?php endif; ?>
                <?php if ($errstr): ?>
                    <div style="color: red;"><?= $errstr ?></div>
                <?php endif; ?>
                <?php if ($public == '2'): ?>
                    <p>新規登録</p>
                    <table cellspacing='1' cellpadding='5'>
                        <tr>
                            <th>質問</th><th>回答方法</th><th>登録</th>
                        </tr>
                        <tr>
                            <td><textarea name='newitem' rows='10' cols='40'><?= htmlspecialchars(safeGet('newitem', '')) ?></textarea></td>
                            <td>
                                <?php $qtype = safeGet('qtype', '1'); ?>
                                <input size='4' type='radio' name='qtype' value='1'<?= $qtype == '1' ? ' checked' : '' ?>>選択式
                                <input size='4' type='radio' name='qtype' value='2'<?= $qtype == '2' ? ' checked' : '' ?>>入力式
                            </td>
                            <td><input type='submit' name='add' value='登録'></td>
                        </tr>
                    </table>
                <?php endif; ?>
                <p>登録内容</p>
                <table cellspacing='1' cellpadding='5'>
                    <tr>
                        <th>No.</th>
                        <th>質問</th>
                        <th>回答方法</th>
                        <?php if ($public == '2'): ?>
                            <th>並べ替え</th>
                            <th>削除</th>
                        <?php endif; ?>
                    </tr>
                    <?php foreach ($array_id as $id4): ?>
                        <tr>
                            <td align='right'><?= $id4 ?></td>
                            <td><textarea name='name<?= $id4 ?>' rows='5' cols='80'><?= htmlspecialchars(safeGet('name'.$id4, '')) ?></textarea></td>
                            <td><?= safeGet('qtype'.$id4, '1') == '1' ? '選択式' : '入力式' ?></td>
                            <?php if ($public == '2'): ?>
                                <td>
                                    <?php if ($id4 != 1): ?>
                                        <a href='item4.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $id2 ?>&id3=<?= $id3 ?>&id4=<?= $id4 ?>&up=<?= $id4 ?>'>▲</a>
                                    <?php endif; ?>
                                    <?php if ($id4 != $maxid): ?>
                                        <a href='item4.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $id2 ?>&id3=<?= $id3 ?>&id4=<?= $id4 ?>&dwn=<?= $id4 ?>'>▼</a>
                                    <?php endif; ?>
                                </td>
                                <td><input type="checkbox" name='del<?= $id4 ?>'></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div align='right' style='margin:5px;'>
                    <input type='reset' name='reset' value='リセット'>
                    <input type='submit' name='edit' value='編集'>
                </div>
            </td></tr>
        </table>
    </form>
</div>
</body>
</html>
