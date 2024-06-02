<!DOCTYPE html>
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=euc-jp">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>質問前後のアンケート設定</title>
<script language='javascript'>
<!--
function item_select(item)
{
	document.maintenance_enqete.id.value = item;
	document.maintenance_enqete.submit();
}
-->
</script>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ 質問前後のアンケート設定</th></tr>
	<tr><td>

	<form name='maintenance_enqete' method='POST' action='<?= $_SERVER['PHP_SELF'] ?>'>
<?php
/*******************************************************************
enquete.php
	質問前後のアンケート設定
								(C)2005-2006,University of Hyougo.
*******************************************************************/

require_once("setup.php");
require_once("/Users/mukaiyamayusuke/Desktop/Sites/QIsystem/kango3/public_html/lib/lib.php"); // lib.phpを直接インクルード

$db = Connection::connect(); // データベース接続

// $_REQUESTから直接変数を設定する場合には、事前にissetまたは??演算子を使用して、未定義のキーに対するデフォルト値を設定します。
// $_GETまたは$_POSTから'id'キーの存在を確認し、デフォルト値を設定
$id = $_REQUEST['id'] ?? '1'; // これを保持する
// ここで$idのチェックをより厳格に行う
if (!isset($_REQUEST['id']) || $_REQUEST['id'] === '') {
    // IDが未指定の場合の処理。例えばエラーメッセージを設定するなど
    echo "IDが未指定です。"; // 実際にはこれをエラーログに記録したり、ユーザーに適切なフィードバックを提供するための処理が必要です。
    $id = '1'; // デフォルト値として'1'を設定する。必要に応じてこの値を変更してください。
} else {
    $id = $_REQUEST['id'];
}
//$id = $_REQUEST['id'] ?? '1'; // '1' をデフォルト値として設定
$dat = $_REQUEST['dat'] ?? null;
$enq = $_REQUEST['enq'] ?? ''; // 最初の設定でデフォルト値を与えます。
$unit = $_REQUEST['unit'] ?? '';
$csv = $_REQUEST['csv'] ?? '0';
$ERR = ''; // ERRはこの時点で初期化

// $_REQUEST['uid']が設定されていない場合、空文字列をデフォルト値として使用
$uid = $_REQUEST['uid'] ?? '';

// explode()関数に渡す前に、$uidが空でないことを確認
$uids = $uid !== '' ? explode("\n", $uid) : [];

// 変数$typeが使用されていますが、初期化されていません。
// $typeのデフォルト値を設定する必要があります。
$type = ''; // 適切なデフォルト値に設定してください。

// 編集画面の表示前にget_enquete関数を呼び出して、変数の値を設定
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

// 現在登録されているアンケートの列挙
// 引数　$id(構造:STRUCTURE:1 / 過程:PROCESS:2 / アウトカム:OUTCOME:3)
// 戻値　なし
function enum_enquete($id)
{
	global $db;
	// $idがnullまたは空でないことを確認
    if ($id === null || $id === '') {
        echo "IDが未指定です。";
        return; // 早期リターン
    }
	//$sql = "SELECT * FROM enquete WHERE id=".$id." ORDER BY id1";
	if(is_numeric($id)) {
		$sql = "SELECT * FROM enquete WHERE id=".$id." ORDER BY id1";
	} else {
		// $idが数値でない場合のエラー処理
	}
	$rs = mysqli_query ( $db ,$sql );
	echo "<table cellspacing='1' cellpadding='5'>\n";
	echo "<tr><th>選択　<input type='submit' name='itemreset' value='解除'></th><th>アンケート</th><th>回答方法</th><th>単位</th><th>CSVデータ統合</th></tr>\n";
	if ( !mysqli_num_rows ( $rs ) ) {
		echo "<tr><td colspan=5>現在登録されているアンケートはありません</td></tr>\n";
	} else {
		while ( $fld = mysqli_fetch_object ( $rs ) ) {
			echo "<tr>";
			// 選択
			$checkedAttribute = isset($_REQUEST['dat']) && $_REQUEST['dat'] == $fld->id1 && !isset($_REQUEST['itemreset']) && !isset($_REQUEST['edit']) ? " checked" : "";
			echo "<td><input type='radio' name='dat' value='".$fld->id1."' onclick='item_select(".$id.")'".$checkedAttribute.">".$fld->id1."</td>";
				//(($_REQUEST['dat'] == $fld->id1) && !isset($_REQUEST['itemreset']) && (!isset($_REQUEST['edit'])) ? " checked" : "").">".$fld->id1."</td>";
				// $_REQUEST['dat']が定義されていない場合に、デフォルト値としてnullを使用する
				$dat = $_REQUEST['dat'] ?? null;

				// 修正された$dat変数を使用して比較
				(($dat == $fld->id1) && !isset($_REQUEST['itemreset']) && (!isset($_REQUEST['edit'])) ? " checked" : "").">".$fld->id1."</td>";

			// アンケート
			echo "<td>";
			if ( mb_ereg ( "<script", $fld->enq ) ) {
				echo "<span style='font-size:9pt;color:#bbb;'>※このアンケートには計算式が含まれています。</span>\n";
			}
			echo "<a href='./enq_preview.php?id=".$id."&id1=".$fld->id1."' target='_blank'>".nl2br ( $fld->enq )."</a></td>";
			//echo "$fld->enq";

			// 回答方法
			// TEXT:テキスト:2 / CHECK:チェックボックス:3 / RADIO:ラジオボタン:4 / TEXTAREA:テキストエリア:5)
			echo "<td>";
			if ($fld->type == TEXT) {
				echo "値";
			} elseif ($fld->type == TEXTAREA) {
				echo "文章";
			} elseif ($fld->type == CHECK) {
				echo "<a href='./enquete_ans.php?id=".$id."&id1=".$fld->id1."'>複数選択</a>";
			} elseif ($fld->type == RADIO) {
				echo "<a href='./enquete_ans.php?id=".$id."&id1=".$fld->id1."'>単一選択</a>";
			}
			echo "</td>";
			// 単位
			echo "<td>".(($fld->unit) ? $fld->unit : "-")."</td>";
			// 統合
			echo "<td>".(($fld->csv == 1) ? "統合" : "-")."</td>";
			echo "</tr>\n";
		}
	}
	
	echo "</table>\n";

}
/*古い関数コード
// アンケートの追加
function add_enquete()
{
	global $db;
	$id = $_REQUEST['id'];
	$sql = "SELECT id1 FROM enquete WHERE id=".$id." ORDER BY id1 DESC LIMIT 1";
	$rs = mysqli_query ( $db ,$sql );
	if ( mysqli_num_rows ( $rs ) ) {
		$fld = mysqli_fetch_object ( $rs );
		$id1 = ($fld->id1) + 1;
	} else {
		$id1 = 1;
	}
	$sql = "INSERT INTO enquete(id,id1,enq,type,unit,csv) VALUES(".$id.",".$id1.",'".$_REQUEST['enq']."','".$_REQUEST['type']."','".$_REQUEST['unit']."','".(isset($_REQUEST['csv']) ? "1" : "2")."')";
	$rs = mysqli_query ( $db ,$sql );
}
*/
// アンケートの追加
function add_enquete()
{
    global $db;
    $id = $_REQUEST['id'];

    // SQLインジェクションを防ぐためのエスケープ処理
    $enq = mysqli_real_escape_string($db, $_REQUEST['enq']);
    $type = mysqli_real_escape_string($db, $_REQUEST['type']);
    $unit = mysqli_real_escape_string($db, $_REQUEST['unit']);
    $csv = isset($_REQUEST['csv']) ? "1" : "2"; // これは数値なのでエスケープ不要

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
		// アンケート回答選択肢削除
		$sql = "DELETE FROM enq_ans WHERE id=".$id." AND id1=".$id1;
		$rs = mysqli_query ( $db ,$sql );
	}
}

// アンケートの削除
function delete_enquete($id,$id1)
{
	global $db;
	// アンケート削除
	$sql = "DELETE FROM enquete WHERE id=".$id." AND id1=".$id1;
	$rs = mysqli_query ( $db ,$sql );
	// アンケート回答選択肢削除
	$sql = "DELETE FROM enq_ans WHERE id=".$id." AND id1=".$id1;
	$rs = mysqli_query ( $db ,$sql );
}

// 選択されたデータの各値を取得
function get_enquete($id,$id1,&$enq,&$type,&$unit,&$csv)
{
	global $db;
	$sql = "SELECT enq,type,unit,csv FROM enquete WHERE id=".$id." AND id1=".$id1;
	//var_dump($db); // mysqli_query を呼び出す直前に $db をチェック
	$rs = mysqli_query ( $db ,$sql );
	$fld = mysqli_fetch_object ( $rs );
	$enq = $fld->enq;	$type = $fld->type;	$unit = $fld->unit;	$csv = $fld->csv;
}


/*** 動作 ***/
	//$db = Connection::connect(); // データベース接続
	//var_dump($db); // データベース接続の結果を確認

	// アイテムを選択した
	if (isset($_REQUEST['delete'])) {	// 削除
		if (empty($dat)) {
			$ERR = "削除するアイテムが選択されていません";
		} else {
			delete_enquete($_REQUEST['id'],$_REQUEST['dat']);
		}

	} elseif (isset($_REQUEST['edit'])) {	// 編集
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
	} elseif (!empty($dat)) {	// 選択された場合
		get_enquete($_REQUEST['id'],$_REQUEST['dat'],$enq,$type,$unit,$csv);
	}

/*** インターフェイス表示 ***/

	// 編集画面
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

	// $_REQUEST['dat']の存在を確認してから使用する
	

	// 同じく$_REQUEST['dat']の存在を確認してから使用する
	$editButtonLabel = $datExists && !isset($_REQUEST['itemreset']) && !isset($_REQUEST['delete']) && !isset($_REQUEST['edit']) ? "　編　集　" : "　登　録　";
	echo "<input name='edit' type='submit' value='".$editButtonLabel."'></div></td></tr>\n";

	echo "</table>\n";
	
	echo "<font color='red'>" . htmlspecialchars($ERR) . "</font><br>\n";

/*** 一覧 ***/
	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '10'; // '10' はデフォルト値
	// 既に$idにはデフォルト値 '10' またはリクエストからの値が設定されている
	echo "<p>".$category[$id]."</p>\n"; // 修正


	enum_enquete($id);



?>

	</form>

	</td></tr>
	</table>


	</div>

</body>
</html>
