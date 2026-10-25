<?php

namespace App\Modules\AiGateway;

use App\Shared\Arabic\ArabicText;

/**
 * Personal data never reaches the LLM (ADR-008, US-E10-04): phones, emails,
 * long digit runs (cards, IBANs) and the names we know are swapped for
 * tokens before the call, and swapped back in the reply.
 *
 * Tokens use letters, not digits, so the price validator never mistakes one
 * for a number.
 */
final class Pseudonymizer
{
    /** Nine digits or more, with the separators people type between them. */
    private const DIGIT_RUN = '/\+?\d(?:[\s\-.]?\d){8,}/u';

    private const EMAIL = '/[\p{L}\p{N}._%+\-]+@[\p{L}\p{N}.\-]+\.\p{L}{2,}/u';

    /** @var array<string, string> token => original */
    private array $map = [];

    /** @var array<string, int> */
    private array $counters = [];

    /**
     * @param  list<string>  $knownNames  e.g. the customer's name from the store
     */
    public function __construct(private readonly array $knownNames = []) {}

    public function pseudonymize(string $text): string
    {
        $text = (string) preg_replace_callback(self::EMAIL, fn (array $m) => $this->token('EMAIL', $m[0]), $text);

        // Arabic-Indic digits too: "٠٥١٢٣٤٥٦٧٨" is a phone like any other.
        $latin = ArabicText::digitsToLatin($text);
        $text = (string) preg_replace_callback(self::DIGIT_RUN, fn (array $m) => $this->token('PHONE', $m[0]), $latin);

        foreach ($this->knownNames as $name) {
            $name = trim($name);

            if (mb_strlen($name) < 2) {
                continue;
            }

            $text = (string) preg_replace_callback(
                '/'.preg_quote($name, '/').'/iu',
                fn (array $m) => $this->token('NAME', $m[0]),
                $text,
            );
        }

        return $text;
    }

    public function restore(string $text): string
    {
        return strtr($text, $this->map);
    }

    /** How many values were replaced (metadata for `ai_calls`). */
    public function count(): int
    {
        return count($this->map);
    }

    /**
     * @return list<string>
     */
    public function originals(): array
    {
        return array_values($this->map);
    }

    private function token(string $kind, string $value): string
    {
        $existing = array_search($value, $this->map, true);

        if (is_string($existing)) {
            return $existing;
        }

        $index = $this->counters[$kind] = ($this->counters[$kind] ?? 0) + 1;
        $token = '['.$kind.'_'.self::letters($index).']';
        $this->map[$token] = $value;

        return $token;
    }

    /** 1 → A, 26 → Z, 27 → AA. */
    private static function letters(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + $index % 26).$letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }
}
