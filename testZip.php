<?php
    $zip = new ZipArchive;
    if ($zip->open("./テスト.zip") === true) {
        $idx = 0;
        $to = "UTF-8";
        $zipEntry = $zip->statIndex($idx);
        $entryName = $zipEntry['name'];

        echo "エンコード無しのファイル名：" . $entryName . "<br>";

        $encode = mb_detect_encoding($entryName, "Shift-JIS,EUC-JP");

        echo "エンコードチェックした結果：" . $encode . "<br>";

        $from = "SJIS";
        $destName = mb_convert_encoding($entryName, $to, $from);
        echo "SJISからUTF-8にエンコードした結果：" . $destName . "<br>";

        $from = "SJIS-win";
        $destName = mb_convert_encoding($entryName, $to, $from);
        echo "SJIS-winからUTF-8にエンコードした結果：" . $destName . "<br>";

        $from = "CP932";
        $destName = mb_convert_encoding($entryName, $to, $from);
        echo "CP932からUTF-8にエンコードした結果：" . $destName . "<br>";

        $to = "SJIS";
        $from = "UTF-8";
        $destName = mb_convert_encoding($entryName, $to, $from);
        echo "$from から$to にエンコードした結果：" . $destName . "<br>";

        $to = "SJIS-win";
        $from = "UTF-8";
        $destName = mb_convert_encoding($entryName, $to, $from);
        echo "$from から$to にエンコードした結果：" . $destName . "<br>";

        $to = "CP932";
        $from = "UTF-8";
        $destName = mb_convert_encoding($entryName, $to, $from);
        echo "$from から$to にエンコードした結果：" . $destName . "<br>";

        $zip->close();
    }

?>
<!-- 
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <p>ファイル読み込み</p>
    <form action="" method="POST" enctype="multipart/form-data">
        <input id="dirname" type="file" name="dirname[]" webkitdirectory directory value="フォルダ読み込み">
        <label class="button" for="dirname">フォルダ選択</label><br>
                <input type="submit" class="col-12 col-sm-12 col-md-12" id="read_dir" name="read_dir" value="読み込み">
    </form>
</body>
</html> -->