<?php

declare(strict_types=1);

namespace NeuronAI\Tests\Stubs\Output123;

use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
use NeuronAI\Tests\Stubs\Address;

class Person
{
    #[NotBlank]
    public string $firstName;
    public string $lastName;

    public Address $address;

    /**
     * @var array<\NeuronAI\Tests\Stubs\Output123\Tag>
     */
    #[ArrayOf(Tag::class, allowEmpty: true)]
    public array $tags;
}
