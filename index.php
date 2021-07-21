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
    // ini_set ("display_errors", 0);

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
        // chmod(__DIR__ . "/", 0777);
        $read_file_name = $_FILES["filename"]["name"];
        $read_file_tmp_name = $_FILES["filename"]["tmp_name"];
        // 読み込まれたファイルがzipファイルではなかったらアラートを出す
        if (mb_substr($read_file_name, -4, 4) !== ".zip") {
            $alert = "<script type='text/javascript'>alert('zipファイルを選択してください');</script>";
            echo $alert;
        } elseif ($zip->open($_FILES["filename"]["tmp_name"]) === TRUE) {
            
            if (!file_exists(__DIR__ . "/pdftotext_escape/")) {
                mkdir(__DIR__ . "/pdftotext_escape/");
                chmod(__DIR__ . "/pdftotext_escape/", 0777);
            }
            if (!file_exists(__DIR__ . "/pdftotext_escape2/")) {
                mkdir(__DIR__ . "/pdftotext_escape2/");
                chmod(__DIR__ . "/pdftotext_escape2/", 0777);
            }
            if (!file_exists(__DIR__ . "/pdftotext_escape3/")) {
                mkdir(__DIR__ . "/pdftotext_escape3/");
                chmod(__DIR__ . "/pdftotext_escape3/", 0777);
            }
            if (!file_exists(__DIR__ . "/pdftotext_escape4/")) {
                mkdir(__DIR__ . "/pdftotext_escape4/");
                chmod(__DIR__ . "/pdftotext_escape4/", 0777);
            }
            if (!file_exists(__DIR__ . "/pdftotext_escape5/")) {
                mkdir(__DIR__ . "/pdftotext_escape5/");
                chmod(__DIR__ . "/pdftotext_escape5/", 0777);
            }

            if (count(glob(__DIR__ . "/pdftotext_escape/*")) === 0) {
                $workdir = "/pdftotext_escape/";
            } elseif (count(glob(__DIR__ . "/pdftotext_escape2/*")) === 0) {
                $workdir = "/pdftotext_escape2/";
            } elseif (count(glob(__DIR__ . "/pdftotext_escape3/*")) === 0) {
                $workdir = "/pdftotext_escape3/";
            } elseif (count(glob(__DIR__ . "/pdftotext_escape4/*")) === 0) {
                $workdir = "/pdftotext_escape4/";
            } elseif (count(glob(__DIR__ . "/pdftotext_escape5/*")) === 0) {
                $workdir = "/pdftotext_escape5/";
            } else {
                $alert = "<script type='text/javascript'>alert('5人が同時にアクセスしています。少し時間を空けてください。');</script>";
                echo $alert;
                goto end;
            }
            // if (!file_exists(__DIR__ . "/pdftotext_escape/*")) {
            //     $workdir = "/pdftotext_escape/";
            // } elseif (!file_exists(__DIR__ . "/pdftotext_escape2/*")) {
            //     $workdir = "/pdftotext_escape2/";
            // } elseif (!file_exists(__DIR__ . "/pdftotext_escape3/*")) {
            //     $workdir = "/pdftotext_escape3/";
            // } elseif (!file_exists(__DIR__ . "/pdftotext_escape4/*")) {
            //     $workdir = "/pdftotext_escape4/";
            // } elseif (!file_exists(__DIR__ . "/pdftotext_escape5/*")) {
            //     $workdir = "/pdftotext_escape5/";
            // }

            for ($i=0; $i < $zip->numFiles; $i++) { 
                // ZipArchive::FL_ENC_RAW → 自動でエンコードされるのを防止するパラメタ
                $archive_file_name = $zip->getNameIndex($i, ZipArchive::FL_ENC_RAW);
                $enc_name = mb_convert_encoding($archive_file_name, "utf-8", "sjis");
                $enc_name_nospace = preg_replace("#[ \n\r\t　]+#um", "", $enc_name);
                $zip->renameIndex($i, $enc_name_nospace);
                $pdf_file_name[$i] = $enc_name_nospace;
            }
            $zip->extractTo(__DIR__ . $workdir, $pdf_file_name);
            $zip->close();

            array_map("unlink", glob("." . $workdir . "*.txt"));

            $pdf_files = glob(__DIR__ . $workdir . "*.pdf");
            for ($i=0; $i < count($pdf_files); $i++) { 
                // txtファイル変換後のファイル名
                $pdf_origin_name = explode ("/", $pdf_files[$i])[count(explode("/", $pdf_files[$i]))-1];
                $pdf_escape_name = "temporary_name.pdf";
                rename ($pdf_files[$i], __DIR__ . $workdir . $pdf_escape_name);
                $cmd = "pdftotext -enc Shift-JIS " . __DIR__ . $workdir . $pdf_escape_name . " " . __DIR__ . $workdir . "temporary_name.txt";
                exec($cmd, $dummy, $result);
                rename (__DIR__ . $workdir . $pdf_escape_name, $pdf_files[$i]);
                if ($result === 0) {
                    $test = glob("." . $workdir . "*txt");
                    // 文字化け
                    $txt_file_path = __DIR__ . $workdir . "temporary_name.txt";
                    $file_get_contents = file_get_contents($txt_file_path);
                    $str = mb_convert_encoding($file_get_contents,"utf-8","sjis"); // シフトJISからUTF-8に変換
                    $contents[$i] = $str;
                }
            }
            array_map("unlink", glob("." . $workdir . "*.txt"));
            array_map("unlink", glob("." . $workdir . "*.pdf"));
        }
    }
    end:
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- <meta charset="UTF-8"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>similar_check</title>
    <!-- Bootstrap4 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <!-- <link rel="stylesheet" href="./bootstrap/css/bootstrap.css"> -->
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
                if (!empty($contents)) {
                    $contents_cnt = count($contents);
                } else {
                    $contents_cnt = 1;
                }
                if ($contents_cnt >=  2) :
                    for ($i = 0; $i < $contents_cnt - 1; $i++) :
                        for ($j = $i + 1; $j < $contents_cnt; $j++) :
                            // 改ページ文字の削除
                            $contents[$i] = preg_replace("/\f/", "", $contents[$i]);
                            $contents[$j] = preg_replace("/\f/", "", $contents[$j]);
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
</div>

    <!-- Bootstrap4 -->
    <!-- <script src="./bootstrap/js/bootstrap.bundle.js"></script>
    <script src="./bootstrap/jquery.js"></script> -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>