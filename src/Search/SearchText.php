<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

final class SearchText
{
    /**
     * InnoDB ignores shorter tokens; `innodb_ft_min_token_size` is three on both MySQL and MariaDB defaults.
     */
    public const int MIN_TOKEN_LENGTH = 3;

    private const string BOOLEAN_OPERATORS = '+-<>()~*"@';

    /**
     * The collation folds `Muller` onto `Müller` on its own, nothing folds `Mueller`.
     */
    private const array TRANSLITERATIONS = [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss',
        'Ä' => 'Ae',
        'Ö' => 'Oe',
        'Ü' => 'Ue',
    ];

    public static function normalize(?string $text): string
    {
        // Without the space every `<p>a</p><p>b</p>` would be stripped into a single token.
        $text = str_replace('<', ' <', (string)$text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }

    public static function transliterate(string $text): string
    {
        $tokens = [];

        foreach (self::split($text) as $token) {
            if (preg_match('/[äöüßÄÖÜ]/u', $token)) {
                $tokens[] = strtr($token, self::TRANSLITERATIONS);
            }
        }

        return implode(' ', array_unique($tokens));
    }

    /**
     * @return list<string> the tokens InnoDB would index, with the boolean operators removed
     */
    public static function tokenize(?string $search): array
    {
        $search = strtr((string)$search, array_fill_keys(str_split(self::BOOLEAN_OPERATORS), ' '));
        $tokens = self::split($search);

        return array_values(array_filter(
            $tokens,
            static fn (string $token): bool => mb_strlen($token) >= self::MIN_TOKEN_LENGTH
        ));
    }

    /**
     * @param list<string> $tokens
     */
    public static function toBooleanQuery(array $tokens): string
    {
        return implode(' ', array_map(static fn (string $token): string => "+$token*", $tokens));
    }

    /**
     * @param list<string> $tokens
     */
    public static function snippet(string $text, array $tokens, int $length = 160): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $offset = 0;

        foreach ($tokens as $token) {
            $position = mb_stripos($text, $token);

            if ($position !== false) {
                $offset = max(0, $position - (int)($length / 4));
                break;
            }
        }

        $snippet = mb_substr($text, $offset, $length);

        return ($offset > 0 ? '… ' : '') . trim($snippet) . ' …';
    }

    /**
     * @return list<string>
     */
    private static function split(string $text): array
    {
        return preg_split('/[^\p{L}\p{N}_]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
