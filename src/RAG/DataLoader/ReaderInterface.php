<?php

declare(strict_types=1);

namespace NeuronAI\RAG\DataLoader;

interface ReaderInterface
{
    public static function getText(string $filePath): string;
}
