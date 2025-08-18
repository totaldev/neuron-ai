<?php

declare(strict_types=1);

namespace NeuronAI\Evaluation\Runner;

use NeuronAI\Evaluation\Contracts\DatasetInterface;
use NeuronAI\Evaluation\Contracts\EvaluatorInterface;
use NeuronAI\Evaluation\Results\EvaluationResult;
use NeuronAI\Evaluation\Results\EvaluationSummary;
use Throwable;

class EvaluationRunner
{
    public function run(EvaluatorInterface $evaluator, DatasetInterface $dataset): EvaluationSummary
    {
        $data = $dataset->load();
        $results = [];
        $totalTime = 0.0;
        
        foreach ($data as $index => $item) {
            $startTime = microtime(true);
            $passed = false;
            $error = null;
            $output = null;
            
            try {
                $output = $evaluator->run($item);
                $passed = $evaluator->evaluate($output, $item);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
            
            $executionTime = microtime(true) - $startTime;
            $totalTime += $executionTime;
            
            $results[] = new EvaluationResult(
                $index,
                $passed,
                $item,
                $output,
                $executionTime,
                $error
            );
        }
        
        return new EvaluationSummary($results, $totalTime);
    }
}