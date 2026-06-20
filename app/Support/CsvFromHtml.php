<?php

namespace App\Support;

/**
 * Converts a rendered HTML <table> into rows for CSV output.
 *
 * The export Blade views (resources/views/exports/**) render a plain
 * <table><thead>/<tbody> with <th>/<td> cells. This mirrors what the old
 * maatwebsite/excel FromView CSV writer did, but with no external dependency
 * (maatwebsite/excel pins phpspreadsheet 1.x, which cannot run on PHP 8.5).
 */
class CsvFromHtml
{
    /**
     * Parse the first table in the given HTML into an array of string rows.
     *
     * @return array<int, array<int, string>>
     */
    public static function rows(string $html): array
    {
        $rows = [];

        if (trim($html) === '') {
            return $rows;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // The XML encoding hint keeps multibyte (UTF-8) cell values intact.
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $row = [];
            foreach ($tr->childNodes as $cell) {
                if (!($cell instanceof \DOMElement)) {
                    continue;
                }
                $tag = strtolower($cell->tagName);
                if ($tag !== 'td' && $tag !== 'th') {
                    continue;
                }
                $row[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Write rows to an open stream as CSV.
     *
     * The empty $escape argument is required: PHP 8.4+ deprecates the default
     * backslash escape, and RFC 4180 quoting (doubling) is what we want anyway.
     *
     * @param resource $stream
     * @param array<int, array<int, string>> $rows
     */
    public static function writeTo($stream, array $rows): void
    {
        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }
    }
}
