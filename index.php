<?php
    session_start();

    // 実行時間（4時間）
    ini_set("max_execution_time", 14400);
    // スクリプトが POST、GET などの入力をパースする最大の時間（0：無制限）
    ini_set("max_input_time", 0);
    // メモリの使用量（-1：無制限）
    ini_set("memory_limit", -1);
    // POSTデータに許可される最大サイズ
    ini_set("post_max_size", "4G");
    // アップロードされるファイルの最大サイズ
    ini_set("upload_max_filesize", "4G");
    // 同時にアップロードできるファイルの最大数
    ini_set("max_file_uploads", 1000);

    $startTime = microtime(true);

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

    /////////////////////
    // フォルダ読み込み //
    /////////////////////
    // 指定されたフォルダの.pdfファイルのパスを[filename][tmp_name]で抽出
    // var_dump("フォルダ読み込み開始");
    $read_file_name = "";       // 読み込まれたファイルのファイル名
    $read_file_tmp_name = "";   // 読み込まれたファイルのテンポラリ名
    $pdf_file_name = [];        // 読み込まれたzipファイルに圧縮されているファイルのファイル名の配列
    $zip = new ZipArchive;      // ZipArchiveクラス
    if (isset($_FILES["filename"]["tmp_name"])) {
        $read_file_name = $_FILES["filename"]["name"];
        $read_file_tmp_name = $_FILES["filename"]["tmp_name"];
        // 読み込まれたファイルがzipファイルではなかったらアラートを出す
        if (mb_substr($read_file_name, -4, 4) !== ".zip") {
            $alert = "<script type='text/javascript'>alert('zipファイルを選択してください');</script>";
            echo $alert;
        } elseif ($zip->open($_FILES["filename"]["tmp_name"]) === TRUE) {
            for ($i=0; $i < $zip->numFiles; $i++) { 
                // ZipArchive::FL_ENC_RAW → 自動でエンコードされるのを防止するパラメタ
                $archive_file_name = $zip->getNameIndex($i, ZipArchive::FL_ENC_RAW);
                $enc_name = mb_convert_encoding($archive_file_name, "utf-8", "sjis");
                $enc_name_nospace = preg_replace("#[ \n\r\t　]+#um", "", $enc_name);
                $zip->renameIndex($i, $enc_name_nospace);
                $pdf_file_name[$i] = $enc_name_nospace;
            }
            $zip->extractTo(__DIR__ . '/pdftotext_escape/', $pdf_file_name);
            $zip->close();

            array_map("unlink", glob("./pdftotext_escape/*.txt"));

            $pdf_files = glob(__DIR__ . "/pdftotext_escape/*.pdf");
            for ($i=0; $i < count($pdf_files); $i++) { 
                // txtファイル変換後のファイル名
                $text_file_name = mb_split("\.", mb_split("/", $pdf_files[$i])[count(mb_split("/", $pdf_files[$i]))-1])[0] . ".txt";
                $cmd = __DIR__ . "/poppler/bin/pdftotext.exe -enc Shift-JIS " . $pdf_files[$i] . " " . __DIR__ . "/pdftotext_escape/temporary_name.txt";
                exec($cmd, $dummy, $result);
                if ($result === 0) {
                    $test = glob("./pdftotext_escape/*txt");
                    // 文字化け
                    $txt_file_path = __DIR__ . "/pdftotext_escape/temporary_name.txt";
                    $file_get_contents = file_get_contents($txt_file_path);
                    $str = mb_convert_encoding($file_get_contents,"utf-8","sjis"); // シフトJISからUTF-8に変換
                    $contents[$i] = $str;
                }
            }
            array_map("unlink", glob("./pdftotext_escape/*.txt"));
            array_map("unlink", glob("./pdftotext_escape/*.pdf"));
        }
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- <meta charset="UTF-8"> -->
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

        .filename {
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
        }

        #outputArea1 {
            background-color: #fffff4;
        }

        #outputArea2 {
            background-color: #f4ffff;
        }

        .no-disp {
            display: none;
        }

        .wrapper {
            width: 90%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <h1>剽窃チェックツール</h1>
    <form action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="check1" value=<?php $_POST["check1"]; ?>>
    <input type="hidden" name="check2" value=<?php $_POST["check2"]; ?>>
    <input type="hidden" name="check3" value=<?php $_POST["check3"]; ?>>
    <input type="hidden" name="check4" value=<?php $_POST["check4"]; ?>>
        <!-- ------------------------------------------- -->
        <div class="content_box no-disp">
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
        <div class="content_box no-disp">
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
                <!-- フォルダ読み込み -->
                <input type="file" class="filename" id="filename" name="filename" value="フォルダ読み込み"><br>
                <!-- ファイル読み込み -->
                <label class="button" for="filename">フォルダ選択</label><br>
                <div class="w-100"></div>
                <p class="mr-5">N-gram：<input type="number" class="ngram" id="ngram" name="ngram" min="2" max="10" step="1" value="<?php echo $_POST["ngram"]; ?>"></p>
                <p class="p-0">類似度閾値：<input type="number" class="threshold" id="threshold" name="threshold" min="1" max="100" step="1" value="<?php echo $_POST["threshold"]; ?>"></p>
                <div class="w-100"></div>
                <p>※文字列に含まれる「改行」「空白」「タブ」は削除して計算しています。</p>
                <?php if ($text_min_len < $_POST["ngram"] || $_POST["ngram"] < 2) : ?>
                    <p class="error col-12 col-sm-12 col-md-12 p-0">N-gramは2以上かつ文字列の文字数以下で指定してください</p>
                <?php elseif (empty($_FILES["filename"]["name"]) && isset($_POST["read_file"])) : ?>
                    <p class="error col-12 col-sm-12 col-md-12 p-0">読み込むフォルダを指定してください</p>
                <?php endif; ?>
                <input type="submit" class="col-12 col-sm-12 col-md-12" id="read_file" name="read_file" value="読み込み">
            </div>
            
            <?php
                // 計算した類似度の表示
                $contents_cnt = count($contents);
                if ($contents_cnt >=  2) :
                    for ($i = 0; $i < $contents_cnt - 1; $i++) :
                        for ($j = $i + 1; $j < $contents_cnt; $j++) :
                            // 改ページ文字の削除
                            $contents[$i] = preg_replace("/\f/", "", $contents[$i]);

                            var_dump ($i);
                            var_dump ($j);

                            $contents[$j] = preg_replace("/\f/", "", $contents[$j]);
                            similar_text($contents[$i], $contents[$j], $perc);
                            get_ngram ($contents[$i], $_POST["ngram"], $substr1);
                            get_ngram ($contents[$j], $_POST["ngram"], $substr2);
                            similar_check ($substr1, $substr2, $perc);
                            // 有効数字
                            if ($perc < 10) {
                                $perc = round($perc, 2, PHP_ROUND_HALF_UP);
                            } elseif ($perc < 100) {
                                $perc = round ($perc, 1, PHP_ROUND_HALF_UP);
                            } elseif ($perc === 100) {
                                // そのまま
                            }
                            if ($perc > $_POST["threshold"]) {
                                echo "<p style='color: red;'><u>" . $i+1 . ". "  . $pdf_file_name[$i] . "</u>（上段）　と　<u>" . $j+1 . ". "  . $pdf_file_name[$j] . "</u>（下段）　の類似度：" . $perc . "%" . "</p>";
                            } else {
                                echo "<p><u>" . $i+1 . ". "  . $pdf_file_name[$i] . "</u>（上段）　と　<u>" . $j+1 . ". "  . $pdf_file_name[$j] . "</u>（下段）　の類似度：" . $perc . "%" . "</p>";
                            }
            ?>
                            <p>
                                <a class="" data-toggle="collapse" href="#collapseExample<?php echo $i * $contents_cnt + $j ?>" role="button" aria-expanded="false" aria-controls="collapseExample<?php echo $i * $contents_cnt + $j ?>">
                                    展開
                                </a>
                            </p>
                            <div class="collapse" id="collapseExample<?php echo $i * $contents_cnt + $j ?>">
                                <div class="card card-body">
                                    <div class="outputArea" id="outputArea1"><?php echo $contents[$i]; ?></div>
                                    <div class="outputArea" id="outputArea2"><?php echo $contents[$j]; ?></div>
                                </div>
                            </div>
            <?php
                        endfor;
                    endfor;
                endif;
            ?>

            <?php
                $endTime = microtime(true);
                echo floor(($endTime - $startTime)/60) . "分" . ($endTime - $startTime)%60 . "秒";
            ?>

        </div>

    </form>

    <!-- Bootstrap4 -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>