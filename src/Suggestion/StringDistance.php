<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Suggestion;

/**
 * String distance helpers + normalized score (0..1).
 */
final class StringDistance
{
    /** @psalm-pure */
    public static function distance(string $a, string $b, string $metric): int
    {
        return $metric === Distance::DAMERAU_LEVENSHTEIN
            ? self::damerauLevenshtein($a, $b)
            : \levenshtein($a, $b);
    }

    /** @psalm-pure */
    public static function normalizedScore(string $a, string $b, string $metric): float
    {
        $dist = self::distance($a, $b, $metric);
        $den  = \max(\strlen($a), \strlen($b), 1);
        $score = 1.0 - ($dist / $den);
        return $score < 0.0 ? 0.0 : ($score > 1.0 ? 1.0 : $score);
    }

    /**
     * Damerau–Levenshtein (OSA: erlaubt nur adjazente Transposition).
     * @psalm-pure
     */
    private static function damerauLevenshtein(string $a, string $b): int
    {
        $m = \strlen($a);
        $n = \strlen($b);
        if ($m === 0) {
            return $n;
        }
        if ($n === 0) {
            return $m;
        }

        $d = \array_fill(0, $m + 1, \array_fill(0, $n + 1, 0));
        for ($i = 0; $i <= $m; $i++) {
            $d[$i][0] = $i;
        }
        for ($j = 0; $j <= $n; $j++) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $m; $i++) {
            $ai = $a[$i - 1];
            for ($j = 1; $j <= $n; $j++) {
                $bj   = $b[$j - 1];
                $cost = ($ai === $bj) ? 0 : 1;

                $del = $d[$i - 1][$j] + 1;
                $ins = $d[$i][$j - 1] + 1;
                $sub = $d[$i - 1][$j - 1] + $cost;

                $val = \min($del, $ins, $sub);

                if ($i > 1 && $j > 1 && $ai === $b[$j - 2] && $a[$i - 2] === $bj) {
                    $val = \min($val, $d[$i - 2][$j - 2] + 1);
                }
                $d[$i][$j] = $val;
            }
        }
        return $d[$m][$n];
    }
}
