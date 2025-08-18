<?php

namespace NeuronAI\Evaluation;

class Assertions
{
    /**
     * Assert that a string contains a substring
     */
    protected function assertContains(string $needle, string $haystack): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Assert that a string contains any of the provided keywords
     * @param array<string> $keywords
     */
    protected function assertContainsAny(array $keywords, string $haystack): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains(strtolower($haystack), strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Assert that a string contains all of the provided keywords
     * @param array<string> $keywords
     */
    protected function assertContainsAll(array $keywords, string $haystack): bool
    {
        foreach ($keywords as $keyword) {
            if (!str_contains(strtolower($haystack), strtolower($keyword))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assert that string length is between min and max
     */
    protected function assertLengthBetween(string $string, int $min, int $max): bool
    {
        $length = strlen($string);
        return $length >= $min && $length <= $max;
    }

    /**
     * Assert that response starts with expected string
     */
    protected function assertResponseStartsWith(string $expected, string $actual): bool
    {
        return str_starts_with($actual, $expected);
    }

    /**
     * Assert that response ends with expected string
     */
    protected function assertResponseEndsWith(string $expected, string $actual): bool
    {
        return str_ends_with($actual, $expected);
    }

    /**
     * Assert that two strings are equal (case-insensitive)
     */
    protected function assertEqualsIgnoreCase(string $expected, string $actual): bool
    {
        return strtolower(trim($expected)) === strtolower(trim($actual));
    }

    /**
     * Assert that string matches a regular expression
     */
    protected function assertMatchesRegex(string $pattern, string $subject): bool
    {
        return preg_match($pattern, $subject) === 1;
    }

    /**
     * Assert that response is not empty
     */
    protected function assertNotEmpty(string $response): bool
    {
        return !empty(trim($response));
    }

    /**
     * Assert that response is JSON
     */
    protected function assertIsJson(string $response): bool
    {
        json_decode($response);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
