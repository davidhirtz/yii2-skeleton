<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Search;

use Normalizer;

final class SearchText
{
    /**
     * InnoDB ignores shorter tokens; `innodb_ft_min_token_size` is three on both MySQL and MariaDB defaults.
     */
    public const int MIN_TOKEN_LENGTH = 3;

    /**
     * What InnoDB leaves out of the index, never which query terms it drops; `innodb_ft_server_stopword_table` replaces it.
     */
    public const array STOPWORDS = [
        'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i', 'in',
        'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'und', 'was', 'what', 'when', 'where',
        'who', 'will', 'with', 'www',
    ];

    /**
     * InnoDB indexes `__i`, since the underscore is a word character counting towards the minimum length; changing it
     * needs a `search/rebuild`.
     */
    public const string UNINDEXED_PREFIX = '__';

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

        return trim((string)preg_replace('/\s+/u', ' ', self::compose($text)));
    }

    /**
     * @return list<string>
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
     * The optional pair stays flat, since InnoDB ORs a parenthesised group without an operator with the whole query.
     *
     * @param list<string> $tokens
     */
    public static function toBooleanQuery(array $tokens): string
    {
        $hasRequiredTerm = array_filter($tokens, static fn (string $token): bool => !self::isStopword($token)) !== [];
        $terms = [];

        foreach ($tokens as $token) {
            $encoded = self::encode($token) . "* $token*";

            $terms[] = match (true) {
                $hasRequiredTerm && self::isStopword($token) => $encoded,
                self::isIndexed($token) => "+$token*",
                default => "+($encoded)",
            };
        }

        return implode(' ', $terms);
    }

    public static function isIndexed(string $token): bool
    {
        return mb_strlen($token) >= self::MIN_TOKEN_LENGTH && !self::isStopword($token);
    }

    public static function isStopword(string $token): bool
    {
        return in_array(mb_strtolower($token), self::STOPWORDS, true);
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
        return preg_split('/[^\p{L}\p{N}_]+/u', self::compose($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Text from macOS (file names, PDFs) arrives decomposed, and a combining mark is no letter: `Mu\u{308}ller`
     * would split in two.
     */
    private static function compose(string $text): string
    {
        return Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
    }
}
