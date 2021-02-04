<?php
    // session_start();

    // N-gramの取得
    function get_ngram($str, $n, &$substr) {
        $substr = [];
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
?>