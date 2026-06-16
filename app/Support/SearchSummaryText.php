<?php

namespace App\Support;

class SearchSummaryText
{
    public static function limit(string $text, int $maxChars): string
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) <= $maxChars) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $maxChars);

        $lastSentence = max(
            mb_strrpos($truncated, '.') ?: -1,
            mb_strrpos($truncated, '!') ?: -1,
            mb_strrpos($truncated, '?') ?: -1,
        );

        if ($lastSentence >= (int) ($maxChars * 0.5)) {
            return trim(mb_substr($truncated, 0, $lastSentence + 1));
        }

        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            return trim(mb_substr($truncated, 0, $lastSpace));
        }

        return trim($truncated);
    }
}
