<?php

namespace NeuronAI\Observability;

use NeuronAI\AgentInterface;
use NeuronAI\Observability\Events\ToolCalled;
use NeuronAI\Observability\Events\ToolCalling;
use NeuronAI\Observability\Events\ToolsBootstrapped;
use NeuronAI\Tools\ToolInterface;
use function array_key_exists;
use function array_map;

trait HandleToolEvents
{
    public function toolCalled(AgentInterface $agent, string $event, ToolCalled $data)
    {
        if (array_key_exists($data->tool->getName(), $this->segments)) {
            $this->segments[$data->tool->getName()]
                ->addContext('Tool', $data->tool->jsonSerialize())
                ->end();
        }
    }

    public function toolCalling(AgentInterface $agent, string $event, ToolCalling $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $this->segments[$data->tool->getName()] = $this->inspector
            ->startSegment(
                self::SEGMENT_TYPE . '-tool-call',
                "toolCall( {$data->tool->getName()} )"
            )
            ->setColor(self::SEGMENT_COLOR);
    }

    public function toolsBootstrapped(AgentInterface $agent, string $event, ToolsBootstrapped $data)
    {
        if (array_key_exists(get_class($agent) . '_tools_bootstrap', $this->segments)) {
            $this->segments[get_class($agent) . '_tools_bootstrap']
                ->addContext('Tools', array_map(fn(ToolInterface $tool) => $tool->getName(), $data->tools))
                ->end();
        }
    }

    public function toolsBootstrapping(AgentInterface $agent, string $event, mixed $data)
    {
        if (!$this->inspector->canAddSegments()) {
            return;
        }

        $this->segments[get_class($agent) . '_tools_bootstrap'] = $this->inspector
            ->startSegment(
                self::SEGMENT_TYPE . '-tools-bootstrap',
                "toolsBootstrap()"
            )
            ->setColor(self::SEGMENT_COLOR);
    }
}
