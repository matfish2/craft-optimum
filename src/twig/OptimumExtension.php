<?php

namespace matfish\Optimum\twig;

use Twig\Extension\AbstractExtension;

class OptimumExtension extends AbstractExtension
{
    public function getTokenParsers() : array
    {
        return [
            new OptimumTokenParser()
        ];
   }

    public function getName(): string
    {
        return 'optimum';
    }
}