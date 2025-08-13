<?php

declare(strict_types=1);

namespace NeuronAI\Tools\Toolkits\Zep;

use NeuronAI\Tools\Toolkits\AbstractToolkit;

/**
 * @method static make(string $key, string $user_id)
 */
class ZepLongTermMemoryToolkit extends AbstractToolkit
{
    public function __construct(
        protected string $key,
        protected string $user_id,
    ) {
    }

    public function provide(): array
    {
        return [
            ZepSearchGraphTool::make($this->key, $this->user_id),
            ZepAddToGraphTool::make($this->key, $this->user_id),
        ];
    }
}
