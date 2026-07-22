<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Services\Translation;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Parses the front translations xlsx file.
 *
 * Expected layout: one header row with a "key" column followed by one
 * column per locale (e.g. key, fr, en, de, it), then one row per
 * translation key.
 */
class TranslationFileParser
{
    /**
     * @return array<string, array<string, string>> locale => [key => value]
     */
    public function parse(string $xlsxPath): array
    {
        $spreadsheet = IOFactory::load($xlsxPath);
        $sheet       = $spreadsheet->getActiveSheet();

        /** @var array<int, array<int, mixed>> $rows */
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            return [];
        }

        $header = array_map(
            static fn (mixed $value): ?string => is_string($value) ? trim(strtolower($value)) : null,
            array_shift($rows)
        );

        $keyColumnIndex = array_search('key', $header, true);
        if ($keyColumnIndex === false) {
            throw new RuntimeException(
                'The translation xlsx file must have a "key" header column.'
            );
        }

        $localeColumns = [];
        foreach ($header as $columnIndex => $locale) {
            if ($columnIndex === $keyColumnIndex || $locale === null || $locale === '') {
                continue;
            }
            $localeColumns[$locale] = $columnIndex;
        }

        /** @var array<string, array<string, string>> $translations */
        $translations = array_fill_keys(array_keys($localeColumns), []);

        foreach ($rows as $row) {
            $key = $row[$keyColumnIndex] ?? null;
            if (!is_string($key) || trim($key) === '') {
                continue;
            }
            $key = trim($key);

            foreach ($localeColumns as $locale => $columnIndex) {
                $value                       = $row[$columnIndex] ?? null;
                $translations[$locale][$key] = $value !== null ? (string) $value : '';
            }
        }

        return $translations;
    }
}
