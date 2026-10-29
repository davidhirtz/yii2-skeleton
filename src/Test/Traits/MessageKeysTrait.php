<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Traits;

/**
 * Asserts the `{DOMAIN}_{SUBJECT}_{KIND}` scheme of a category's keys and that every language holds the same ones.
 */
trait MessageKeysTrait
{
    protected static function assertMessageKeys(string $messagePath, string $category): void
    {
        $kinds = implode('|', [
            'BUTTON',
            'CONFIRM',
            'DESCRIPTION',
            'EMPTY',
            'ERROR',
            'FORMAT',
            'HINT',
            'LABEL',
            'LINK',
            'NAME',
            'PLACEHOLDER',
            'PLURAL',
            'PROMPT',
            'SINGULAR',
            'SUBJECT',
            'SUCCESS',
            'TEXT',
            'TITLE',
            'TOOLTIP',
            'TRAIL',
            'WARNING',
        ]);

        $files = glob("$messagePath/*/$category.php") ?: [];
        self::assertNotEmpty($files, "No message file for \"$category\" in $messagePath.");

        $expected = null;

        foreach ($files as $file) {
            /** @var array<string, string> $messages */
            $messages = require $file;
            $keys = array_keys($messages);
            $language = basename(dirname($file));

            $invalid = array_filter($keys, fn (string $key): bool => !preg_match("/^[A-Z][A-Z0-9]*(_[A-Z0-9]+)*_($kinds)$/", $key));
            self::assertSame([], array_values($invalid), "Keys of \"$category\" ($language) without a kind.");

            $empty = array_keys(array_filter($messages, fn (string $message): bool => $message === ''));
            self::assertSame([], $empty, "Untranslated keys of \"$category\" ($language).");

            sort($keys);
            $expected ??= $keys;

            self::assertSame($expected, $keys, "The keys of \"$category\" ($language) differ from the other languages.");
        }
    }
}
