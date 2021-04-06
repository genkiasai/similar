<?php
    // session_start();

    // N-gramの取得
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

    // N-gramの配列をもらう
    function similar_check ($str1, $str2, &$perc) {
        $cnt = count (array_intersect($str1, $str2));
        $cnt_str1 = count ($str1);
        $cnt_str2 = count ($str2);
        // $perc_subの計算結果が100を超えないようにする処理
        $cnt = min ($cnt_str1, $cnt_str2, $cnt);
        // $perc_sub = ($cnt / $n4)*100;
        $perc = (($cnt * 2) / (count ($str1) + count ($str2)))*100;
    }

    // 任意の文字数で文字列を分割して結果を配列で返す
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
?>