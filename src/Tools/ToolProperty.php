<?php

namespace NeuronAI\Tools;

use JsonSerializable;

class ToolProperty implements JsonSerializable
{
    public function __construct(
        protected string       $name,
        protected string|array $type,
        protected string       $description,
        protected bool         $required = false,
        protected array        $enum = [],
    ) {}

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getEnum(): array
    {
        return $this->enum;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string|array
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'type'        => $this->type,
            'enum'        => $this->enum,
            'required'    => $this->required,
        ];
    }
}
