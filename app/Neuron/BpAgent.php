<?php

declare(strict_types=1);

namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\ToolkitInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\Gemini\Gemini;


class BpAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        try {
            // return an instance of Anthropic, OpenAI, Gemini, Ollama, etc...
            // https://docs.neuron-ai.dev/the-basics/ai-provider
            return new Gemini(
                key: env('GEMINI_API_KEY'),
                model: env('GEMINI_MODEL'),
                parameters: [], // Add custom params (temperature, logprobs, etc)
                httpOptions: new HttpClientOptions(timeout: 30),
            );

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            exit;
        }
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ["You are a friendly AI Agent created with NeuronAI framework."],
        );
    }

    /**
     * @return ToolInterface[]|ToolkitInterface[]
     */
    protected function tools(): array
    {
        return [];
    }
}
