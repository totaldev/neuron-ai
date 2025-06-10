<?php

declare(strict_types=1);

namespace NeuronAI\Workflow;

use NeuronAI\Chat\Messages\Message;
use NeuronAI\Exceptions\StateGraphError;
use NeuronAI\Observability\Events\WorkflowEnd;
use NeuronAI\Observability\Events\WorkflowNodeEnd;
use NeuronAI\Observability\Events\WorkflowNodeStart;
use NeuronAI\Observability\Events\WorkflowStart;
use NeuronAI\Observability\Observable;
use NeuronAI\StaticConstructor;
use SplSubject;

class Workflow implements SplSubject
{
    use StaticConstructor;
    use Observable;

    /** @var string[] */
    private array $executionList;

    /** @var array<string,Message[]> */
    private array $replies = [];

    /**
     * @throws StateGraphError
     */
    public function __construct(
        private readonly StateGraph $graph,
    ) {
        $this->executionList = $graph->compile();
    }

    /**
     * @throws StateGraphError
     */
    public function execute(Message|array $messages): Message
    {
        $lastReply = null;

        $this->notify('workflow-start', new WorkflowStart($this->executionList));

        foreach ($this->graph->getNodeNames() as $node) {
            $this->replies[$node] = [];
        }

        foreach ($this->executionList as $item) {
            $node = $this->graph->getNode($item);
            $input = $this->getPayload($item, $messages);

            $this->attachObservers($node);

            $this->notify('workflow-node-start', new WorkflowNodeStart($item, $input));

            $lastReply = $node->execute($input);
            $this->replies[$item] = [$lastReply];

            $this->notify('workflow-node-end', new WorkflowNodeEnd($item, $lastReply));
        }

        $this->notify('workflow-end', new WorkflowEnd($lastReply));

        return $lastReply;
    }

    /**
     * @throws StateGraphError
     */
    private function getPayload(string $node, Message|array $messages): array
    {
        // Always add the original query
        $input = is_array($messages) ? $messages : [$messages];

        // Add the replies of all the predecessors
        foreach ($this->graph->getPredecessors($node) as $predecessor) {
            $input = array_merge($input, $this->replies[$predecessor]);
        }

        return $input;
    }

    private function attachObservers(NodeInterface $node): void
    {
        foreach ($this->observers as $event => $observers) {
            foreach ($observers as $observer) {
                $node->observe($observer, $event);
            }
        }
    }
}
