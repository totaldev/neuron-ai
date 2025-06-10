<?php

namespace NeuronAI\Tests\stubs;

use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;

class Person
{
    #[NotBlank]
    public string $firstName;
    public string $lastName;

    public Address $address;

    /**
     * @var \NeuronAI\Tests\stubs\Tag[]
     */
    #[ArrayOf(Tag::class, allowEmpty: true)]
    public array $tags;
}
