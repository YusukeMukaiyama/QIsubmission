<?php

require_once __DIR__ . '/../config/Config.php';

class UserClassification {
    /**
     * ユーザIDから分類を返します。
     * 
     * @param string $userId ユーザID
     * @return string|null 分類（構造 / 過程 / アウトカム）、またはnull
     */
    public static function getUserType($userId) {
        if (is_null($userId) || $userId === '') {
            return null;
        }
        
        $parts = explode("-", $userId);
        if (!isset($parts[4])) {
            return null;
        }
        
        $type = (int)$parts[4];
        if ($type === 0) {
            return Config::STRUCTURE;
        } elseif ($type >= 1 && $type <= 50) {
            return Config::PROCESS;
        } else {
            return Config::OUTCOME;
        }
    }
}
?>