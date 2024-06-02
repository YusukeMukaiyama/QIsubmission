<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Style-Type" content="text/css">
    <link rel="stylesheet" href="/QIsystem/1new/public/admin.css"> 
    <title>ユーザ一覧</title>
</head>
<body>
    <div align='center'>
        <h1>QIシステム</h1>

        <form method='POST' action='usr_reg.php'>
            <!-- Search Criteria -->
            <p>検索条件を指定してください。</p>
            <table cellspacing='1' cellpadding='5'>
                <tr><th rowspan='4'><input type='radio' name='enumtype' value='1' <?php echo ($enumtype != 2 ? 'checked' : ''); ?>>項目を選択して検索</th></tr>
                <tr>
                    <th>年度</th>
                    <td>
                        <select name='year' onChange='javascript:submit();'>
                            <option value='0'>選択して下さい</option>
                            <?php foreach ($years as $year): ?>
                                <option value='<?php echo $year; ?>' <?php echo ($year == ($_POST['year'] ?? '') ? 'selected' : ''); ?>><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>都道府県</th>
                    <td>
                        <select name='pref' onChange='javascript:submit();'>
                            <option value='0'>選択して下さい</option>
                            <?php foreach ($prefs as $pref): ?>
                                <option value='<?php echo $pref; ?>' <?php echo ($pref == ($_POST['pref'] ?? '') ? 'selected' : ''); ?>><?php echo $pref; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>病院No.</th>
                    <td>
                        <select name='hosp' onChange='javascript:submit();'>
                            <option value='0'>選択して下さい</option>
                            <?php foreach ($hospitals as $hospital): ?>
                                <option value='<?php echo $hospital; ?>' <?php echo ($hospital == ($_POST['hosp'] ?? '') ? 'selected' : ''); ?>><?php echo $hospital; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>病棟No.</th>
                    <td>
                        <select name='ward' onChange='javascript:submit();'>
                            <option value='0'>選択して下さい</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value='<?php echo $ward; ?>' <?php echo ($ward == ($_POST['ward'] ?? '') ? 'selected' : ''); ?>><?php echo $ward; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><input type='radio' name='enumtype' value='2' <?php echo ($enumtype == 2 ? 'checked' : ''); ?>>IDを指定して検索</th>
                    <th>ID</th>
                    <td>
                        <input size='25' type='text' name='UserID' value='<?php echo $_POST['UserID'] ?? ''; ?>'>
                    </td>
                </tr>
                <tr>
                    <td colspan='3'>
                        <div align='right' style='margin:5px;'>
                            <input type='submit' name='search' value='　検　索　'>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Search Results -->
            <?php if (!empty($users)): ?>
                <p>検索結果　　<input type='submit' name='back' value='　≪戻る　'></p>
                <table cellspacing='1' cellpadding='5'>
                    <tr><th>ユーザID</th><th>パスワード</th><th>回答状況</th><th>ステータス</th><th>削除</th></tr>
                    <?php foreach ($users as $index => $user): ?>
                        <tr>
                            <td><a href='list.php?uid=<?php echo $user['uid']; ?>' target='_blank'><?php echo $user['uid']; ?></a>
                                <input type='hidden' name='uid<?php echo $index; ?>' value='<?php echo $user['uid']; ?>'>
                            </td>
                            <td><?php echo $user['pass']; ?></td>
                            <td>
                                <input type='radio' name='comp<?php echo $index; ?>' value='COMPLETE' <?php echo ($user['comp'] == 'COMPLETE' ? 'checked' : ''); ?>>完了
                                <input type='radio' name='comp<?php echo $index; ?>' value='UNCOMPLETE' <?php echo ($user['comp'] == 'UNCOMPLETE' ? 'checked' : ''); ?>>未完了
                            </td>
                            <td>
                                <input type='radio' name='enable<?php echo $index; ?>' value='ENABLE' <?php echo ($user['del'] != 'DISABLE' ? 'checked' : ''); ?>>有効
                                <input type='radio' name='enable<?php echo $index; ?>' value='DISABLE' <?php echo ($user['del'] == 'DISABLE' ? 'checked' : ''); ?>>無効
                            </td>
                            <td><input type='checkbox' name='ureg<?php echo $index; ?>'>削除</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div align='right' style='margin:5px;'>
                    <input type='reset' name='reset' value='リセット'>　　
                    <input type='submit' name='update' value='　更　新　'>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
