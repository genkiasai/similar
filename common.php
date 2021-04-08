<?php
    // session_start();

    /////////////////////////
    // N-gramを取得する処理 //
    /////////////////////////
    function get_ngram($str, $n, &$substr) {
        $substr = [];
        $str = preg_replace("#[ \n\t\r　]+#um", "", $str);
        $len = mb_strlen($str);
        // 文字数が0もしくはN数より少ない場合はfalseを返す
        if ($len <= 0 || $len < $n) return false;

        for ($i = 0; $i < $len; $i++) {
            $sub = mb_substr($str, $i, $n);
            if (mb_strlen($sub) == $n) {
                $substr[$i] = $sub;
            }
        }
        return count ($substr);
    }

    /////////////////////////////
    // 類似度をチェックする処理 //
    ///////////////////////////
    function similar_check ($str1, $str2, &$perc) {
        $cnt = count (array_intersect($str1, $str2));
        $cnt_str1 = count ($str1);
        $cnt_str2 = count ($str2);
        // $perc_subの計算結果が100を超えないようにする処理
        $cnt = min ($cnt_str1, $cnt_str2, $cnt);
        // $perc_sub = ($cnt / $n4)*100;
        $perc = (($cnt * 2) / (count ($str1) + count ($str2)))*100;
    }

    ////////////////////////////////////////////////////
    // 任意の文字数で文字列を分割して結果を配列で返す処理 //
    ///////////////////////////////////////////////////
    // $beforeStr:分割する文字列
    // $splitLen:任意の文字数
    // $afterStr:分割後の文字列配列
    function str_nsplit ($beforeStr, $splitLen, &$afterStr) {
        // 比較対象の読み込み
        $strLen = 0;       // 読み込んだテキストの文字数
        $div = 0;          // 読み込んだテキストを分割する文字数で割ったときの商
        $cutNum = 0;       // 分割する回数
        // ファイルの文字数を取得
        $strLen = mb_strlen($beforeStr);
        // ファイルの文字数を分割する文字数で割る
        // $div = gmp_div_qr($strLen, $splitLen);
        $div = floor($strLen / $splitLen);
        // 割り切れたら
        if ($div === 0) {
            // 商をそのまま使う
            $cutNum = $div;
        // 割り切れなかったら
        } else {
            // 商に1を足した数を分割する回数とする
            $cutNum = $div + 1;
        }
        // 分割して処理する
        for ($o=0; $o < $cutNum; $o++) { 
            // 先頭文字からの場合
            if ($o === 0) {
                // 分割
                $afterStr[$o] = mb_substr($beforeStr, $o * $splitLen, $splitLen);
            // 先頭文字からではない場合は直前の文字列の最後2文字からスタートする
            } else {
                // 分割
                $afterStr[$o] = mb_substr($beforeStr, $o * $splitLen - 2, $splitLen + 2);
            }
        }
    }

    ///////////////////////////////////////////
    // 一致した要素を赤文字で表示するための処理 //
    //////////////////////////////////////////
    // $intersect:N-gram同士で一致した要素の配列
    // $elementRed:赤文字にするべき要素を配列で渡す変数
    function matchStrRedChange ($intersect, &$elementRed) {
        // $elementNum：要素数のカウントアップ
        $elementNum = 0;
        // ひとつ前の要素のインデックス番号メモリ
        $previousElementIndex = 0;
        // インクリメント
        $i = 0;
        $continuous = [];       // 連続要素を記憶する変数
        $break = false;         // すべての要素をループしたかどうかのフラグ
        $continuousStr = "";    // 記憶しておいた連続要素を一つの文字列に連結する変数 
        $elementRed = [];      // 赤文字にすべき要素を格納する配列
        // すべての要素を回るまでループ
        while (true) {
            $element = $intersect[$i];
            // ループ回数番目のインデックスに要素があったら
            if (!empty($element) or $break) {
                $elementNum++;   // 要素数があったからカウントアップ
                if (($i - $previousElementIndex == 1) and !$break) { // もし連番の要素だったら
                    // ひとつ前の要素の先頭文字だけ取得
                    $continuous[count($continuous)] = mb_substr($intersect[$previousElementIndex], 0, 1);
                } elseif (($i - $previousElementIndex == 2) and !$break) { // もし一個飛ばしの要素だったら
                    // ひとつ前の要素の先頭2文字を取得
                    $continuous[count($continuous)] = mb_substr($intersect[$previousElementIndex], 0, 2);
                } elseif (($i - $previousElementIndex == 3) and !$break) { // もし二個飛ばしの要素だったら
                    // ひとつ前の要素の先頭3文字を取得
                    $continuous[count($continuous)] = mb_substr($intersect[$previousElementIndex], 0, 3);
                } elseif ($i != 0) {    // 赤文字の連続文字列じゃなくて最初の要素じゃなかったら
                        // 連続文字列の配列の要素をひとつの文字列にする
                        for ($k=0; $k < count($continuous); $k++) { 
                            $continuousStr = $continuousStr . $continuous[$k];
                        }
                        // 連続文字列の最後の要素は全文字結合
                        $elementRed[count($elementRed)] = $continuousStr . $intersect[$previousElementIndex];
                        // 変数初期化
                        $continuous = [];
                        $continuousStr = "";
                }
                // すべての要素をループしたかどうかのフラグが立っていたら
                if ($break) {
                    break;
                }
                // すべての要素をループしたらフラグを立てる
                if ($elementNum >= count($intersect)) {
                    $break = true;
                }
                // ループ回数番目のインデックスに要素があったことを記憶する
                $previousElementIndex = $i;
            }
            // ループ回数インクリメント
            $i++;
        }

        // 各要素の文字列にかぶりがあったら分解する処理
        $endFlug = true;
        while ($endFlug === true) {
            $compare = "";              // preg_splitの第一引数で使用するパターン
            $newElementRed = [];       // preg_splitで分割された要素のまとまり
            $deleteIndexMemory = [];    // preg_splitで引っかかった要素のインデックス番号記憶変数→あとで消すために記憶しておく
            $m = 0;                     // ループ回数カウンター
            $endFlug = false;           // while文を抜けるフラグ
            // 赤文字にする文字列の配列の要素の数だけループ
            for ($k=0; $k < count($elementRed); $k++) {
                // 比較する要素
                $compare = preg_quote($elementRed[$k], "/");
                $compare = preg_quote($compare, "\"");
                $compare = preg_quote($compare, "\'");
                // 比較する要素が""じゃなかったら 
                if ($compare != "") {
                    // 比較する要素を総当たり
                    for ($l=0; $l < count($elementRed); $l++) {
                        $split = [];    // 比較される要素に比較する要素の文字列が一致する文字列があったら
                        // 比較する要素と比較される要素がイコールじゃなかったら
                        if ($compare !== $elementRed[$l]) {
                            // 比較される要素の中に比較する要素の文字列が含まれていたら
                            if(strpos($elementRed[$l], $compare) !== false){ 
                                // 比較する要素で比較される要素を分解する
                                $split = preg_split("/$compare/", $elementRed[$l]);
                                // 要素に一致があったら削除するためにインデックス番号を記憶する
                                $deleteIndexMemory[$m] = $l;
                                // 新しく赤文字要素に追加するための分割された要素
                                if ($split !== false) {
                                    $newElementRed = array_merge($newElementRed, $split);
                                }
                                $endFlug = true;
                                $m++;
                            }
                        }
                    }
                }
            }

            //  同じ要素は削除する
            for ($k=0; $k < count($newElementRed); $k++) { 
                $diff = $newElementRed[$k];
                $newElementRed = array_diff($newElementRed, array($diff));
                $newElementRed = array_values($newElementRed);
                array_splice($newElementRed, $k, 0, $diff);
            }

            // 元の一致要素と分割した一致要素をひとつにまとめる
            $elementRed = array_merge($elementRed, $newElementRed);
            // 元の一致要素から分割された要素を削除してインデックスを詰める
            if (count($deleteIndexMemory) > 0) {
                for ($k=0; $k < count($deleteIndexMemory) ; $k++) { 
                    unset($elementRed[$deleteIndexMemory[$k]]);
                }
            }
            $elementRed = array_values($elementRed);
            
            //  同じ要素は削除する
            for ($k=0; $k < count($elementRed); $k++) { 
                $diff = $elementRed[$k];
                $elementRed = array_diff($elementRed, array($diff));
                $elementRed = array_values($elementRed);
                array_splice($elementRed, $k, 0, $diff);
            }
        }
    }

    /////////////////////
    // 赤文字にする処理 //
    ////////////////////
    // $elementRed:赤文字表示したい文字列配列
    // $contents:赤文字個所を変更する文字列
    function ChangeRedStr ($elementRed, &$contents) {
        for ($i=0; $i < count($elementRed); $i++) { 
            if ($elementRed != "") {
                //  $pattern = "#$elementRed[$k]+#um";
                $pattern = "#$elementRed[$i]#um";
                // $aaa = mb_strlen($elementRed[$k]);
                $replacement = "<span style=color:red>$elementRed[$i]</span>";
                //  $contents[$i] = preg_replace($pattern, $replacement, $contents[$i]);
                $contents = preg_replace($pattern, $replacement, $contents);
            }
        }
    }


        // //  同じ要素は削除する
        // for ($k=0; $k < count($elementRed); $k++) { 
        //     $diff = $elementRed[$k];
        //     $elementRed = array_diff($elementRed, array($diff));
        //     $elementRed = array_values($elementRed);
        //     array_splice($elementRed, $k, 0, $diff);
        // }



?>