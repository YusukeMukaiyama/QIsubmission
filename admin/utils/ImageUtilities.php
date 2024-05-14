<?php

require_once __DIR__ . '/../config/Config.php';

class ImageUtilities {
    /**
     * 画像ファイルのファイル名を取得します。
     *
     * @param int $id カテゴリID
     * @param int $item1_id 大項目のID
     * @param int $item2_id 中項目のID
     * @param int $item3_id 小項目のID
     * @return string 画像のファイル名
     */
    public static function getFileName($id, $id1, $id2, $id3) {
        $fileName = Config::IMGDIR . $id . "_" . $id1 . "_" . $id2 . "_" . $id3;

        // 存在するファイルの形式を調べる
        if (file_exists($fileName . ".jpg")) { // jpg ファイル
            $fileName .= ".jpg";
        } elseif (file_exists($fileName . ".png")) { // png ファイル
            $fileName .= ".png";
        } elseif (file_exists($fileName . ".gif")) { // gif ファイル
            $fileName .= ".gif";
        } else { // 画像が存在しない
            $fileName = "";
        }

        return $fileName;
    }

    /**
     * 登録されている画像を削除します。
     *
     * @param int $id カテゴリID
     * @param int $item1_id 大項目のID
     * @param int $item2_id 中項目のID
     * @param int $item3_id 小項目のID
     */
    public static function deleteImg($id, $id1, $id2, $id3) {
        $fileName = Config::IMGDIR . $id . "_" . $id1 . "_" . $id2 . "_" . $id3;
        if (file_exists($fileName . ".jpg")) unlink($fileName . ".jpg");
        if (file_exists($fileName . ".gif")) unlink($fileName . ".gif");
        // PNG画像の削除も考慮する場合
        if (file_exists($fileName . ".png")) unlink($fileName . ".png");
    }
}

?>