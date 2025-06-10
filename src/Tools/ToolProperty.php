<?php

namespace NeuronAI\Tools;

class ToolProperty implements ToolPropertyInterface
{
    public function __construct(
        protected string $name,
        protected PropertyType $type,
        protected string $description,
        protected bool $required = false,
        protected array $enum = [],
    ) {
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'enum' => $this->enum,
            'required' => $this->required,
        ];
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): PropertyType
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getEnum(): array
    {
        return $this->enum;
    }

    public function getJsonSchema(): array
    {
        $schema = [
            'type' => $this->type->value,
            'description' => $this->description,
        ];

        if (!empty($this->enum)) {
            $schema['enum'] = $this->enum;
        }

        return $schema;
    }
}
