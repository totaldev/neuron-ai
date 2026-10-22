<?php

declare(strict_types=1);

namespace NeuronAI\Tests\Stubs\StructuredOutput;

use NeuronAI\StructuredOutput\SchemaProperty;

/**
 * Schema stub for Agent\Agent::structuredAsync() tests.
 *
 * `categories` mimics the consumer's real schema (required list of
 * categories, ArrayOf validation commented out in the current version):
 * it must be a non-empty array in a valid response.
 */
class Summary
{
    /**
     * @var object[]
     */
    #[SchemaProperty(
        description: 'Classification of the dialog. At least one category.',
        required   : true,
    )]
    public array $categories = [];

    #[SchemaProperty(
        description: 'Short description of the dialog',
        required   : true,
    )]
    public string $summary;
}
