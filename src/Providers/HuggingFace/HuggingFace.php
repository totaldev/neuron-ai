<?php

declare(strict_types=1);

namespace NeuronAI\Providers\HuggingFace;

use NeuronAI\Providers\OpenAI\OpenAI;

class HuggingFace extends OpenAI
{
    protected string $baseUri = 'https://router.huggingface.co/%s/v1';

    public function __construct(
        protected string            $key,
        protected string            $model,
        protected ?InferenceProvider $inferenceProvider = InferenceProvider::HF_INFERENCE,
        protected array             $parameters = [],
    ) {
        $this->buildBaseUri();
        parent::__construct($key, $model, $parameters);
    }

    private function buildBaseUri(): void
    {
        $endpoint = match ($this->inferenceProvider) {
            InferenceProvider::HF_INFERENCE => \trim($this->inferenceProvider->value, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$this->model,
            default => \trim($this->inferenceProvider->value, \DIRECTORY_SEPARATOR),
        };

        $this->baseUri = \sprintf($this->baseUri, $endpoint);
    }

}
