<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

final class SearchText
{
    /**
     * InnoDB ignores shorter tokens; `innodb_ft_min_token_size` is three on both MySQL and MariaDB defaults.
     */
    public const int MIN_TOKEN_LENGTH = 3;

    /**
     * InnoDB's built-in stopword list. Together with `MIN_TOKEN_LENGTH` it says which tokens InnoDB leaves out
     * of the index — never which query terms are dropped, since a prefix term is always matched literally. A
     * project that sets `innodb_ft_server_stopword_table` replaces this list too.
     */
    public const array STOPWORDS = [
        'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i', 'in',
        'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'und', 'was', 'what', 'when', 'where',
        'who', 'will', 'with', 'www',
    ];

    /**
     * Prefixed to a token InnoDB would not index, so the index carries a form of it that InnoDB keeps. Three
     * characters at worst, and no stopword starts with it. Changing it needs a `search/rebuild`.
     */
    public const string UNINDEXED_PREFIX = 'zz';

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

    /**
     * @return list<string> every word of the search, with the boolean operators removed
     */
    public static function tokenize(?string $search): array
    {
        $search = strtr((string)$search, array_fill_keys(str_split(self::BOOLEAN_OPERATORS), ' '));
        return self::split($search);
    }

    /**
     * The extra tokens the index needs beside the readable text: the German transliteration of every umlaut
     * token, and a prefixed copy of every token InnoDB would leave out of the index.
     */
    public static function getIndexTokens(string $text): string
    {
        $tokens = [];

        foreach (self::split($text) as $token) {
            $forms = [$token];

            if (preg_match('/[äöüßÄÖÜ]/u', $token)) {
                $forms[] = $transliterated = strtr($token, self::TRANSLITERATIONS);
                $tokens[] = $transliterated;
            }

            foreach ($forms as $form) {
                if (!self::isIndexed($form)) {
                    $tokens[] = self::encode($form);
                }
            }
        }

        return implode(' ', array_unique($tokens));
    }

    /**
     * A token InnoDB indexes is asked for as an ordinary prefix. One it does not is asked for as either its
     * prefixed copy or the plain prefix, so `com` finds both `domain.com` and `commerce`.
     *
     * @param list<string> $tokens
     */
    public static function toBooleanQuery(array $tokens): string
    {
        $terms = array_map(
            static fn (string $token): string => self::isIndexed($token)
                ? "+$token*"
                : '+(' . self::encode($token) . "* $token*)",
            $tokens
        );

        return implode(' ', $terms);
    }

    public static function isIndexed(string $token): bool
    {
        return mb_strlen($token) >= self::MIN_TOKEN_LENGTH
            && !in_array(mb_strtolower($token), self::STOPWORDS, true);
    }

    public static function encode(string $token): string
    {
        return self::UNINDEXED_PREFIX . mb_strtolower($token);
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
