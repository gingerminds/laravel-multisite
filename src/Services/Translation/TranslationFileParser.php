<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Services\Translation;

use Gingerminds\LaravelMultisite\Exceptions\Translation\TranslationSourceException;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $rows = $this->readRows($xlsxPath);
        if ($rows === []) {
            return [];
        }

        $header = $this->normalizeHeader(array_shift($rows));

        $keyColumnIndex = array_search('key', $header, true);
        if ($keyColumnIndex === false) {
            throw new TranslationSourceException(
                'The translation xlsx file must have a "key" header column.'
            );
        }

        $localeColumns = $this->resolveLocaleColumns($header, $keyColumnIndex);

        return $this->buildTranslations($rows, $keyColumnIndex, $localeColumns);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $xlsxPath): array
    {
        $spreadsheet = IOFactory::load($xlsxPath);
        $sheet       = $spreadsheet->getActiveSheet();

        /** @var array<int, array<int, mixed>> $rows */
        $rows = $sheet->toArray(null, true, true, false);

        return $rows;
    }

    /**
     * @param array<int, mixed> $rawHeader
     *
     * @return array<int, string|null>
     */
    private function normalizeHeader(array $rawHeader): array
    {
        return array_map(
            static fn (mixed $value): ?string => is_string($value) ? trim(strtolower($value)) : null,
            $rawHeader
        );
    }

    /**
     * @param array<int, string|null> $header
     *
     * @return array<string, int> locale => column index
     */
    private function resolveLocaleColumns(array $header, int $keyColumnIndex): array
    {
        $localeColumns = [];
        foreach ($header as $columnIndex => $locale) {
            if ($columnIndex === $keyColumnIndex || $locale === null || $locale === '') {
                continue;
            }
            $localeColumns[$locale] = $columnIndex;
        }

        return $localeColumns;
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     * @param array<string, int>             $localeColumns
     *
     * @return array<string, array<string, string>>
     */
    private function buildTranslations(array $rows, int $keyColumnIndex, array $localeColumns): array
    {
        /** @var array<string, array<string, string>> $translations */
        $translations = array_fill_keys(array_keys($localeColumns), []);

        foreach ($rows as $row) {
            $key = $this->extractKey($row, $keyColumnIndex);
            if ($key === null) {
                continue;
            }

            foreach ($localeColumns as $locale => $columnIndex) {
                $value                       = $row[$columnIndex] ?? null;
                $translations[$locale][$key] = $value !== null ? (string) $value : '';
            }
        }

        return $translations;
    }

    /**
     * @param array<int, mixed> $row
     */
    private function extractKey(array $row, int $keyColumnIndex): ?string
    {
        $key = $row[$keyColumnIndex] ?? null;
        if (!is_string($key) || trim($key) === '') {
            return null;
        }

        return trim($key);
    }
}
