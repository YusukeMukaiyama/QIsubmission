<?php
/*******************************************************************
year.php
*******************************************************************/
  require_once("setup.php");

  $db = Connection::connect(); // データベース接続

  function update_year($db, $year)
  {
    // 正規表現で一発チェック
    /*
    if (!preg_match ("^([0-9]{2})$", $year)) {
      return "「年度:".$year."」の登録形式に誤りがあります。<br>\n";
    }*/
    if (!preg_match ("/^([0-9]{2})$/", $year)) {
      return "「年度:".$year."」の登録形式に誤りがあります。<br>\n";
    }
    

    // 年度更新
    $sql = "DELETE FROM year";
    $res = mysqli_query ( $db , $sql );
    $sql = "INSERT INTO year(year) VALUES('".$year."')";
    $res = mysqli_query ( $db , $sql );
    return "変更登録が完了しました";
  }
  function getYear()
  {
    global $db;
    $sql = "SELECT year FROM year";
    $res = mysqli_query ( $db , $sql );
    $fld = mysqli_fetch_object ( $res );
    $year = $fld->year;
    return $year;
  }
  
    // 公開 / 非公開取得
  $sql =  "SELECT pub FROM public";
  $res = mysqli_query ( $db , $sql );
  if (!mysqli_num_rows ( $res )) die ("データの取得に失敗しました");
  $fld = mysqli_fetch_object ( $res );
  $public = $fld->pub;
  
  
  $errorMsg = "";
  if ($public != Config::OPEN && isset($_POST['regist']) && $_POST['year'] != "") {
    $errorMsg = update_year($db,$_POST['year']);
  }
  $year = getYear();


  

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>年度変更</title>
</head>
<body>

<div align='center'>

  <h1>QIシステム</h1>

  <form method='POST' action="<?= $_SERVER['PHP_SELF'] ?>">

    <table cellspacing="1" cellpadding="5">
    <tr><th><a href="index.php">メニュー</a> ≫ 年度変更</th></tr>
    <tr><td>
<?php if ($errorMsg != "") echo ErrorHandling::DispErrMsg($errorMsg);  ?>
    <p>
      新しい年度を入力し登録ボタンをクリックして下さい。<br>
      ※年度を変更した場合、新しい年度以外のユーザを登録することはできません<br>
      ※この操作によって古いデータが削除されることはありません<br>
    </p>
    <?php
    if ($public == Config::OPEN) {
    ?>
    <div><input type="text" name="year" size="6" value="<?= $year ?>">年度<br>公開中のため設定変更できません。</div>
    <?php }else{ ?>
    <div><input type="text" name="year" size="6" value="<?= $year ?>">年度　<input type="submit" name="regist" value="　登録　"></div>
    <?php } ?>
    </td></tr>
    </table>

  </form>

</div>

</body>
</html>
