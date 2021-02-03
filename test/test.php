<?php
    if (!empty($_POST["pdf"])) {
        $cmd1 = "cd C:\\xampp\\htdocs\\similar\\xpdf-tools-win-4.03\\bin64";
        $cmd2 = "C:\\xampp\\htdocs\\similar\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS C:\\xampp\\htdocs\\similar\\pdf\\テストです.pdf";
        exec ($cmd1);
        exec ($cmd2);
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<form action="" method="post">
    <input type="submit" name="pdf" id="pdf" value="実行">
    <textarea name="output" id="output" cols="30" rows="10"><?php  ?></textarea>
    </form>
</body>
</html>