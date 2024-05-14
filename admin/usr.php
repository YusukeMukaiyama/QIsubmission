<?php
/**************************************************************************************************

	usr_reg.php

	ユーザ登録メンテナンス

																	(C)2005,University of Hyougo.
**************************************************************************************************/
require_once("setup.php");

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">

<link rel="stylesheet" href="/QIsystem/2new/admin_new/admin.css" media="all">
<title>ユーザ一覧</title>
</head>
<body>

<div align='center'>

	<h1>QIシステム</h1>

	<form method='POST' action='<?= $_SERVER['PHP_SELF'] ?>'>
	<table cellspacing='1' cellpadding='5'>
	<tr><th><a href='index.php'>メニュー</a> ≫ ユーザ一覧</th></tr>
	<tr><td>
<?php

	$db = Connection::connect();	// データベース接続

	// 有効 / 無効 更新
	if ( isset ( $_POST['update'] ) ) {
		for ($i = 0; isset($_POST['uid'.$i]); $i++) {
			$uid = $_POST['uid'.$i];
			$sql = "UPDATE usr SET del='".$_POST['enable'.$i]."',comp='".$_POST['comp'.$i]."' WHERE uid='".$uid."'";
			$res = mysqli_query($db, $sql);
			if (isset($_POST['ureg'.$i])) {
				$sql = "DELETE FROM usr WHERE uid='".$uid."'";
				$res = mysqli_query($db, $sql);
			}
		}

	}

	// 検索条件指定
	if ((!isset($_POST['search'])) && (!isset($_POST['update']))) {

		echo "<p>検索条件を指定してください。</p>\n".
			"<table cellspacing='1' cellpadding='5'>\n".
			// 修正案
			// $_POST['enumtype'] が定義されていない場合のデフォルト値を 1 とする
			$enumtype = isset($_POST['enumtype']) ? $_POST['enumtype'] : 1;

			// 修正後の使用例
			echo "<tr><th rowspan='4'><input type='radio' name='enumtype' value='1'" . ($enumtype != 2 ? ' checked' : '') . ">項目を選択して検索</th>\n";


		// 年度
		echo "<th>年度</th><td>\n";
		echo "<select name='year' onChange='javascript:submit();'>\n";
		echo "<option value='0'".( !$_POST['year'] ? " selected" : "").">選択して下さい</option>\n";
		$res = mysqli_query ( $db , "SELECT (LEFT(uid,2))AS itemdata FROM usr GROUP BY LEFT(uid,2) ORDER BY LEFT(uid,2) DESC" );
		while ( $fld =mysqli_fetch_object ( $res ) ) {
			echo "<option value='".$fld->itemdata."'".( $fld->itemdata == $_POST['year'] ? ' selected' : '').">".$fld->itemdata."</option>\n";
		}
		
		echo "</select>\n";
		echo "</td></tr>\n";

		// 都道府県
		echo "<tr><th>都道府県</th><td>\n";
		echo "<select name='pref' onChange='javascript:submit();'>\n";
		echo "<option value='0'".( !$_POST['pref'] ? " selected" : "").">選択して下さい</option>\n";
		$res = mysqli_query ( $db ,"SELECT DISTINCT SUBSTRING(uid FROM 4 FOR 2)AS itemdata FROM usr WHERE uid LIKE '".$_POST['year']."-%' ORDER BY itemdata ASC"  );

		while ( $fld =mysqli_fetch_object ( $res ) ) {
			echo "<option value='".$fld->itemdata."'".( $fld->itemdata == $_POST['pref'] ? ' selected' : '').">".Config::$prefName[ (int) $fld->itemdata ]."</option>\n";

		}
		
		echo "</select>\n";
		echo "</td></tr>\n";

		// 病院NO.
		echo "<tr><th>病院No.</th><td>\n";
		echo "<select name='hosp' onChange='javascript:submit();'>\n";
		echo "<option value='0'".( !$_POST['hosp'] ? " selected" : "").">選択して下さい</option>\n";
		$res = mysqli_query ( $db  , "SELECT DISTINCT SUBSTRING(uid FROM 7 FOR 4) AS itemdata FROM usr WHERE uid LIKE '".$_POST['year']."-".$_POST['pref']."-%' ORDER BY itemdata ASC" );
		while ( $fld =mysqli_fetch_object ( $res ) ) {
			echo "<option value='".$fld->itemdata."'".( $fld->itemdata == $_POST['hosp'] ? ' selected' : '').">".$fld->itemdata."</option>\n";
		}
		
		echo "</select>\n";
		echo "</td></tr>\n";

		// 病棟NO.
		echo "<tr><th>病棟No.</th><td>\n";
		echo "<select name='ward' onChange='javascript:submit();'>\n";
		echo "<option value='0'".( !$_POST['ward'] ? " selected" : "").">選択して下さい</option>\n";
		$res = mysqli_query ( $db  , "SELECT DISTINCT SUBSTRING(uid FROM 12 FOR 2)AS itemdata FROM usr WHERE uid LIKE '".$_POST['year']."-".$_POST['pref']."-".$_POST['hosp']."-%' ORDER BY itemdata ASC" );
		while ( $fld =mysqli_fetch_object ( $res ) ) {
			echo "<option value='".$fld->itemdata."'".( $fld->itemdata == $_POST['ward'] ? ' selected' : '').">".$fld->itemdata."</option>\n";
		}
		
		echo "</select>\n";
		echo "</td></tr>\n";

		// $_POST['UserID'] が定義されていない場合のデフォルト値を空文字列とする
		$UserID = isset($_POST['UserID']) ? $_POST['UserID'] : '';

		// 修正後の使用例
		echo "<tr><th><input type='radio' name='enumtype' value='2'".( $enumtype == 2 ? ' checked' : '').">IDを指定して検索</th><th>ID</th>\n".
			"<td><input size='25' type='text' maxlength='17' name='UserID' value='".$UserID."' onKeyDown='if (window.event.keyCode == 13) { search.click(); return false; }'></td></tr>\n".
			"<tr><td colspan=3><div align='right' style='margin:5px;'><input type='submit' name='search' value='　検　索　'></div></td></tr>\n".
			"</table>\n";


	// 検索条件に該当するユーザを表示
	} else {


		// 検索条件
		$enumtype = isset($_POST['enumtype']) ? $_POST['enumtype'] : '';
		$UserID = isset($_POST['UserID']) ? $_POST['UserID'] : '';

		if ($enumtype == 1) {	// 項目を選択して検索
			$sql = "";
			if (isset($_POST['year'])) $sql = $_POST['year']."-";
			if (isset($_POST['pref'])) $sql .= $_POST['pref']."-";
			if (isset($_POST['hosp'])) $sql .= $_POST['hosp']."-";
			if (isset($_POST['ward'])) $sql .= $_POST['ward']."-";
		} else {	// IDを指定して検索
			$sql = $UserID;
		}

		if ((!isset($_POST['update'])) && (!$sql)) {	// 検索条件が指定されていない
			echo DispErrMsg("検索条件が指定されていません");
		} else {	// 検索条件が指定されている
			$sql = "SELECT uid,pass,comp,del,lastupdate FROM usr WHERE uid LIKE '".$sql."%' ORDER BY uid";
			$res = mysqli_query ( $db ,$sql );
			if ( !mysqli_num_rows ( $res ) ) {
				echo "検索条件に該当するユーザは登録されていません";
			} else {

				echo "<p>検索結果　　<input type='submit' name='back' value='　≪戻る　'></p>\n";
				echo "<table cellspacing='1' cellpadding='5'>\n";
				echo "<tr><th>ユーザID</th><th>パスワード</th><th>回答状況</th><th>ステータス</th><th>削除</th></tr>\n";

				$uid = 0;
				while ( $fld =mysqli_fetch_object ( $res ) ) {

					// 回答値修正画面へのリンク
					echo "<tr><td><a href ='./list.php?uid=".$fld->uid."' target='_blank'>".$fld->uid."</a>".
						"<input type='hidden' name='uid".$uid."' value='".$fld->uid."'></td><td>".$fld->pass."</td>";

					// 完了ステータス（回答完了日付が登録されている・完了ステータスとなっている場合）
					if ( $fld->lastupdate || $fld->comp == Config::COMPLETE ) {
						echo "<td><input type='radio' name='comp".$uid."' value='".Config::COMPLETE."'". ( $fld->comp == Config::COMPLETE   ? " checked" : "" )."> 完了　".
								"<input type='radio' name='comp".$uid."' value='".Config::UNCOMPLETE."'".( $fld->comp == Config::UNCOMPLETE ? " checked" : "" )."> 未完了</td>";
					} else {
						echo "<td>未完了</td>";
					}

					// 有効ステータス
					echo "<td><input type='radio' name='enable".$uid."' value='".Config::ENABLE."'".(($fld->del != Config::DISABLE) ? ' checked' : '')."> 有効　".
							"<input type='radio' name='enable".$uid."' value='".Config::DISABLE."'".(($fld->del == Config::DISABLE) ? ' checked' : '')."> 無効</td>";

					// 削除ステータス
					echo "<td><input type='checkbox' name='ureg".$uid."'>削除</td></tr>\n";

					$uid++;

				}

				echo "</table>\n";
				echo "<div align='right' style='margin:5px;'><input type='reset' name='reset' value='リセット'>　　".
						"<input type='submit' name='update' value='　更　新　'></div>\n";

			}
		}

	}


?>
	</td></tr>
	</table>
<?php
	if (isset($_POST['search'])) {
		echo "<input type='hidden' name='year' value='".$_POST['year']."'>\n";
		echo "<input type='hidden' name='pref' value='".$_POST['pref']."'>\n";
		echo "<input type='hidden' name='pref' value='".$_POST['pref']."'>\n";
		echo "<input type='hidden' name='ward' value='".$_POST['ward']."'>\n";
	}
?>

	</form>

	</div>

</body>
</html>
