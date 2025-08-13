<?php

declare(strict_types=1);

namespace NeuronAI\StructuredOutput\Validation\Rules;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class LowerThanEqual extends AbstractValidationRule
{
    public function __construct(protected mixed $reference)
    {
    }

    public function validate(string $name, mixed $value, array &$violations): void
    {
        if (\is_null($value) || $value > $this->reference) {
            $violations[] = $this->buildMessage($name, 'must be greater than {compare}', ['compare' => \get_debug_type($this->reference)]);
        }
    }
}
