<?php

namespace NeuronAI\RAG\DataLoader;

interface ReaderInterface
{
    public static function getText(string $filePath): string;
}
