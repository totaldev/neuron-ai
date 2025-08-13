<?php

declare(strict_types=1);

namespace NeuronAI\Workflow\Exporter;

use NeuronAI\Workflow\Workflow;
use ReflectionClass;

class MermaidExporter implements ExporterInterface
{
    public function export(Workflow $graph): string
    {
        $output = "graph TD\n";

        foreach ($graph->getEdges() as $edge) {
            $from = $this->getShortClassName($edge->getFrom());
            $to = $this->getShortClassName($edge->getTo());

            $output .= "    {$from} --> {$to}\n";
        }

        return $output;
    }

    private function getShortClassName(string $class): string
    {
        $reflection = new ReflectionClass($class);
        return $reflection->getShortName();
    }
}
