<?php
// 公開状況を取得する関数
function getPublicationStatus($db) {
    $stmt = mysqli_prepare($db, "SELECT pub FROM public");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $fld = mysqli_fetch_object($res);
    return $fld->pub;
}

// エラーメッセージを表示する関数
function displayErrorMessage($message) {
  echo "<div class='error'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
}

// 文字列を安全にサニタイズする関数
function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 最大IDとNoを取得する共通関数
function getMaxIdAndNo($db, $tableName, $columnPrefix, $params) {
  $whereClause = [];
  foreach ($params as $key => $value) {
      $whereClause[] = "$key = ?";
  }
  $whereClause = implode(" AND ", $whereClause);
  $sql = "SELECT MAX($columnPrefix) AS maxid, MAX(no) AS maxno FROM $tableName WHERE $whereClause";
  $stmt = mysqli_prepare($db, $sql);
  mysqli_stmt_bind_param($stmt, str_repeat("i", count($params)), ...array_values($params));
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $maxid = 0; $maxno = 0;
  if ($row = mysqli_fetch_assoc($res)) {
      $maxid = $row['maxid'] ?? 0;
      $maxno = $row['maxno'] ?? 0;
  }
  mysqli_stmt_close($stmt);
  return [$maxid, $maxno];
}

// IDリストを取得する共通関数
function getIdList($db, $tableName, $columnPrefix, $params) {
  $whereClause = [];
  foreach ($params as $key => $value) {
      $whereClause[] = "$key = ?";
  }
  $whereClause = implode(" AND ", $whereClause);
  $sql = "SELECT $columnPrefix FROM $tableName WHERE $whereClause ORDER BY no";
  $stmt = mysqli_prepare($db, $sql);
  mysqli_stmt_bind_param($stmt, str_repeat("i", count($params)), ...array_values($params));
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $ids = [];
  while ($row = mysqli_fetch_assoc($res)) {
      $ids[] = $row[$columnPrefix];
  }
  mysqli_stmt_close($stmt);
  return $ids;
}

// 項目の編集後のno整合性維持
function maintainOrderConsistency($db, $table, $ids) {
  $newno = 1;
  $query = "SELECT id, no FROM $table WHERE " . implode(' AND ', array_map(function($k) { return "$k = ?"; }, array_keys($ids))) . " ORDER BY no";
  $stmt = mysqli_prepare($db, $query);
  mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids)), ...array_values($ids));
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  while ($row = mysqli_fetch_assoc($result)) {
      $updateQuery = "UPDATE $table SET no = ? WHERE " . implode(' AND ', array_map(function($k) { return "$k = ?"; }, array_keys($ids))) . " AND id = ?";
      $updateStmt = mysqli_prepare($db, $updateQuery);
      $params = array_merge([$newno], array_values($ids), [$row['id']]);
      $types = 'i' . str_repeat('i', count($ids) + 1);
      mysqli_stmt_bind_param($updateStmt, $types, ...$params);
      mysqli_stmt_execute($updateStmt);
      mysqli_stmt_close($updateStmt);
      $newno++;
  }
  mysqli_stmt_close($stmt);
}




// 各項目称取得の関数化
function fetchItemDetails($db, $ids, $fields, $from, $joins, $order) {
  $query = "SELECT " . implode(', ', $fields) . " FROM $from ";
  foreach ($joins as $join) {
      $query .= $join['type'] . " JOIN " . $join['table'] . " ON " . $join['on'] . " ";
  }
  $query .= "WHERE " . implode(' AND ', array_map(function($k) { return "$k = ?"; }, array_keys($ids))) . " ORDER BY $order";

  $stmt = mysqli_prepare($db, $query);
  mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids)), ...array_values($ids));
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
  mysqli_stmt_close($stmt);
  return $data;
}

function fetchItems($db, $id, $id1) {
  // SQLクエリの準備
  $sql = "SELECT item2.id2, item2.name, item2.point, item2.no, item2.recommendation, item2.up_recommendation, category.category AS category
          FROM category
          LEFT JOIN item1 ON category.id = item1.id
          LEFT JOIN item2 ON item1.id = item2.id AND item1.id1 = item2.id1
          WHERE category.id = ? AND item1.id1 = ?
          ORDER BY item2.no";
  $stmt = mysqli_prepare($db, $sql);
  
  // パラメータのバインド
  mysqli_stmt_bind_param($stmt, "ii", $id, $id1);
  
  // クエリの実行
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  
  // 結果のフェッチ
  $items = [];
  while ($row = mysqli_fetch_assoc($result)) {
      $items[] = $row;
  }
  
  // ステートメントのクローズ
  mysqli_stmt_close($stmt);
  
  // 取得した項目のリストを返す
  return $items;
}


function addItem($db, $id, $id1, $newitem, $newpoint, $recommendation, $up_recommendation) {
  $newitem = StringUtilities::half2full($newitem);  // 全角に変換
  $newpoint = StringUtilities::str2int($newpoint);  // 文字列を整数に変換

  // 入力値のバリデーション
  if (empty($newitem)) {
      return "項目が未入力です。";
  }
  if (empty($newpoint)) {
      return "配点は0より大きな値をご入力ください。";
  }
  if (!is_numeric($recommendation) || !is_numeric($up_recommendation)) {
      return "基準点は0より大きな値をご入力ください。";
  }

  // 最大IDとNoの取得
  list($maxid, $maxno) = getMaxIdAndNo($db, 'item2', 'id2', ['id' => $id, 'id1' => $id1]);
  $maxid++;  // 新しいIDとして使用するためにインクリメント
  $maxno++;  // 新しいNoとして使用するためにインクリメント

  // データベースへの挿入
  $stmt = mysqli_prepare($db, "INSERT INTO item2 (id, id1, id2, name, point, no, recommendation, up_recommendation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  mysqli_stmt_bind_param($stmt, "iiisiiii", $id, $id1, $maxid, $newitem, $newpoint, $maxno, $recommendation, $up_recommendation);
  $execute_result = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  if ($execute_result) {
      return true;  // 成功した場合はtrueを返す
  } else {
      return "データベースへの挿入に失敗しました。";
  }
}


?>
