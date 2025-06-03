<?php

namespace NeuronAI\RAG\DataLoader;

use Spatie\PdfToText\Pdf;

/**
 * Requires pdftotext php extension.
 *
 * https://en.wikipedia.org/wiki/Pdftotext
 */
class PdfReader extends Pdf implements ReaderInterface
{
    public function setBinPath(string $binPath): self
    {
        $this->binPath = $binPath;
        return $this;
    }
}
