<?php

namespace NeuronAI\RAG\DataLoader;

use Html2Text\Html2Text;

class HtmlReader implements ReaderInterface
{
    /**
     * @param string $filePath It can be a URL or a local file path
     */
    public static function getText(string $filePath, array $options = []): string
    {
        $html = new Html2Text(\file_get_contents($filePath));

        return $html->getText();
    }
}
