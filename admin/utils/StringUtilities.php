<?php

class StringUtilities {
    /**
     * 半角英数・記号を全角に変換します。必要な特殊文字の変換も行います。
     * @param string $str 変換対象文字列
     * @return string 変換された文字列
     */
    public static function half2full($str) {
        $str = trim(strip_tags($str)); // HTMLタグを除去し、前後の空白を削除
        $str = mb_convert_kana($str, "AKV"); // 半角英数・カナを全角に変換
        $str = str_replace("\\", "￥", $str);
        $str = str_replace("'", "’", $str);
        $str = str_replace("\"", "”", $str);
        return $str;
    }

    /**
     * 全角英数・記号を半角に変換します。
     * @param string $str 変換対象文字列
     * @return string 変換された文字列
     */
    public static function full2half($str) {
        $str = trim($str); // 前後の空白を削除
        return mb_convert_kana($str, "as"); // 全角英数を半角に変換
    }

    /**
     * 文字列内の全角数字を整数に変換します。
     * @param string $arg 数字を含む文字列
     * @return int 数値
     */
    public static function str2int($arg) {
        $arg = self::full2half($arg); // 全角数字を半角に変換
        return intval($arg); // 文字列を整数に変換
    }

    /**
     * 全角空白を半角空白に変換します。
     * @param string $arg 全角空白を含む文字列
     * @return string 半角空白に置き換えられた文字列
     */
    public static function fullspace2halfspace($arg) {
        return mb_convert_kana($arg, "s"); // 全角スペースを半角スペースに変換
    }

    /**
     * 全角、小数点を含む数字を可能な範囲で浮動小数点数に変換します。
     * @param string $arg 数字を含む文字列
     * @return float 数値
     */
    public static function str2decimal($arg) {
        $arg = self::full2half($arg); // 全角数字を半角に変換
        return floatval(preg_replace("/[^0-9\.]/", "", $arg)); // 非数値・非小数点文字を除去し、浮動小数点数に変換
    }
}

?>
