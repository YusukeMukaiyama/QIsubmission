<?php
/*******************************************************************
usr_reg.php
--ユーザ登録メンテナンス
*******************************************************************/
  require_once("setup.php");

  $db = Connection::connect(); // データベース接続

  /*******************************************************************
  simplepasswd
    概要：ランダムな数字6桁のパスワードを生成する
    引数：なし
    戻値：生成された数字列を返す
  *******************************************************************/
  function simplepasswd()
  {
    $passwd = "";
    $tmp = array (0,1,2,3,4,5,6,7,8,9);
    for ($i=0;$i<6;$i++) {
      mt_srand((double)microtime()*1000000); 
      $passwd .= $tmp[mt_rand(0,9)];
    }
    return $passwd;
  }

  /*******************************************************************
  random_char
    概要：ランダムな１文字を生成する
    引数：なし
    戻値：生成された文字を返す
  *******************************************************************/
  function random_char()
  {
    $p = rand(0, 99);
    if        ($p < 20) {
      return chr( rand(48,  57) );  // 数値　 0-9
    } else if ($p < 60) {
      return chr( rand(65,  90) );  // 大文字 A-Z
    } else if ($p < 100) {
      return chr( rand(97, 122) );  // 小文字 a-z
    } else {
      return "@"; // ありえない
    }
  }

  /*******************************************************************
  random_str
    概要：ランダムなパスワード文字列を生成する
    引数：$minSize  パスワード文字数、最小値
    　　：$maxSize  パスワード文字数、最大値
    戻値：生成された文字列を返す
  *******************************************************************/
  function random_str($minSize, $maxSize, $no)
  {
    // 乱数ジェネレータ初期化
    list($usec, $sec) = explode(' ', microtime());
    (float)$sec + ((float)$usec * 100000);
    $sec = $sec / $no;
    srand($sec);

    $ret = "";
    $len = rand($minSize, $maxSize);
    for ($i=0;$i<$len;$i++) {
      $ch = random_char();
      if ($ch == "9" || $ch == "q" || $ch == "0" || $ch == "O" || $ch == "I" || $ch == "l" || $ch == "1") {
        $i--;
        continue;
      }
      $ret .= $ch;
    }
    return $ret;
  }

  /*******************************************************************
  check_uid
    概要：uid の形式が正しいかチェックする
    引数：$uid   ユーザID文字列
    戻値：正常：空文字列
    　　：異常：エラー文字列
  *******************************************************************/
  function check_uid($db, $uid, $year)
  {
    // 正規表現で一発チェック
    if (!preg_match ("/^([0-9]{2})-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
      return "「ユーザID:".$uid."」の登録形式に誤りがあります。<br>\n";
    }
    if (!preg_match ("/^".$year."-([0-9]{2})-([0-9]{4})-([0-9]{2})-([0-9]{3})$/", $uid)) {
      return "「ユーザID:".$uid."」の登録年度に誤りがあります。<br>\n";
    }

    // 都道府県チェック
    $tmp = explode("-", $uid);
    $pref = (int)$tmp[1];
    if ($pref < 1 || $pref > 47) {
      return "「ユーザID:".$uid."」の登録形式に誤りがあります。<br>\n";
    }

    // 重複チェック
    $sql = "SELECT uid FROM usr WHERE uid='".$uid."'";
    $res = mysqli_query($db, $sql);
    if ( mysqli_num_rows ( $res ) != 0) {
      return "「ユーザID:".$uid."」は既に登録されています。<br>\n";
    }

    return "";
  }
  
  function getYear()
  {
    global $db;
    $sql = "SELECT year FROM year";
    $res = mysqli_query($db, $sql);
    $fld = mysqli_fetch_object ( $res );
    $year = $fld->year;
    return $year;
  }


  // 登録処理
  $cnt = 0;
  $uids = explode("\n", $_POST['uid'] ?? '');
  $year = getYear();
  $errorMsg = ''; 
  foreach ($uids as $uid) {
    // 前後の空白を削除
    $uid = StringUtilities::fullspace2halfspace($uid);
    $uid = trim($uid);

    if ($uid == "") continue;

    // エラーチェック
    $errorMsg = check_uid($db, $uid, $year);
    if ($errorMsg != "") break;

    $cnt++;

    // UIDからカテゴリの取得
    $no = substr($uid, 14, 3); //枝番号
    if ($no == "000") {             // 構造
      $category_id = 1;
    } elseif ($no >= "001" AND $no <= "050") {  // 過程
      $category_id = 2;
    } elseif ($no >= "051") { // アウトカム
      $category_id = 3;
    }

    // パスワード生成
    $pass = simplepasswd();
//    if ($category_id == 3) {
//      $pass = simplepasswd();
//    } else {
//      $pass = random_str(PASS_SIZE_MIN, PASS_SIZE_MAX, $cnt);
//    }

    // 登録
    $sql = "INSERT INTO 
        usr (id, uid, pass, comp, del) 
        VALUES (".$category_id.", '".$uid."', '".$pass."', '".UNCOMPLETE."', '".ENABLE."')";
    $res = mysqli_query($db, $sql);

    // 年度を保持
    $tmp = explode("-", $uid);
//    $year = $tmp[0];

    $registedList[] = array($uid, $pass);
  }


//  if (isset($_POST['regist']) && $year != "") {
//    // 最後に登録された先頭2バイトを今年度とする
//    // 年度更新
//    $sql = "DELETE FROM year";
//    $res = mysqli_query($db, $sql);
//    $sql = "INSERT INTO year(year) VALUES('".$year."')";
//    $res = mysqli_query($db, $sql);
//  }

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>ユーザ登録</title>
</head>
<body>

<div align='center'>

  <h1>QIシステム</h1>

  <form method='POST' action='<?= $_SERVER['PHP_SELF'] ?>'>

    <table cellspacing='1' cellpadding='5'>
    <tr><th><a href='index.php'>メニュー</a> ≫ ユーザ登録</th></tr>
    <tr><td>
<?php if ($errorMsg != "") echo DispErrMsg($errorMsg);  ?>
    <p>
      ユーザIDを入力し登録ボタンをクリックして下さい。<br>
      ※ユーザIDは一度に複数登録できます。<br>
      ※<?= $year ?>年度以外の(<?= $year ?>で始まらない)ユーザIDは登録できません。<br>
      　登録する場合は先に年度変更を行ってください。<br>
    </p>
    <div><input type='submit' name='regist' value='　登録　'></div>
    <textarea name='uid' rows='20' cols='30'><?= isset($_POST['uid']) ? $_POST['uid'] : '' ?></textarea>
<?php
  if ($cnt > 0) {
    echo "<p>下記のユーザを登録しました。</p>\n";
    echo "<table cellspacing='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>パスワード</th></tr>\n";
    foreach ($registedList as $value) {
      echo "<tr><td>".$value[0]."</td><td>".$value[1]."</td></tr>\n";
    }
    echo "</table>\n";
  }


?>
    </td></tr>
    </table>

  </form>

</div>

</body>
</html>
