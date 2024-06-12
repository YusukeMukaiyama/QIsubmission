<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="./admin.css" media="all">
<title>アウトカムCSVインポート</title>
</head>
<body>

<div align='center'>

  <h1>QIシステム</h1>

  <table cellspacing='1' cellpadding='5'>
  <tr><th><a href='index.php'>メニュー</a> ≫ アウトカムCSVインポート</th></tr>
  <tr><td>
<?php
require_once("setup.php");

/*-------------------------------------------------------------------------------------------------
buffer_flush
  概要：バッファの強制フラッシュ
  引数：なし
  戻値：なし
-------------------------------------------------------------------------------------------------*/
function buffer_flush() {
    flush();
    ob_flush();
}

/*-------------------------------------------------------------------------------------------------
simplepasswd
  概要：ランダムな数字6桁のパスワードを生成する
  引数：なし
  戻値：生成された数字列を返す
-------------------------------------------------------------------------------------------------*/
function simplepasswd() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/*-------------------------------------------------------------------------------------------------
update_point
  概要：アウトカムデータのポイントを更新する
  引数：year - 年度, id1, id2, id3, id4 - 質問IDの各部分, db - データベース接続
  戻値：なし
-------------------------------------------------------------------------------------------------*/
function update_point($year, $id1, $id2, $id3, $id4, $db) {
  // SQLクエリの準備と実行
  $stmt = $db->prepare("SELECT ans_id, point FROM ans WHERE id=3 AND id1=? AND id2=? AND id3=? AND id4=? ORDER BY ans_id");
  $stmt->bind_param("iiii", $id1, $id2, $id3, $id4);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($fld = $result->fetch_object()) {
      // アップデート用SQLの準備と実行
      $update_stmt = $db->prepare("UPDATE usr_ans SET point=? WHERE id=3 AND id1=? AND id2=? AND id3=? AND id4=? AND ans=? AND uid LIKE ?");
      $year_like = $year . '%';
      $update_stmt->bind_param("iiiiiis", $fld->point, $id1, $id2, $id3, $id4, $fld->ans_id, $year_like);
      $update_stmt->execute();
  }
  $stmt->close();
}

/*-------------------------------------------------------------------------------------------------
import_csv
  概要：アウトカムデータの一括インポート
  引数：なし
  戻値：なし
-------------------------------------------------------------------------------------------------*/
function import_csv($db) {
  $hFile = fopen($_FILES['userfile']['tmp_name'], "r");

  // ヘッダラインを処理
  $header = fgetcsv($hFile, 1024, ",");
  
  $header_name = array('ID','NO','ID1','ID2','ID3','ID4','性別','年齢','入院期間');
  foreach($header_name as $key => $val){
      $header[$key] = mb_convert_encoding($header[$key], 'EUC-JP', 'SJIS');
      if ($header[$key] != $val) die("項目名（1行目）が違います。".($key+1)."項目名は".$header_name[$key]."で無ければなりません");
  }

  // 各種変数の初期化
  $header_ids = array();
  $header_id = array();
  $header_id_val = array();
  $i = 0;
  foreach($header as $key => $val) {
      $i++;
      if($i <= 9){
          continue;
      }
      if (strlen($header[$key]) != 4 || !is_numeric($header[$key])) die("項目名（1行目）が違います。".$i."項目名(".$header[$key].")は4桁数字で無ければなりません");
      
      $sql = "SELECT no, ans_id FROM ans WHERE id=3 AND id1=".$val[0]." AND id2=".$val[1]." AND id3=".$val[2]." AND id4=".$val[3]." ORDER BY ans_id";
      $rs = mysqli_query($db, $sql);
      $j = 0;
      $wk_vals = array();
      while ($fld = mysqli_fetch_object($rs)) {
          $wk_vals[$fld->no] = $fld->ans_id;
          $j++;
      }
      if ($j == 0) die("項目名（1行目）が違います。".$i."項目名(".$header[$key].")は質問のIDを繋げたもので無ければなりません");
      $header_ids[] = $header[$key];
      $header_id[$header[$key]] = array($header[$key][0], $header[$key][1], $header[$key][2], $header[$key][3]);
      $wk_vals[0] = 0;
      $header_id_val[$header[$key]] = $wk_vals;
  }


// 0  ID
// 1  NO

// 2  ID1
// 3  ID2
// 4  ID3
// 5  ID4

    $cnt = 0;
    while (($data = fgetcsv($hFile, 1024, ",")) !== FALSE) {
        $year = $data[2];
        if (count($header) != count($data)) die("データインポートエラー ".$cnt."行目 LINE:".__LINE__."(項目数)");

        // アウトカムUIDの生成
        $uid = sprintf("%02d-%02d-%04d-%02d", $data[2], $data[3], $data[4], $data[5]);
        $stmt = $db->prepare("SELECT uid FROM usr WHERE id = '3' AND uid > ? AND uid LIKE ? ORDER BY uid DESC LIMIT 1");
        $like_uid = $uid . '-050%';
        $stmt->bind_param("ss", $uid, $like_uid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows) {
            $fld = $result->fetch_object();
            $buf = explode("-", $fld->uid);
            $oc_number = (int)$buf[4] + 1;
        } else {
            $oc_number = 51;
        }
        $stmt->close();
        $uid .= "-".sprintf("%03d", $oc_number);

        // 回答しないを補完
        for ($i = 9; $i < 22; $i++) { // $data[9]〜$data[21]を検証
            if (!$data[$i]) $data[$i] = 0;
        }

        // usrテーブルにデータをインポート
        $stmt = $db->prepare("INSERT INTO usr(id, uid, pass, comp, del, lastupdate) VALUES(3, ?, ?, '1', '1', ?)");
        $passwd = simplepasswd();
        $date = date('Y-m-d');
        $stmt->bind_param("sss", $uid, $passwd, $date);
        $stmt->execute();
        $stmt->close();

        // enq_usr_ansテーブルにデータをインポート
        $enq_stmt = $db->prepare("INSERT INTO enq_usr_ans(id, id1, uid, ans) VALUES(30, ?, ?, ?)");
        foreach (array(1 => $data[7], 2 => $data[6], 3 => $data[8]) as $enq_id1 => $enq_ans) {
            $enq_stmt->bind_param("iss", $enq_id1, $uid, $enq_ans);
            $enq_stmt->execute();
        }
        $enq_stmt->close();

        // usr_ansテーブルにデータをインポート
        $usr_stmt = $db->prepare("INSERT INTO usr_ans(id, uid, id1, id2, id3, id4, ans, point) VALUES(3, ?, ?, ?, ?, ?, ?, 0)");
        $i = 9;
        foreach ($header_ids as $key => $val) {
            $id1 = $val[0];
            $id2 = $val[1];
            $id3 = $val[2];
            $id4 = $val[3];
            $ans = isset($header_id_val[$val][$data[$i]]) ? $header_id_val[$val][$data[$i]] : '';
            $usr_stmt->bind_param("siiiii", $uid, $id1, $id2, $id3, $id4, $ans);
            $usr_stmt->execute();
            $i++;
        }
        $usr_stmt->close();


        /*
    // 9  1111
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',1,1,1,1,".$data[9].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //10  1121
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',1,1,2,1,".$data[10].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //11  2111
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',2,1,1,1,".$data[11].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //12  2131
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',2,1,3,1,".$data[12].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //13  3111
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',3,1,1,1,".$data[13].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //14  3131
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',3,1,3,1,".$data[14].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //15  4111
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',4,1,1,1,".$data[15].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //16  4121
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',4,1,2,1,".$data[16].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //17  4131
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',4,1,3,1,".$data[17].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //18  5111
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',5,1,1,1,".$data[18].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //19  5121
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',5,1,2,1,".$data[19].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //20  6111
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',6,1,1,1,".$data[20].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );

    //21  6121
    $sql = "INSERT INTO usr_ans(id,uid,id1,id2,id3,id4,ans,point) VALUES(3,'".$uid."',6,1,2,1,".$data[21].",0)";
    mysqli_unbuffered_query ( $sql ,$db ) or die ( "データインポートエラー LINE:".__LINE__."(".$sql.")" );
    */

        $cnt++;

        echo $uid."データをインポートしました。<br>";
        buffer_flush();
    }

    fclose($hFile);

    // usr_ans.pointの一括変更
    foreach ($header_id as $key => $val) {
        update_point($year, $val[0], $val[1], $val[2], $val[3], $db);
    }
    /*
    // 9  1111
    update_point ( $year, 1 ,1 ,1 ,1 );
    //10  1121
    update_point ( $year, 1 ,1 ,2 ,1 );
    //11  2111
    update_point ( $year, 2 ,1 ,1 ,1 );
    //12  2131
    update_point ( $year, 2 ,1 ,3 ,1 );
    //13  3111
    update_point ( $year, 3 ,1 ,1 ,1 );
    //14  3131
    update_point ( $year, 3 ,1 ,3 ,1 );
    //15  4111
    update_point ( $year, 4 ,1 ,1 ,1 );
    //16  4121
    update_point ( $year, 4 ,1 ,2 ,1 );
    //17  4131
    update_point ( $year, 4 ,1 ,3 ,1 );
    //18  5111
    update_point ( $year, 5 ,1 ,1 ,1 );
    //19  5121
    update_point ( $year, 5 ,1 ,2 ,1 );
    //20  6111
    update_point ( $year, 6 ,1 ,1 ,1 );
    //21  6121
    update_point ( $year, 6 ,1 ,2 ,1 );
    */
//  }

    echo $cnt."件のデータを読み込みました。";
    }
    $db = Connection::connect(); // データベース接続

    if (isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
        echo "処理が完了するまで、そのままお待ちください。<br>";
        echo "※）ブラウザのリロードを行うとデータが重複登録されますので決して行わないでください。<br>";
    
        echo $_FILES['userfile']['name']."がアップロードされました。<br>";
        buffer_flush();
    
        echo "インポート処理を行っています。<br>";
        buffer_flush();
    
        import_csv($db);
    
    } else {
    ?>
        インポートできるデータの形式は以下の通りです（1行目のタイトル行も必要です）。<br>
        <p style='margin : 2px;padding : 5px;background : #dddddd;'>
          ex)<br>
          ID,NO,ID1,ID2,ID3,ID4,性別,年齢,入院期間,1111,1121,2111,2131,3111,3131,4111,4121,4131,5111,5121,6111,6121<br>
          020101,00001,06,02,0001,01,2,79,34,3,3,3,3,3,3,3,3,3,,2,3,3<br>
          020101,00002,06,02,0001,01,2,49,8,3,3,3,3,2,2,3,2,2,3,3,2,3<br>
          020101,00003,06,02,0001,01,1,24,22,1,3,3,3,2,3,3,2,2,3,2,2,3<br>
          020101,00004,06,02,0001,01,1,72,33,3,3,3,3,3,3,3,3,3,3,3,3,3<br>
             :
        </p>
        <form method='POST' action='./outcome_csv_import.php' enctype='multipart/form-data'>
          <input type='hidden' name='MAX_FILE_SIZE' value='10485760'>
          <input type='file' name='userfile'>　<input type='submit' value='アップロード'>
        </form>
    <?php
    }
    ?>
      </td></tr>
      </table>
    
    </div>
    
    </body>
    </html>