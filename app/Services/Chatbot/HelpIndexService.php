<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Cache;

/**
 * Lightweight, zero-infrastructure retrieval over the app's help content.
 * Loads the configured Markdown/Blade sources, splits them into heading-titled
 * chunks, and ranks them against a query by keyword overlap. No embeddings or
 * vector store — the corpus is small and this keeps the chatbot dependency-free.
 */
class HelpIndexService
{
    private const CHUNK_WORDS = 160;

    /** Common words that shouldn't influence ranking. */
    private const STOPWORDS = [
        'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'is', 'are',
        'how', 'do', 'i', 'we', 'my', 'our', 'can', 'what', 'where', 'when', 'with',
        'this', 'that', 'it', 'be', 'as', 'at', 'by', 'from', 'you', 'your',
    ];

    /**
     * Return the top matching help chunks for a query.
     *
     * @return array<int,array{module:string,title:string,text:string}>
     */
    public function search(string $query, int $limit = 4): array
    {
        $terms = $this->tokenize($query);

        if ($terms === []) {
            return [];
        }

        $scored = [];

        foreach ($this->index() as $chunk) {
            $haystack = strtolower($chunk['title'] . ' ' . $chunk['text']);
            $score = 0;

            foreach ($terms as $term) {
                $occurrences = substr_count($haystack, $term);
                if ($occurrences > 0) {
                    // Title hits weigh more than body hits.
                    $score += $occurrences + (str_contains(strtolower($chunk['title']), $term) ? 3 : 0);
                }
            }

            if ($score > 0) {
                $scored[] = ['score' => $score] + $chunk;
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(
            fn ($c) => ['module' => $c['module'], 'title' => $c['title'], 'text' => $c['text']],
            array_slice($scored, 0, $limit),
        );
    }

    /**
     * Build (and cache) the chunked index from all configured sources.
     *
     * @return array<int,array{module:string,title:string,text:string}>
     */
    public function index(): array
    {
        return Cache::remember('chatbot_help_index', (int) config('chatbot.help_cache_ttl', 3600), function () {
            $chunks = [];

            foreach ($this->resolveSources() as $module => $path) {
                if (! is_file($path)) {
                    continue;
                }

                $text = $this->toPlainText(file_get_contents($path), $path);

                foreach ($this->chunk($text) as $chunk) {
                    $chunks[] = ['module' => (string) $module] + $chunk;
                }
            }

            return $chunks;
        });
    }

    /**
     * Merge explicit sources with glob-discovered ones. Globs let new guides be
     * picked up automatically (drop a folder, no config edit) — the module label
     * is derived from the guide's directory name.
     *
     * @return array<string,string>  module label => absolute path
     */
    private function resolveSources(): array
    {
        $sources = [];

        foreach ((array) config('chatbot.help_sources', []) as $module => $relativePath) {
            $sources[(string) $module] = base_path($relativePath);
        }

        foreach ((array) config('chatbot.help_source_globs', []) as $pattern) {
            foreach (glob(base_path($pattern)) ?: [] as $path) {
                // e.g. ".../sweettooth-userguide/inventory-user-guide/index.html" → "Inventory User Guide"
                $label = ucwords(str_replace('-', ' ', basename(dirname($path))));
                $sources[$label] = $path;
            }
        }

        return $sources;
    }

    /** Clear the cached index (call after editing help sources). */
    public function flush(): void
    {
        Cache::forget('chatbot_help_index');
    }

    private function toPlainText(string $raw, string $path): string
    {
        $isBlade = str_ends_with($path, '.blade.php');
        $isHtml  = $isBlade || str_ends_with($path, '.html') || str_ends_with($path, '.htm');

        if ($isHtml) {
            // Drop script/style blocks entirely (their contents aren't help text).
            $raw = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $raw);

            if ($isBlade) {
                $raw = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@[a-zA-Z]+(\s*\(.*?\))?|<\?php.*?\?>/s', ' ', $raw);
            }

            // Turn block tags into line breaks so heading detection still works.
            $raw = preg_replace('/<\/?(h[1-6]|p|div|li|tr|br|section|header)\b[^>]*>/i', "\n", $raw);
            $raw = strip_tags($raw);
            $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5);
        }

        // Normalise whitespace but keep line breaks for heading detection.
        $raw = preg_replace('/[ \t]+/', ' ', $raw);
        $raw = preg_replace('/\n{3,}/', "\n\n", $raw);

        return trim($raw);
    }

    /**
     * Split text into ~CHUNK_WORDS chunks, tracking the nearest heading as title.
     *
     * @return array<int,array{title:string,text:string}>
     */
    private function chunk(string $text): array
    {
        $lines = explode("\n", $text);
        $chunks = [];
        $title = 'Overview';
        $buffer = [];
        $wordCount = 0;

        $flush = function () use (&$chunks, &$buffer, &$wordCount, &$title) {
            $body = trim(implode(' ', $buffer));
            if ($body !== '') {
                $chunks[] = ['title' => $title, 'text' => $body];
            }
            $buffer = [];
            $wordCount = 0;
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if ($this->isHeading($line)) {
                $flush();
                $title = trim(preg_replace('/^#+\s*|[*_`>]+/', '', $line));
                continue;
            }

            $buffer[] = $line;
            $wordCount += str_word_count($line);

            if ($wordCount >= self::CHUNK_WORDS) {
                $flush();
            }
        }

        $flush();

        return $chunks;
    }

    private function isHeading(string $line): bool
    {
        // Markdown heading, or a short title-ish line with no terminal punctuation.
        if (str_starts_with($line, '#')) {
            return true;
        }

        $words = str_word_count($line);

        return $words > 0 && $words <= 8 && ! preg_match('/[.:,]$/', $line) && strlen($line) < 70;
    }

    /** @return string[] */
    private function tokenize(string $text): array
    {
        preg_match_all('/[a-z0-9]+/', strtolower($text), $matches);

        return array_values(array_filter(
            array_unique($matches[0]),
            fn ($w) => strlen($w) > 2 && ! in_array($w, self::STOPWORDS, true),
        ));
    }
}
