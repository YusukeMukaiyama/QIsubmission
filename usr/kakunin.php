<?php
/*-------------------------------------------------------------------------------------------------
	過程専用
	同意後の確認ページ

	template_kakunin.html をテンプレートとして読み込んで、<!-- CONTENTS -->部分を
	システムに必要なテキストを割り当てて表示します。

-------------------------------------------------------------------------------------------------*/

function getHtmlTemplate($filename)
{
    if (!file_exists($filename)) {
        die("ERROR FILE:".__FILE__." LINE:".__LINE__);
    }

    return file_get_contents($filename);
}

function generateFormHtml()
{
    $uid = isset($_REQUEST['uid']) ? htmlspecialchars($_REQUEST['uid'], ENT_QUOTES, 'UTF-8') : '';

    $formHtml = <<<HTML
<table>
<tr><td>
    <form method='POST' action='index.php'><input type='submit' name='no' value='≪   いいえ   '></form>
</td><td>
    <form method='GET' action='q_a.php'>
        <input type='hidden' name='uid' value='$uid'>
        <input type='submit' name='yes' value='   は   い   ≫'>
    </form>
</td></tr>
</table>
HTML;

    return $formHtml;
}

$filename = "./template_kakunin.html";
$template = getHtmlTemplate($filename);
$contentHtml = generateFormHtml();
$finalOutput = str_replace("<!-- CONTENTS -->", $contentHtml, $template);

echo $finalOutput;
?>
