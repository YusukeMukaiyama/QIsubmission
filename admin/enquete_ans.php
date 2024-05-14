<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>質問前後のアンケート回答設定</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<form name='maintenance_enqete_ans' method='POST' action='<?= $_SERVER['PHP_SELF'] ?>'>

		<table cellspacing='1' cellpadding='5'>
		<tr><th><a href='index.php'>メニュー</a> ≫ <a href='enquete.php?id=<?= $_REQUEST['id']?>'>質問前後のアンケート設定</a> ≫ 質問前後のアンケート回答設定</th></tr>
		<tr><td>
<?php
/*******************************************************************
enq_ans.php
	質問前後のアンケート回答設定
								(C)2005-2006,University of Hyougo.
*******************************************************************/
require_once("setup.php");

$category = array (1=>"STRUCTURE",2=>"PROCESS",3=>"OUTCOME");
$category_jpn = array (1=>"構造",2=>"過程",3=>"アウトカム");

// 初期化
$ERR = "";
$ans = "";

// スクリプトの先頭で$datを初期化し、さらにアクセスする前に確認
$dat = isset($_POST['dat']) ? $_POST['dat'] : null;

// 以下の関数呼び出しや条件分岐の中で、$datを使用する前にnullチェックを追加
if ($dat !== null) {
    // $datを使った処理
}



// 現在登録されているアンケートの列挙
// 引数　$id(構造:STRUCTURE:1 / 過程:PROCESS:2 / アウトカム:OUTCOME:3)
// 戻値　なし
function enum_enq_ans($id, $id1) {
    global $db, $category, $dat; // ここで$datを追加

    $sql = "SELECT id2, ans FROM enq_ans WHERE id=" . $id . " AND id1='" . $id1 . "' ORDER BY id2";
    $rs = mysqli_query($db, $sql);

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
/*
function enum_enq_ans($id,$id1)
{
	global $db,$category;
	$sql = "SELECT id2,ans FROM enq_ans WHERE id=".$id." AND id1='".$id1."' ORDER BY id2";
	$rs = mysqli_query ( $db , $sql );

	echo "<table cellspacing='1' cellpadding='5'>\n";
	echo "<tr><th>選択　<input type='submit' name='itemreset' value='解除'></th><th>回答</th></tr>\n";
	if ( !mysqli_num_rows ( $rs ) ) {
		echo "<tr><td colspan=2>現在登録されている回答はありません</td></tr>\n";
	} else {
		while ($fld = mysqli_fetch_object ( $rs ) ) {
			echo "<tr><td><input type='radio' name='dat' value='".$fld->id2."' onclick='document.maintenance_enqete_ans.submit();'" .
				($dat === $fld->id2 ? " checked" : "") . ">" . $fld->id2 . "</td><td>" . nl2br($fld->ans) . "</td></tr>\n";
		}
	}
	echo "</table>\n";
	echo "<br>\n";

}
*/

// 回答の追加
function add_enq_ans()
{
	global $db;
	$id = $_POST['id'];
	$id1 = $_POST['id1'];
	$sql = "SELECT id2 FROM enq_ans WHERE id=".$id." AND id1='".$id1."' ORDER BY id2 DESC LIMIT 1";
	$rs = mysqli_query ( $db , $sql );

	if ( mysqli_num_rows ( $rs ) ) {
		$fld = mysqli_fetch_object ( $rs );
		$id2 = ($fld->id2) + 1;
	} else {
		$id2 = 1;
	}
	$sql = "INSERT INTO enq_ans(id,id1,id2,ans) VALUES(".$id.",".$id1.",".$id2.",'".$_POST['ans']."')";
	$rs = mysqli_query ( $db , $sql );
}

// 回答の編集
function edit_enq_ans($id,$id1,$id2)
{
	global $db;
	$sql = "UPDATE enq_ans SET ans = '".$_POST['ans']."' WHERE id=".$id." AND id1=".$id1." AND id2=".$id2;
	$rs = mysqli_query ( $db , $sql );
}

// 回答の削除
function delete_enq_ans($id,$id1,$id2)
{
	global $db;
	// アンケート回答選択肢削除
	$sql = "DELETE FROM enq_ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2;
	$rs = mysqli_query ( $db , $sql );
}

// 選択されたデータを取得
function get_enq_ans($id,$id1,$id2,&$ans)
{
	global $db;
	$sql = "SELECT ans FROM enq_ans WHERE id=".$id." AND id1=".$id1." AND id2=".$id2;
	$rs = mysqli_query ( $db , $sql );
	$fld = mysqli_fetch_object ( $rs );
	$ans = $fld->ans;
}


/*** 動作 ***/
	$db = Connection::connect();	// データベース接続

	$id = $_REQUEST['id'];
	$id1 = $_REQUEST['id1'];

	// アイテムを選択した
	// アイテムを選択した
	if (isset($_POST['delete'])) {    // 削除
		if (!isset($_POST['dat']) || !$_POST['dat']) {
			$ERR = "削除するアイテムが選択されていません";
		} else {
			delete_enq_ans($id, $id1, $_POST['dat']);
		}

	} elseif (isset($_POST['edit'])) {    // 編集
		if (!$_POST['ans']) { // 回答が入力されていない場合
			$ERR = "回答が入力されていません";
		} else {
			if (!isset($_POST['dat']) || !$_POST['dat']) {
				// $_POST['dat']が設定されていない場合、新規登録と見なす
				add_enq_ans();
			} else {
				// $_POST['dat']が設定されている場合、編集と見なす
				edit_enq_ans($id, $id1, $_POST['dat']);
			}
		}
	} elseif ($dat) {    // $datがnullでない場合
		get_enq_ans($id, $id1, $dat, $ans); // こちらは変更なし
	}



/*** インターフェイス表示 ***/

	// 編集画面
	echo "<table cellspacing='1' cellpadding='5'>\n";
	echo "<tr><th>回答</th><td><textarea name='ans' cols='80' rows='7'>".$ans."</textarea></td></tr>\n";
	echo "<tr><td colspan='2'><div align='right' style='margin:5px;'><input type='reset' value='リセット'>　";
	// 142,143行目を修正
	$datCheck = $dat && !isset($_POST['itemreset']) && !isset($_POST['delete']) && !isset($_POST['edit']);
	echo $datCheck ? "<input name='delete' type='submit' value='　削　除　'>　" : "";
	echo "<input name='edit' type='submit' value='" . ($datCheck ? "　編　集　" : "　登　録　") . "'></div></td></tr>\n";
	echo "</table>\n";
	echo "<p><span style='color:red;'>".$ERR."</span></p>\n";

/*** 一覧 ***/

	enum_enq_ans($_REQUEST['id'],$_REQUEST['id1']);



	echo "<input type='hidden' name='id' value='".$_REQUEST['id']."'>";
	echo "<input type='hidden' name='id1' value='".$_REQUEST['id1']."'>";

?>
		</td></tr>
		</table>

	</form>

</div>

</body>
</html>
