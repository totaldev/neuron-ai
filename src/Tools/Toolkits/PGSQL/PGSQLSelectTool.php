<?php

declare(strict_types=1);

namespace NeuronAI\Tools\Toolkits\PGSQL;

use InvalidArgumentException;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use PDO;

/**
 * @method static static make(PDO $pdo)
 */
class PGSQLSelectTool extends Tool
{
    /**
     * Patterns for write operations that should be blocked
     */
    protected array $forbiddenPatterns = [
        '/^\s*(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE)\s+/i',
        '/\b(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE)\s+/i',
        '/;\s*(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE|REPLACE)\s+/i',
        '/\bINTO\s+OUTFILE\s+/i',
        '/\bLOAD\s+DATA\s+/i',
        '/\bSET\s+/i',
        '/\bCALL\s+/i',
        '/\bEXEC(UTE)?\s+/i',
    ];

    // Allowed read-only statements
    protected array $allowedPatterns = [
        '/^\s*SELECT\s+/i',
        '/^\s*WITH\s+/i', // Common Table Expressions
        '/^\s*EXPLAIN\s+/i',
        '/^\s*SHOW\s+/i',
        '/^\s*DESCRIBE\s+/i',
        '/^\s*DESC\s+/i',
    ];

    public function __construct(protected PDO $pdo)
    {
        parent::__construct(
            'execute_select_query',
            'Use this tool only to run SELECT query against the PostgreSQL database.
This the tool to use only to gather information from the PostgreSQL database.'
        );
    }

    protected function properties(): array
    {
        return [
            new ToolProperty(
                'query',
                PropertyType::STRING,
                'The SELECT query you want to run against the database.',
                true
            )
        ];
    }

    public function __invoke(string $query): array
    {
        if (!$this->validateReadOnlyQuery($query)) {
            return [
                "error" => "The query was rejected for security reasons.
It looks like you are trying to run a write query using the read-only query tool."
            ];
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validates that the query is read-only
     *
     * @throws InvalidArgumentException if query contains write operations
     */
    protected function validateReadOnlyQuery(string $query): bool
    {
        if ($query === '') {
            return false;
        }

        // Remove comments to avoid false positives
        $cleanQuery = $this->removeComments($query);

        // Check if query starts with an allowed read operation
        $isAllowed = false;
        foreach ($this->allowedPatterns as $pattern) {
            if (\preg_match($pattern, $cleanQuery)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return false;
        }

        // Check for forbidden write operations
        foreach ($this->forbiddenPatterns as $pattern) {
            if (\preg_match($pattern, $cleanQuery)) {
                return false;
            }
        }

        // Additional security checks
        return $this->performAdditionalSecurityChecks($cleanQuery);
    }

    protected function removeComments(string $query): string
    {
        // Remove single-line comments (-- style)
        $query = \preg_replace('/--.*$/m', '', $query);

        // Remove multi-line comments (/* */ style)
        $query = \preg_replace('/\/\*.*?\*\//s', '', (string) $query);

        return $query;
    }

    protected function performAdditionalSecurityChecks(string $query): bool
    {
        // Check for semicolon followed by potential write operations
        if (\preg_match('/;\s*(?!$)/i', $query)) {
            // Multiple statements detected - need to validate each one
            $statements = $this->splitStatements($query);
            foreach ($statements as $statement) {
                if (\trim((string) $statement) !== '' && !$this->validateSingleStatement(\trim((string) $statement))) {
                    return false;
                }
            }
        }

        // Check for function calls that might modify data
        $dangerousFunctions = [
            'pg_exec',
            'pg_query',
            'system',
            'exec',
            'shell_exec',
            'passthru',
            'eval',
        ];

        foreach ($dangerousFunctions as $func) {
            if (\stripos($query, $func) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Split query into individual statements
     */
    protected function splitStatements(string $query): array
    {
        // Simple split on semicolons (this could be enhanced for more complex cases)
        return \array_filter(
            \array_map('trim', \explode(';', $query)),
            fn (string $stmt): bool => $stmt !== ''
        );
    }

    /**
     * Validate a single statement
     *
     * @return bool True if statement is valid read-only operation, false otherwise
     */
    protected function validateSingleStatement(string $statement): bool
    {
        $isAllowed = false;
        foreach ($this->allowedPatterns as $pattern) {
            if (\preg_match($pattern, $statement)) {
                $isAllowed = true;
                break;
            }
        }

        return $isAllowed;
    }
}
