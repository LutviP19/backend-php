<?php

namespace App\Neuron\Output;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;

class Tag
{
    #[SchemaProperty(description: 'The name of the tag', required: true)]
    #[NotBlank]
    public string $name;
}