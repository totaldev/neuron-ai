<?php

declare(strict_types=1);

namespace NeuronAI\Tests\Stubs\Tools;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\ToolProperty;

class TestToolClassWithoutParentConstructorMixed extends \NeuronAI\Tools\Tool
{
    protected string $name = 'test_tool';
    protected ?string $description = 'test tool';

    public function __construct(protected string $key)
    {
    }

    public function properties(): array
    {
        return [
            new ToolProperty(
                'url',
                PropertyType::STRING,
                'The URL to read.',
                true
            ),
        ];
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
