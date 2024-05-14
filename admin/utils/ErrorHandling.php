<?php

class ErrorHandling {
    /**
     * エラーメッセージの装飾
     * @param string $message エラーメッセージ文字列
     * @return string 装飾されたエラーメッセージ
     */
    public static function dispErrMsg($message) {
        return "<div style='color: #ff0000;'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
    }
}

?>