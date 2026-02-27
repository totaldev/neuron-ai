<?php

declare(strict_types=1);

namespace NeuronAI\Providers;

use function array_map;
use function mb_convert_encoding;
use function preg_replace;

/**
 * Трейт для санитизации строк перед JSON encoding
 */
trait SanitizeTrait
{
    /**
     * Очищает строку от некорректных UTF-8 символов и управляющих символов
     */
    private function sanitizeString(string $value): string
    {
        return preg_replace(
            '/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/u',
            '',
            mb_convert_encoding($value, 'UTF-8', 'UTF-8')
        );
    }

    /**
     * Рекурсивно санитизирует данные для JSON
     */
    private function sanitizeForJson(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->sanitizeString($data);
        }
        
        if (is_array($data)) {
            return array_map($this->sanitizeForJson(...), $data);
        }
        
        if ($data instanceof \stdClass) {
            return $data;
        }
        
        // Handle objects by converting to array
        if (is_object($data)) {
            return $this->sanitizeForJson((array)$data);
        }
        
        return $data;
    }
}
