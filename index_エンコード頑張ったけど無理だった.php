<?php
    session_start();
    require ("./common.php");
    require ("./zipName.php");
    ini_set ("display_errors", 0);

    if (!isset($_POST["ngram_"])) {
        $_POST["ngram_"] = 3;
    }
    if (!isset($_POST["ngram"])) {
        $_POST["ngram"] = 3;
    }
    if (!isset($_POST["threshold"])) {
        $_POST["threshold"] = 80;
    }

    // エラーメッセージ閾値
    $text_min_len = 999999999;

    // ZipArchiveクラスコンストラクタ
    $zip = new ZipArchive;


    //////////////
    // 比較処理1 //
    //////////////
    if (!empty($_POST["check"])) {
        $text1 = $_POST["check1"];
        $text2 = $_POST["check2"];
        similar_text($text1, $text2, $perc);
    }

    //////////////
    // 比較処理2 //
    //////////////
    $judge_len = 9999999;
    if (isset($_POST["check_sub"])) {
        $text3 = preg_replace("#[ \n\r\t　]+#um", "", $_POST["check3"]);
        $text4 = preg_replace("#[ \n\r\t　]+#um", "", $_POST["check4"]);
        $n3 = get_ngram ($text3, $_POST["ngram_"], $substr3);
        $n4 = get_ngram ($text4, $_POST["ngram_"], $substr4);
        similar_check ($substr3, $substr4, $perc_sub);
        // エラー判定
        $text3_len = mb_strlen ($text3);
        $text4_len = mb_strlen ($text4);
        $judge_len = min($text3_len, $text4_len);
    }

    /////////////
    // 変換処理 //
    /////////////
    if (!empty($_POST["conversion"])) {
        $text5 = $_POST["check5"];
        // スラッシュで囲わないと上手く動かなかった →　囲うのはスラッシュでなくても良くて、囲う文字のことをデリミタという
        // 角かっこ[]で囲ったものをマッチさせる
        // +記号は連続文字を一つのまとまりとして扱う
        // uはUTF-8として扱う
        // mは改行文字ごとに行頭と行末を判断する。mをつけなければ改行されている文字列があっても先頭文字と末端文字でしか行頭と行末を判断してくれない
        $text6 = preg_replace("#[ \n\t\r　]+#um", "", $text5);

        $len = mb_strlen($text6);
        $n = 3;
        if ($len <=0 || $len < $n) return false;
        for ($i = 0; $i < $len; $i++) {
            $substr = mb_substr($text6, $i, $n);
            if (mb_strlen($substr) >= $n) {
                $comp_array[$i] = $substr;
            }
        }
    }

    /////////////////////
    // フォルダ読み込み //
    /////////////////////
    // 指定されたフォルダの.pdfファイルのパスを[dirname][tmp_name]で抽出
    // 抽出したファイルを1つずつ見ていく
    // for $i = 0; $i < count($txt_contents); $i++
    // 0.特定のフォルダ(./pdftotext_escape)に.pdfファイルを[dirname][tmp_name]でコピーさせる
    // 1.「pdftotextのパス -enc Shift-JIS ./pdftotext_escape/.pdfのファイル名」でコマンドをたたいて.txtファイルを作成
    // 2.作成した.txtファイルのテキストを配列($contents)に格納
    // 3.作成した.txtファイルを削除
    // 4.
    // 5.

    // 指定されたフォルダの.pdfファイルのパスを[dirname][tmp_name]で抽出
    $pdf_cnt = 0;
    $pdf_name[] = "";
    $pdf_tmp_name[] = "";
    if (isset($_FILES["dirname"]["tmp_name"])) {
        ////////////////////////
        // Zipファイル読み込み //
        ////////////////////////
        // 指定されたフォルダの.zipファイルのパスを[dirname][tmp_name]で抽出
        $zip_cnt = 0;
        $zip_name[] = "";
        $zip_tmp_name[] = "";
        for ($i = 0; $i < count($_FILES["dirname"]["tmp_name"]); $i++) {
            if (substr($_FILES["dirname"]["name"][$i], -4, 4) === ".zip") {
                $zip_name[$zip_cnt] = $_FILES["dirname"]["name"][$i];
                $zip_tmp_name[$zip_cnt] = $_FILES["dirname"]["tmp_name"][$i];
                $zip_cnt++;
            }
        }

        // zipファイルがあったら
        if ($zip_cnt > 0) {
            for ($i = 0; $i < count($zip_tmp_name); $i++) {
                // 0.特定のフォルダ(./pdftotext_escape)に.zipファイルを[dirname][tmp_name]でコピーさせる
                $test = preg_replace("#[ ]#", "" ,$zip_name[$i]);
                // $zip_name_replace = mb_convert_encoding($test, "CP932", "sjis-win");
                // $zip_name_replace = mb_convert_encoding($test, "CP932", "utf-8");
                // // $zip_name_replace = mb_convert_encoding($test, "CP932", "sjis-win");
                // $zip_name_replace = mb_convert_encoding(preg_replace("#[ ]#", "" ,$zip_name[$i]), "CP932", "sjis-win");
                // $zip_name_replace = mb_convert_encoding(preg_replace("#[ ]#", "" ,$zip_name[$i]), "CP932", "utf-8");
                // $zip_name_replace = mb_convert_encoding(preg_replace("#[ ]#", "" ,$zip_name[$i]), "CP932");
                move_uploaded_file($zip_tmp_name[$i], "./pdftotext_escape/" . $test);                    $archive = "./pdftotext_escape/" . $test;
                // $res = Archive::convertZipEncode($archive);
                // var_dump($res);
                if ($zip->open("./pdftotext_escape/" . $test) === true) {
                    // $zipEntry = $zip->statIndex($i);
                    // $zipName = $zipEntry["name"];
                    // $destName = mb_convert_encoding($zipName, "utf-8", "CP932");
                    // $zip_name_replace = mb_convert_encoding($zipName, "utf-8", "sjis-win");
                    // $zip_name_replace = mb_convert_encoding($zipName, "utf-8");
                    // $zip_name_replace = mb_convert_encoding($zipName, "CP932", "sjis-win");
                    // $zip_name_replace = mb_convert_encoding($zipName, "CP932", "utf-8");
                    // $zip_name_replace = mb_convert_encoding($zipName, "CP932");
                    // $zip->renameName($zipName, $destName);
                    
                    $zip->extractTo("./pdftotext_escape/");
                    // $zip->extractTo("./pdftotext_escape/" . $destName);
                    // $zip->extractTo("./pdftotext_escape/");
                    $zip->close();
                }
            }
        }

        ////////////////////////
        // pdfファイル読み込み //
        ////////////////////////
        for ($i = 0; $i < count($_FILES["dirname"]["tmp_name"]); $i++) {
            if (substr($_FILES["dirname"]["name"][$i], -4, 4) === ".pdf") {
                $pdf_name[$pdf_cnt] = $_FILES["dirname"]["name"][$i];
                $pdf_tmp_name[$pdf_cnt] = $_FILES["dirname"]["tmp_name"][$i];
                $pdf_cnt++;
            }
        }
        // pdfファイルがあったら
        if ($pdf_cnt > 0) {
            for ($i = 0; $i < count($pdf_tmp_name); $i++) {
                // 0.特定のフォルダ(./pdftotext_escape)に.pdfファイルを[dirname][tmp_name]でコピーさせる
                $pdf_name_replace = preg_replace("#[　 ]#", "" ,$pdf_name[$i]);
                move_uploaded_file($pdf_tmp_name[$i], "./pdftotext_escape/" . $pdf_name_replace);
            }
        }

        /////////////
        // pdf2txt //
        /////////////
        $pdf_path = glob("./pdftotext_escape/*.pdf");
        for ($i=0; $i < count($pdf_path); $i++) {
            // @TODO ファイル名もいる
            $pdf_path_name = basename($pdf_path[$i]);
            $pdf_replace_name = preg_replace("#[ 　]#", "", $pdf_path_name);
            // rename($pdf_path[$i], "./pdftotext_escape/pdf/" . $pdf_replace_name);
            rename($pdf_path[$i], "./pdftotext_escape/pdf/1.pdf");
            // $read_pdf_path = glob("./pdftotext_escape/pdf/*.pdf");
            // $cmd1 = "cd " . __DIR__;
            // exec ($cmd1, $dummy, $result1);
            // $cmd2 = __DIR__ . "\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS " . $read_pdf_path[$i];
            $cmd2 = __DIR__ . "\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS " . __DIR__ . "\\pdftotext_escape/pdf/1.pdf";
            // $cmd2 = __DIR__ . "\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS " . __DIR__ . "\\pdftotext_escape/pdf/" . $pdf_path_name;
            exec ($cmd2, $dummy, $result2);
            if ($result2 === 0) {
                // $txt_name = explode(".", $pdf_path_name)[0] . ".txt";
                $txt_name = explode(".", "1.txt");
                $txt_path = __DIR__ . "\\pdftotext_escape\\pdf\\" . $txt_name;
                // 文字化け
                $file_get_contents = file_get_contents($txt_path);
                $str = mb_convert_encoding($file_get_contents,"utf-8","sjis"); // シフトJISからUTF-8に変換
                $contents[$i] = $str;
                // unlink("./pdftotext_escape/pdf/" . $pdf_name_replace);
                // unlink($txt_path);
            }
        }
        array_map("unlink", glob("./pdftotext_escape/pdf/*.pdf"));
        array_map("unlink", glob("./pdftotext_escape/pdf/*.txt"));
        array_map("unlink", glob("./pdftotext_escape/*.zip"));
        array_map("unlink", glob("./pdftotext_escape/pdf/*.txt"));
    }

                // 1.「pdftotextのパス -enc Shift-JIS ./pdftotext_escape/.pdfのファイル名」でコマンドをたたいて.txtファイルを作成
                // index.php(このファイル)と同階層にxpdf-tools-win-4.03を置く
                // Macはスラッシュ？
                // $cmd = __DIR__ . "/xpdf-tools-win-4.03/bin64/pdftotext -enc Shift-JIS " . __DIR__ . "/pdftotext_escape/" . $pdf_name_replace;
                // Windowsは￥マーク？
                // $cmd = __DIR__ . "\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS " . __DIR__ . "\\pdftotext_escape\\" . $pdf_name_replace;
                // exec ($cmd, $dummy, $result);
                // if ($result === 0) {
                //     $txt_name = explode(".", $pdf_name_replace)[0] . ".txt";
                //     $txt_path = __DIR__ . "\\pdftotext_escape\\" . $txt_name;
                //     // 文字化け
                //     $file_get_contents = file_get_contents($txt_path);
                //     $str = mb_convert_encoding($file_get_contents,"utf-8","sjis"); // シフトJISからUTF-8に変換
                //     $contents[$i] = $str;
                //     unlink("./pdftotext_escape/" . $pdf_name_replace);
                //     unlink($txt_path);
                // }






        for ($i = 0; $i < $zip_cnt; $i++) {
            // 読み込んだファイルに書かれた文字数で一番少ない数字を取得
            for ($i = 0; $i < count ($contents); $i++) {
                $text_len = mb_strlen ($contents[$i]);
                if ($text_len < $text_min_len) {
                    $text_min_len = $text_len;
                }
            }
            // if (empty($_FILES["dirname"]["name"][0])) {
            //     $text_min_len = 0;
            // }
        }
    



?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>similar_check</title>
    <!-- Bootstrap4 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        .input_text {
            margin: 30px 0;
        }

        .row {
            margin-top: 0;
        }

        p {
            margin-bottom: 0;
        }

        .result {
            padding-bottom: 25px;
            margin-bottom: 25px;
            border-bottom: 1px solid #000;
        }

        .check {
            padding: 0;
        }

        .check1, .check3, .check5 {
            padding-right: 10px;
        }

        .check2, .check4, .check6 {
            padding-left: 10px;
        }

        .check p {
            margin: 3px 0;
        }

        textarea {
            width: 100%;
        }

        .dirname {
            display: none;
        }

        label {
            display: inline-block;
            border: 1px solid #000;
            padding: 3px;
            background-color: lightgray;
            box-shadow: 1px 1px 0px rgba(0, 0, 0, .3);
            color: #000;
            text-decoration: none;
        }

        label:active {
            position: relative;
            top: 1px;
            left: 1px;
            color: #000;
        }

        .button {
            border-radius: 5px 5px;
        }


        .content_box {
            background-color: #dfd;
            padding: 10px 40px 40px 40px;
            margin-bottom: 40px;
            border-radius: 16px 16px;
        }

        .example {
            border: 1px solid #000;
            padding: 16px;
            margin-bottom: 16px;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>剽窃チェックツール</h1>
    <form action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="check1" value=<?php $_POST["check1"]; ?>>
    <input type="hidden" name="check2" value=<?php $_POST["check2"]; ?>>
    <input type="hidden" name="check3" value=<?php $_POST["check3"]; ?>>
    <input type="hidden" name="check4" value=<?php $_POST["check4"]; ?>>
        <!-- ------------------------------------------- -->
        <div class="content_box">
        <p>【similar_text】</p>
        <p class="discription">
            PHPで用意されている関数「similar_text ($str1(string), $str2(string), $perc(double))」を使った類似度チェック方法です。<br>
            第一引数と第二引数に比較する文字列を指定すると第三引数の変数に類似度が返ってきます。<br>
            ロジックとしては、UTF-8の文字コードで比較するため、日本語の文字列を比較する場合は比較精度が落ちる場合があります。<br>
            例）
            <div class="example">
                <p class="cord" style="color:red">コード</p>
                $perc = 0;<br>
                $str1 = "あああ";<br>
                $str2 = "いいい";<br>
                similar_text ($str1, $str2, $perc);<br>
                echo "類似度：" . $perc . "%";
            </div>
            <div class="example">
                <p class="output" style="color:red">出力結果</p>
                類似度：66.6666667%
            </div>

            例の場合、$str1と$str2の文字列「あああ」と「いいい」の文字コードは16進数表記で<br>
            あああ => E3 81 <span style="color:red">82</span> E3 81 <span style="color:red">82</span> E3 81 <span style="color:red">82</span> <br>
            いいい => E3 81 <span style="color:red">83</span> E3 81 <span style="color:red">83</span> E3 81 <span style="color:red">83</span> <br>
            となるため、66.7%となります。
            <br>
        </p>
        <div class="row input_text">
            <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check1">
                <p>Text1</p>
                <textarea name="check1" id="check1" cols="" rows="10"><?php if(!empty($_POST["check1"])){
                        echo $_POST["check1"];
                    } ?></textarea>
            </div>

            <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check2">
                <p>Text2</p>
                <textarea name="check2" id="check2" cols="" rows="10"><?php if(!empty($_POST["check2"])){
                        echo $_POST["check2"];
                    } ?></textarea>
            </div>
            <input class="col-12 col-sm-12 col-md-12" id="check" type="submit" name="check" value="比較">
        </div>
        <div class="result">
            <p>類似度：<?php if (isset($perc)) {echo $perc . "%";} ?></p>
        </div>
        </div>

        <!-- ------------------------------------------- -->
        <div class="content_box">
        <p>【N-gram】</p>
        <p class="discription">
            「N-gram」を用いた類似度チェック方法です。「N-gram」とは、文字列の隣り合うN個の文字の並びのことをいいます。<br>
            文字列のN-gramをすべて配列の要素として取得し、配列の一致度を見ることで類似度を計算します。<br>
            以下、N-gramを2とした時の例です。
            <div class="example">
                文字列①：店で食事をする<br>
                <span style="color:red">店で</span><br>
                で食<br>
                <span style="color:red">食事</span><br>
                <span style="color:red">事を</span><br>
                をす<br>
                <span style="color:red">する</span><br>
                <br>
                文字列②：食事を店でする<br>
                <span style="color:red">食事</span><br>
                <span style="color:red">事を</span><br>
                を店<br>
                <span style="color:red">店で</span><br>
                です<br>
                <span style="color:red">する</span>
            </div>
            赤色の文字が、文字列①と文字列②で一致しており、一致した要素数を文字列②の要素数で除することで類似度を計算します。<br>
            上記の例で計算すると、<br>
            4（一致した要素数） / 6（文字列②の要素数） = 66.7%<br>
            となります。<br>
            この手法は文字コードで比較する方法ではないため日本語にも対応でき、N-gramの数字を変えることで類似度の計算の検出精度を可変させることができます。<br>
            <br>
        </p>
        <div class="row input_text">
            <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check1">
                <p>Text3</p>
                <textarea name="check3" id="check3" cols="" rows="10"><?php if(!empty($_POST["check3"])){
                        echo $_POST["check3"];
                    } ?></textarea>
            </div>

            <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check2">
                <p>Text4</p>
                <textarea name="check4" id="check4" cols="" rows="10"><?php if(!empty($_POST["check4"])){
                        echo $_POST["check4"];
                    } ?></textarea>
            </div>
            N-gram:<input type="number" class="ngram" id="ngram_" name="ngram_" min="2" max="10" step="1" value="<?php echo $_POST["ngram_"]; ?>">
            <input class="col-12 col-sm-12 col-md-12" id="check_sub" type="submit" name="check_sub" value="比較">
        </div>
        <div class="result">
            <?php if (!empty($_POST["check3"]) && !empty ($_POST["check4"])) : ?>
                <?php if ($_POST["ngram_"] < 2 || $_POST["ngram_"] > $judge_len || empty($_POST["ngram_"])) : ?>
                    <p class="error">N-gramは2以上かつ文字列の文字数以下で指定してください</p>
                <?php endif; ?>
            <?php endif; ?>
                <p>類似度：<?php if (isset($perc_sub)) {echo $perc_sub . "%";} ?></p>
        </div>
        </div>

        <div class="content_box">
            <p>【フォルダ読み込み】</p>
            <p class="discription">
                「フォルダ選択」ボタンで比較したい.docxファイルが入っているフォルダを選択し、読み込みボタンを押下することで、指定したフォルダ内の.docxファイルに書かれている文字列を取得し、類似度を計算します。<br>
                現状、N-gramでの類似度チェックとなっているため、N-gramの数値の指定もお願いします。
            </p>
            <div class="row input_text">
                <input type="file" class="dirname" id="dirname" name="dirname[]" webkitdirectory directory value="フォルダ読み込み"><br>
                <label class="button" for="dirname">フォルダ選択</label><br>
                <div class="w-100"></div>
                <p class="mr-5">N-gram:<input type="number" class="ngram" id="ngram" name="ngram" min="2" max="10" step="1" value="<?php echo $_POST["ngram"]; ?>"></p>
                <p class="p-0">類似度閾値：<input type="number" class="threshold" id="threshold" name="threshold" min="1" max="100" step="1" value="<?php echo $_POST["threshold"]; ?>"></p>
                <div class="w-100"></div>
                <p>※文字列に含まれる「改行」「空白」「タブ」は削除して計算しています。</p>
                <?php if ($text_min_len < $_POST["ngram"] || $_POST["ngram"] < 2) : ?>
                    <p class="error col-12 col-sm-12 col-md-12 p-0">N-gramは2以上かつ文字列の文字数以下で指定してください</p>
                <?php elseif (empty($_FILES["dirname"]["name"][0]) && isset($_POST["read_dir"])) : ?>
                    <p class="error col-12 col-sm-12 col-md-12 p-0">読み込むフォルダを指定してください</p>
                <?php endif; ?>
                <input type="submit" class="col-12 col-sm-12 col-md-12" id="read_dir" name="read_dir" value="読み込み">
                <?php // var_dump($_FILES["dirname"]["name"]); ?>
                <?php for ($i = 0; $i < count($contents); $i++): ?>
                    <p>ファイル<?php echo $i + 1; ?>：<?php echo $pdf_name[$i]; ?></p>
                    <textarea name="read_text" id="read_text<?php echo $i; ?>" cols="30" rows="10"><?php echo $contents[$i]; ?></textarea>
                <?php endfor; ?>
            </div>

            <?php
                // 計算した類似度の表示
                $contents_cnt = count($contents);
                if ($contents_cnt >=  2) {
                    for ($i = 0; $i < $contents_cnt - 1; $i++) {
                        for ($j = $i + 1; $j < $contents_cnt; $j++) {
                            get_ngram ($contents[$i], $_POST["ngram"], $substr1);
                            get_ngram ($contents[$j], $_POST["ngram"], $substr2);
                            similar_check ($substr1, $substr2, $perc);
                            if ($perc > $_POST["threshold"]) {
                                echo "<p style='color: red;'>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                            } else {
                                echo "<p>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                            }

                        }
                    }
                }
            ?>
        </div>

    </form>

    <!-- Bootstrap4 -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>