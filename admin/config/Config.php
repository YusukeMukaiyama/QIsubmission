<?php

class Config {
    // データベース設定
    const DBHOST = "localhost";
    const DBNAME = "xs728645_qisystem";
    const DBUSER = "xs728645_qi";
    const DBPASS = "kangohyogouni";

    // COPYRIGHT表示
    const COPYRIGHT = "";

    // 回答後ログイン許可日数[日]
    const login_arrow_date = 2;

    // データ保持年数
    const HOLD = 2;

    // 有効状態 (ENABLE: 有効 / DISABLE: 無効)
    const ENABLE = "1";
    const DISABLE = "2";

    // 完了状態 (COMPLETE: 完了 / UNCOMPLETE: 未完了)
    const COMPLETE = "1";
    const UNCOMPLETE = "2";

    // 公開状態 (OPEN: 公開 / CLOSE: 非公開)
    const OPEN = "1";
    const CLOSE = "2";

    // 質問の形式(SELECT:選択 / TEXT:テキスト / CHECK:チェックボックス / RADIO:ラジオボタン / TEXTAREA:テキストエリア)
    const SELECT = "1";
    const TEXT = "2";
    const CHECK = "3";
    const RADIO = "4";
    const TEXTAREA = "5";

    // パスワード最大、最小文字数
    const PASS_SIZE_MIN = 6;
    const PASS_SIZE_MAX = 8;

    // 画像ファイル格納ディレクトリ
    const IMGDIR = "../img/";

    // 構造(看護師長) / 過程(看護師) / アウトカム(一般)
    const STRUCTURE = "1";
    const PROCESS = "2";
    const OUTCOME = "3";

    // 回答完了後の一定ログイン許可日数定義
    const GRACE = 7;

    // 都道府県リスト
    public static $prefName = [
        "", "北海道", "青森県", "岩手県", "宮城県", "秋田県", "山形県",
        "福島県", "茨城県", "栃木県", "群馬県", "埼玉県", "千葉県",
        "東京都", "神奈川県", "新潟県", "富山県", "石川県", "福井県",
        "山梨県", "長野県", "岐阜県", "静岡県", "愛知県", "三重県",
        "滋賀県", "京都府", "大阪府", "兵庫県", "奈良県", "和歌山県",
        "鳥取県", "島根県", "岡山県", "広島県", "山口県", "徳島県",
        "香川県", "愛媛県", "高知県", "福岡県", "佐賀県", "長崎県",
        "熊本県", "大分県", "宮崎県", "鹿児島県", "沖縄県"
    ];
}
