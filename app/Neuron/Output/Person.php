<?php

namespace App\Neuron\Output;


use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\NotBlank;
// use Symfony\Component\Validator\Constraints\NotBlank;
// use Symfony\Component\Validator\Constraints\Valid;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;

class Person 
{
    #[SchemaProperty(description: 'The user name.', required: true)]
    #[NotBlank]
    public string $name;
    
    #[SchemaProperty(description: 'What user love to eat.', required: true)]
    public string $preference;
    
    #[SchemaProperty(description: 'The address to complete the delivery.', required: true)]
    public Address $address;

    #[SchemaProperty(description: 'The list of tag for the user profile.', required: true)]
    public array $tags;
}
