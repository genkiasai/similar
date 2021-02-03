<?php
    session_start();

    function get_ngram($str, $n, &$substr) {
        $len = mb_strlen($str);
        if ($len <= 0 || $len < $n) return false;

        for ($i = 0; $i < $len; $i++) {
            $sub = mb_substr($str, $i, $n);
            if (mb_strlen($sub) == $n) {
                $substr[$i] = $sub;
            }
        }
        return count ($substr);
    }

?>