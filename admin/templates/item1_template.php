<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="admin.css"> 
<title>大項目編集</title>
</head>
<body>

<div align="center">
    <h1>QIシステム</h1>

    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
        <table cellspacing="1" cellpadding="5">
            <tr>
                <th><a href="index.php">メニュー</a> ≫ 大項目編集</th>
            </tr>
            <?php if ($errstr): ?>
            <tr>
                <td><?= htmlspecialchars($errstr); ?></td>
            </tr>
            <?php endif; ?>
            <?php foreach ($categories as $category_id => $category_data): ?>
            <tr>
                <td>
                    <fieldset>
                        <legend><?= htmlspecialchars($category_data['category']) ?></legend>
                        <table cellspacing='1' cellpadding='5'>
                            <tr>
                                <th>No.</th>
                                <th>内容</th>
                                <th>配点</th>
                                <th>高得点基準</th>
                                <th>低得点基準</th>
                                <th>中項目</th>
                            </tr>
                            <?php foreach ($category_data['items'] as $item): ?>
                            <tr>
                                <td align='right'><?= htmlspecialchars($item->no) ?></td>
                                <td><input size='60' type='text' name='name<?= $item->cat_id . $item->id1 ?>' value='<?= htmlspecialchars($item->name) ?>'></td>
                                <td align='right'>
                                    <?php if ($public == 'OPEN'): ?>
                                        <?= htmlspecialchars($item->point) ?>
                                    <?php else: ?>
                                        <input size='4' type='text' maxlength='4' name='point<?= $item->cat_id . $item->id1 ?>' value='<?= htmlspecialchars($item->point) ?>'>
                                    <?php endif; ?>
                                </td>
                                <td style='text-align:center;'><input size='4' type='text' maxlength='4' name='up_recommendation<?= $item->cat_id . $item->id1 ?>' value='<?= htmlspecialchars($item->up_recommendation) ?>'></td>
                                <td style='text-align:center;'><input size='4' type='text' maxlength='4' name='recommendation<?= $item->cat_id . $item->id1 ?>' value='<?= htmlspecialchars($item->recommendation) ?>'></td>
                                <td><a href='item2.php?id=<?= $item->cat_id ?>&id1=<?= $item->id1 ?>'>中項目</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <div align='right' style='margin:5px;'>
                            <input type='reset' name='reset' value='リセット' >
                            <input type='submit' name='regist<?= $category_id ?>' value='   登   録   ' >
                        </div>
                    </fieldset>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </form>
</div>

</body>
</html>
