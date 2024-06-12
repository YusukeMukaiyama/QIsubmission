<!-- item2_template.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="admin.css"> 
    <title>中項目編集</title>
</head>
<body>

<div align='center'>

    <h1>QIシステム</h1>

    <form method='POST' action='<?= $_SERVER['PHP_SELF'] ?>'>
        <table cellspacing='1' cellpadding='5'>
            <tr>
                <th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?= $id ?>'>大項目編集</a> ≫ 中項目編集</th>
            </tr>
            <tr><td><?= $category ?></td></tr>
            <tr><th><?= $item1_no ?>. <?= $item1_name ?></th></tr>
            <tr><td>
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
                            <th>項目</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>登録</th>
                        </tr>
                        <tr>
                            <td><input size='90' type='text' name='newitem' value='<?= $adderrstr ? $_POST['newitem'] : '' ?>'></td>
                            <td><input size='4' type='text' maxlength='4' name='newpoint' value=''></td>
                            <td><input size='4' type='text' maxlength='4' name='up_recommendation<?= $item['id2'] ?>' value='<?= $item['up_recommendation'] ?? '' ?>'></td>
                            <td><input size='4' type='text' maxlength='4' name='recommendation<?= $item['id2'] ?>' value='<?= $item['recommendation'] ?? '' ?>'></td>
                            <td><input type='submit' name='add' value='登録'></td>
                        </tr>
                    </table>
                <?php endif; ?>
                <p>登録内容</p>
                <table cellspacing='1' cellpadding='5'>
                    <tr><th>No.</th><th>項目</th><th>配点</th><th>高得点基準</th><th>低得点基準</th><th>小項目</th><?php if ($public == '2'): ?><th>並べ替え</th><th>削除</th><?php endif; ?></tr>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td align='right'><?= $item['item1_no'] ?>.<?= $item['item2_no'] ?></td>
                            <td><input size='90' type='text' name='name<?= $item['id2'] ?>' value='<?= $item['item2_name'] ?>'></td>
                            <td align='right'>
                                <?php if ($public == '2'): ?>
                                    <input size='4' type='text' maxlength='4' name='point<?= $item['id2'] ?>' value='<?= $item['point'] ?>'>
                                <?php else: ?>
                                    <?= $item['point'] ?>
                                <?php endif; ?>
                            </td>
                            <td><input size='4' type='text' maxlength='4' name='up_recommendation<?= $item['id2'] ?>' value='<?= $item['up_recommendation'] ?? '' ?>'></td>
                            <td><input size='4' type='text' maxlength='4' name='recommendation<?= $item['id2'] ?>' value='<?= $item['recommendation'] ?? '' ?>'></td>
                            <td><a href='item3.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $item['id2'] ?>'>小項目</a></td>
                            <?php if ($public == '2'): ?>
                                <td>
                                    <?php if ($item['item2_no'] != 1): ?>
                                        <a href='item2.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $item['id2'] ?>&up=<?= $item['item2_no'] ?>'>▲</a>
                                    <?php endif; ?>
                                    <?php if ($item['item2_no'] != $maxno): ?>
                                        <a href='item2.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $item['id2'] ?>&dwn=<?= $item['item2_no'] ?>'>▼</a>
                                    <?php endif; ?>
                                </td>
                                <td><input type="checkbox" name='del<?= $item['id2'] ?>'></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div align='right' style='margin:5px;'>
                    <input type='reset' name='reset' value=' リセット '>
                    <input type='submit' name='edit' value='   編   集   '>
                </div>
            </td></tr>
        </table>
    </form>

</div>

</body>
</html>
