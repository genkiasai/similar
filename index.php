<?php
    session_start();
    require ("./common.php");
    // require ("./zipName.php");
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
    var_dump("フォルダ読み込み開始");
    $pdf_cnt = 0;
    $pdf_name[] = "";
    $pdf_tmp_name[] = "";
    if (isset($_FILES["dirname"]["tmp_name"])) {
        ////////////////////////
        // Zipファイル読み込み //
        ////////////////////////
        // 指定されたフォルダの.zipファイルのパスを[dirname][tmp_name]で抽出
        var_dump("zipファイル読み込み開始");
        $zip_cnt = 0;
        $zip_name[] = "";
        $zip_tmp_name[] = "";
        if (!(file_exists(__DIR__ . "/pdftotext_escape"))) {
            mkdir(__DIR__ . "/pdftotext_escape");
        }
        if (!(file_exists(__DIR__ . "/pdftotext_escape/pdf"))) {
            mkdir(__DIR__ . "/pdftotext_escape/pdf");
        }
        for ($i = 0; $i < count($_FILES["dirname"]["tmp_name"]); $i++) {
            if (substr($_FILES["dirname"]["name"][$i], -4, 4) === ".zip") {
                $zip_name = preg_replace("#[　 ]+#um", "", $_FILES["dirname"]["name"][$i]);
                var_dump("zipファイル" . $i . "：" . $zip_name);
                $zip_tmp_name = $_FILES["dirname"]["tmp_name"][$i];
                move_uploaded_file($zip_tmp_name, "./pdftotext_escape/" . $zip_name);
                if ($zip->open("./pdftotext_escape/" . $zip_name) === true) {
                    $zip->extractTo("./pdftotext_escape/");
                    $zip->close();
                }
                $pdf_files = glob("./pdftotext_escape/*.pdf");
                for ($j = 0; $j < count($pdf_files); $j++) {
                    $file_name = preg_replace("#[ 　]+#um", "", explode(".", $zip_name)[0]);
                    rename($pdf_files[$j], "./pdftotext_escape/pdf/" . $file_name . ".pdf");
                } 
            }
        }
        $pdf_files = [];
        $pdf_files = glob("./pdftotext_escape/pdf/*.pdf");
        for ($i = 0; $i < count($pdf_files); $i++) {
            var_dump("展開させてescapeさせたpdfファイル：" . $pdf_files[$i]);
        } 
        for ($i = 0; $i < count($pdf_files); $i++) {
            $pdf_path_name = basename($pdf_files[$i]);
            $pdf_file_name[$i] = $pdf_path_name; 
            $pdf_replace_name = preg_replace("#[ 　]+#um", "", $pdf_path_name);
            // $cmd2 = __DIR__ . "\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS " . __DIR__ . "\\pdftotext_escape/pdf/" . $pdf_replace_name;
            $cmd2 = __DIR__ . "/xpdf-tools-win-4.03/bin64/pdftotext -enc Shift-JIS " . __DIR__ . "/pdftotext_escape/pdf/" . $pdf_replace_name;
            var_dump("\$cmd2：" . $cmd2);
            exec ($cmd2, $dummy, $result2);
            var_dump("\$result2：" . $result2);
            if ($result2 === 0) {
                // $txt_name = explode(".", $pdf_path_name)[0] . ".txt";
                $txt_name = explode(".", $pdf_replace_name)[0] . ".txt";
                $txt_path = __DIR__ . "\\pdftotext_escape\\pdf\\" . $txt_name;
                // 文字化け
                $file_get_contents = file_get_contents($txt_path);
                $str = mb_convert_encoding($file_get_contents,"utf-8","sjis"); // シフトJISからUTF-8に変換
                $str = preg_replace("#[ \n\t\r　]+#um", "", $str);
                $contents[$i] = $str;
            }
        }
        array_map("unlink", glob("./pdftotext_escape/pdf/*.pdf"));
        array_map("unlink", glob("./pdftotext_escape/pdf/*.txt"));
        array_map("unlink", glob("./pdftotext_escape/*.zip"));
        array_map("unlink", glob("./pdftotext_escape/*.txt"));
    }

    // 計算した類似度の表示
    // $contents_cnt = count($contents);
    // if ($contents_cnt >=  2) {
    //     for ($i = 0; $i < $contents_cnt - 1; $i++) {
    //         for ($j = $i + 1; $j < $contents_cnt; $j++) {
    //             get_ngram ($contents[$i], $_POST["ngram"], $substr1);
    //             get_ngram ($contents[$j], $_POST["ngram"], $substr2);
    //             $aaa = array_intersect($substr1, $substr2);
    //             similar_check ($substr1, $substr2, $perc);
    //             if ($perc > $_POST["threshold"]) {
    //                 echo "<p style='color: red;'>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
    //             } else {
    //                 echo "<p>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
    //             }
    //         }
    //     }
    // }
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

        .outputArea {
            border: 1px solid #000;
            background-color: #fff;
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
                「フォルダ選択」ボタンで比較したい.pdfファイルが圧縮されているzipファイルが入っているフォルダを選択し、読み込みボタンを押下することで、指定したフォルダ内にある圧縮ファイルの.pdfファイルに書かれている文字列を取得し、類似度を計算します。<br>
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
                    <!-- @TODO ファイル名 -->
                    <p>ファイル<?php echo $i + 1; ?>：<?php echo $pdf_file_name[$i]; ?></p>
                    <textarea name="read_text" id="read_text<?php echo $i; ?>" cols="30" rows="10"><?php echo $contents[$i]; ?></textarea>
                <?php endfor; ?>
            </div>

            <?php
                // 計算した類似度の表示
                $contents_cnt = count($contents);
                if ($contents_cnt >=  2) :
                    for ($i = 0; $i < $contents_cnt - 1; $i++) :
            ?>
            <?php
                        for ($j = $i + 1; $j < $contents_cnt; $j++) :
                            get_ngram ($contents[$i], $_POST["ngram"], $substr1);
                            get_ngram ($contents[$j], $_POST["ngram"], $substr2);
                            $aaa = array_intersect($substr1, $substr2);
                            $bbb = array_intersect($substr2, $substr1);
                            // 一致した要素を赤文字で表示するための処理
                            // $a：要素数のカウントアップ
                            $a = 0;
                            // ひとつ前の要素のインデックス番号メモリ
                            $b = 0;
                            // インクリメント
                            $c = 0;
                            $continuous = [];
                            $continuous1 = "";
                            $continuous2 = "";
                            $flug = false;
                            $break = false;
                            $continuousStr = "";
                            $elementRed1 = [];
                            while (true) {
                                $element = $aaa[$c];
                                if (!empty($element) or $break) {
                                    $a++;
                                    if (($c - $b == 1) and !$break) { // もし連番の要素だったら
                                        $continuous[count($continuous)] = mb_substr($aaa[$b], 0, 1);
                                        $flug = true;
                                    } elseif (($c - $b == 2) and !$break) { // もし一個飛ばしの要素だったら
                                        $continuous[count($continuous)] = mb_substr($aaa[$b], 0, 2);
                                        $flug = true;
                                    } elseif (($c - $b == 3) and !$break) { // もし二個飛ばしの要素だったら
                                        $continuous[count($continuous)] = mb_substr($aaa[$b], 0, 3);
                                        $flug = true;
                                    } elseif ($c != 0) {
                                            for ($k=0; $k < count($continuous); $k++) { 
                                                $continuousStr = $continuousStr . $continuous[$k];
                                            }
                                            $elementRed1[count($elementRed1)] = $continuousStr . $aaa[$b];
                                            $continuous = [];
                                            $continuousStr = "";
                                            $flug = false;
                                    }
                                    if ($break) {
                                        break;
                                    }
                                    if ($a >= count($aaa)) {
                                        $break = true;
                                    }
                                    $b = $c;
                                }
                                $c++;
                            }

                            
                            // 一致した要素を赤文字で表示するための処理
                            // $a：要素数のカウントアップ
                            $a = 0;
                            // ひとつ前の要素のインデックス番号メモリ
                            $b = 0;
                            // インクリメント
                            $c = 0;
                            $continuous = [];
                            $continuous1 = "";
                            $continuous2 = "";
                            $flug = false;
                            $break = false;
                            $continuousStr = "";
                            $elementRed2 = [];
                            while (true) {
                                $element = $bbb[$c];
                                if (!empty($element) or $break) {
                                    $a++;
                                    if (($c - $b == 1) and !$break) { // もし連番の要素だったら
                                        $continuous[count($continuous)] = mb_substr($bbb[$b], 0, 1);
                                        $flug = true;
                                    } elseif (($c - $b == 2) and !$break) { // もし一個飛ばしの要素だったら
                                        $continuous[count($continuous)] = mb_substr($bbb[$b], 0, 2);
                                        $flug = true;
                                    } elseif (($c - $b == 3) and !$break) { // もし二個飛ばしの要素だったら
                                        $continuous[count($continuous)] = mb_substr($bbb[$b], 0, 3);
                                        $flug = true;
                                    } elseif ($c != 0) {
                                            for ($k=0; $k < count($continuous); $k++) { 
                                                $continuousStr = $continuousStr . $continuous[$k];
                                            }
                                            $elementRed2[count($elementRed2)] = $continuousStr . $bbb[$b];
                                            $continuous = [];
                                            $continuousStr = "";
                                            $flug = false;
                                    }
                                    if ($break) {
                                        break;
                                    }
                                    if ($a >= count($bbb)) {
                                        $break = true;
                                    }
                                    $b = $c;
                                }
                                $c++;
                            }
                            
                            for ($k=0; $k < count($elementRed1); $k++) { 
                                $contents[$i] = preg_replace("#$elementRed1[$k]+#um", "<span style=color:red>$elementRed1[$k]</span>", $contents[$i]);
                            }
                            // for ($k=0; $k < count($elementRed2); $k++) { 
                            //     $contents[$j] = preg_replace("#$elementRed2[$k]+#um", "<span style=color:red>$elementRed2[$k]</span>", $contents[$j]);
                            // }
                            
                            $compare = "";
                            $newElementRed2 = [];
                            $newElementRed22 = [];
                            for ($k=0; $k < count($elementRed2); $k++) {
                                $compare = $elementRed2[$k]; 
                                for ($l=0; $l < count($elementRed2); $l++) {
                                    $explode = [];
                                    $split = [];
                                    if ($compare != $elementRed2[$l]) {
                                        // @TODO かさなった要素の比較される側の配列のインデックスの要素を覚えておいて最後にそのインデックスの要素を削除
                                        if((strpos($elementRed2[$l], $compare) !== false) or (strpos($compare, $elementRed2[$l]) !== false)){
                                            // $explode = explode($compare, $elementRed2[$l], -1);
                                            $split = preg_split("/$compare/", $elementRed2[$l]);
                                            if (!isset($explode)) {
                                                // $explode = explode($elementRed2[$l], $compare, -1);
                                                // $split = preg_split("/$compare/", $elementRed2[$l]);
                                            }
                                            $array = array($elementRed2[$l]);
                                            // $elementRed2 = array_diff($elementRed2, $array);
                                            $array = "";
                                            // $newElementRed2 = array_merge($newElementRed2, $explode);
                                            $newElementRed2 = array_merge($newElementRed2, $split);
                                        }
                                    }
                                }
                            }

                            
                            $elementRed2 = array_merge($elementRed2, $newElementRed2);
                            for ($k=0; $k < count($elementRed2); $k++) { 
                                if ($elementRed2[$k] != "") {
                                    $contents[$j] = preg_replace("#$elementRed2[$k]+#um", "<span style=color:red>$elementRed2[$k]</span>", $contents[$j]);
                                }
                            }
                            // $outputTest = "";
                            // $elementRed2 = array_merge($elementRed2, $newElementRed2);
                            // for ($k=0; $k < count($elementRed2); $k++) { 
                            //     $outputTest = preg_replace("#$elementRed2[$k]+#um", "<span style=color:red>$elementRed2[$k]</span>", $contents[$j]);
                            // }


                            // $empty = array("");
                            // $empty = [""];
                            // $newElementRed2 = array_diff($newElementRed2, $empty);

                            // for ($k=0; $k < count($elementRed2); $k++) { 
                            //     $elementRed22[$k] = "<span style=color:red>$elementRed2[$k]</span>";
                            // }
                            // for ($k=0; $k < count($elementRed2); $k++) { 
                            //     $elementRed222[$k] = "/$elementRed2[$k]/";
                            // }

                            // $contents[$j] = preg_replace($elementRed222, $elementRed22, $contents[$j]);

                            // for ($k=0; $k < count($elementRed2); $k++) { 
                            //     $contents[$j] = preg_replace("#$elementRed2[$k]+#um", "<span style=color:red>$elementRed2[$k]</span>", $contents[$j]);
                            //     # code...
                            // }

                            similar_check ($substr1, $substr2, $perc);
                            if ($perc > $_POST["threshold"]) {
                                echo "<p style='color: red;'>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                            } else {
                                echo "<p>上段ファイル" . ($i + 1) . "と" . "下段ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                            }

                            // @TODO
                            // for ($r=0; $r < count($bbb); $r++) { 
                            //     for ($s=0; $s < count($substr2); $s++) { 
                            //         if ($bbb[$r] == $substr2[$s]) {
                            //             $substr2[$s] = str_replace($bbb[$r], "<span style=color:red>$bbb[$r]</span>", $substr2[$s]);
                            //         }
                            //     }
                            // }

                            // for ($r=0; $r < count($substr2); $r+=3) {
                            //     $outputTest = $outputTest . $substr2[$r];
                            // } 
            ?>
                            <div class="outputArea" id="outputArea1"><?php echo $contents[$i]; ?></div>
                            <div class="outputArea" id="outputArea2"><?php echo $contents[$j]; ?></div>
                            <!-- <div class="outputArea" id="outputArea3"><?php // echo $outputTest; ?></div> -->
            <?php
                            $contents[$i] = preg_replace("#<span style=color:red>|</span>#", "", $contents[$i]);
                            $contents[$j] = preg_replace("#<span style=color:red>|</span>#", "", $contents[$j]);
                        endfor;
                    endfor;
                endif;
            ?>
            <?php
                //             get_ngram ($contents[$i], $_POST["ngram"], $substr1);
                //             get_ngram ($contents[$j], $_POST["ngram"], $substr2);
                //             $aaa = array_intersect($substr1, $substr2);
                //             similar_check ($substr1, $substr2, $perc);
                //             if ($perc > $_POST["threshold"]) {
                //                 echo "<p style='color: red;'>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                //             } else {
                //                 echo "<p>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                //             }
                //         }
                //     }
                // }
            ?>
        </div>

    </form>

    <!-- Bootstrap4 -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>