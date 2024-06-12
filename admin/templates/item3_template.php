<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="admin.css"> 
    <title>小項目編集</title>
</head>
<body>

<div align='center'>

    <h1>QIシステム</h1>

    <form method='POST' action='<?= $_SERVER['PHP_SELF'] ?>' enctype="multipart/form-data">
        <table cellspacing='1' cellpadding='5'>
            <tr>
                <th><a href='index.php'>メニュー</a> ≫ <a href='item1.php?id=<?= $id ?>'>大項目編集</a> ≫ <a href='item2.php?id=<?= $id ?>&id1=<?= $id1 ?>'>中項目編集</a> ≫ 小項目編集</th>
            </tr>
            <tr><td><?= $category ?></td></tr>
            <tr><th><?= $item1_no ?>. <?= $item1_name ?></th></tr>
            <tr><th><?= $item1_no ?>. <?= $item2_no ?>. <?= $item2_name ?></th></tr>
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
                            <th>項目</th><th>配点</th><th>登録</th>
                        </tr>
                        <tr>
                            <td><input size='60' type='text' name='newitem' value='<?= $adderrstr ? $_POST['newitem'] : '' ?>'></td>
                            <td><input size='4' type='text' maxlength='4' name='newpoint' value='<?= $_POST['newpoint'] ?? '' ?>'></td>
                            <td><input type='submit' name='add' value='登録'></td>
                        </tr>
                    </table>
                <?php endif; ?>
                <p>登録内容</p>
                <table cellspacing='1' cellpadding='5'>
                <tr>
                <th>No.</th>
                <th>項目</th>
                <th>配点</th>
                <th>質問</th>
                <?php if ($public == '2'): ?>
                <th>並べ替え</th>
                <th>削除</th>
                <?php endif; ?>
                <th>画像登録</th>
                <th>登録</th>
                <th>画像削除</th>
            </tr>
            <?php foreach ($subItems as $item): ?>
            <tr>
                <td align='right'><?= $item['no'] ?></td>
                <td><input size='60' type='text' name='name<?= $item['id3'] ?>' value='<?= htmlspecialchars($item['name']) ?>'></td>
                <td align='right'>
                    <?php if ($public == '2'): ?>
                        <input size='4' type='text' maxlength='4' name='point<?= $item['id3'] ?>' value='<?= htmlspecialchars($item['point']) ?>'>
                    <?php else: ?>
                        <?= htmlspecialchars($item['point']) ?>
                    <?php endif; ?>
                </td>
                <td><a href='item4.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $id2 ?>&id3=<?= $item['id3'] ?>'>質問</a></td>
                <?php if ($public == '2'): ?>
                    <td>
                        <?php if ($item['no'] != 1): ?>
                            <a href='item3.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $id2 ?>&id3=<?= $item['id3'] ?>&up=<?= $item['no'] ?>'>▲</a>
                        <?php endif; ?>
                        <?php if ($item['no'] != $maxno): ?>
                            <a href='item3.php?id=<?= $id ?>&id1=<?= $id1 ?>&id2=<?= $id2 ?>&id3=<?= $item['id3'] ?>&dwn=<?= $item['no'] ?>'>▼</a>
                        <?php endif; ?>
                    </td>
                    <td><input type="checkbox" name='del<?= $item['id3'] ?>'></td>
                <?php endif; ?>
                <td>
                    <input type="file" name="img<?= $item['id3'] ?>">
                </td>
                <td>
                    <?php
                    // ファイルの存在を確認
                    $imgPath = "path/to/images/{$id}_{$id1}_{$id2}_{$item['id3']}.jpg";
                    if (file_exists($imgPath)) {
                        echo "<a href='{$imgPath}' target='_blank'>あり</a>";
                    } else {
                        echo "なし";
                    }
                    ?>
                </td>
                <td><input type="checkbox" name='imgdel<?= $item['id3'] ?>' <?= file_exists($imgPath) ? '' : 'disabled' ?>></td>
            </tr>
            <?php endforeach; ?>

                </table>
                <div align='right' style='margin:5px;'>
                    <input type='reset' name='reset' value=' リセット '>
                    <input type='submit' name='edit' value='   編   鎖   '>
                </div>
            </td></tr>
        </table>
    </form>

</div>

</body>
</html>
