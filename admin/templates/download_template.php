<!DOCTYPE HTML>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="Content-Style-Type" content="text/css">
    <link rel="stylesheet" type="text/css" href="./admin.css" media="all">
    <title>ダウンロード</title>
    <script type='text/javascript'>
        function down_check(form, ftype) {
            if (confirm("この処理には時間がかかります。\nダウンロードしますか？\n")) {
                document.forms[form].action = './makedata.php';
                document.forms[form].target = '_self';
                document.forms[form].ftype.value = ftype;
                document.forms[form].submit();
            }
        }

        function down_check_pdf(form, ftype) {
            document.forms[form].action = './download_pdf.php';
            document.forms[form].target = '_self';
            document.forms[form].ftype.value = ftype;
            document.forms[form].submit();
        }

        function redraw(fm, no) {
            switch (no) {
                case 0: fm.hos1.selectedIndex = 0; break;
                case 1: fm.hos2.selectedIndex = 0;
                case 2: fm.ward.selectedIndex = 0;
                default: break;
            }
            fm.action = './download.php';
            fm.target = '_self';
            fm.submit();
        }
    </script>
</head>
<body>
    <div align='center'>
        <h1>QIシステム</h1>
        <table cellspacing='1' cellpadding='5'>
            <tr>
                <th><a href='index.php'>メニュー</a> ≫ ダウンロード</th>
            </tr>
            <tr>
                <td>20<?= $year ?>年度のデータをダウンロードします。
                    <p style='margin : 2px; padding : 5px; background : #dddddd;'>
                        <!-- PDFダウンロード情報の表示 -->
                        <?= $errorMessage ?>
                    </p>
                    <!-- ダウンロードフォームの内容 -->
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
