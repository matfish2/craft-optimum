<?php

namespace matfish\Optimum\twig;

use matfish\Optimum\records\Experiment;
use Twig\Compiler;
use Twig\Node\Node;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class OptimumNode extends Node
{
    public function __construct($nodes, $lineno = 0, $tag = null)
    {
        parent::__construct(
            ['body' => $nodes[0]],
            [
                'experiment' => $nodes[1],
                'variant' => $nodes[2] ?? null
            ],
            $lineno, $tag);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function compile(Compiler $compiler): void
    {
        $experiment = $this->getAttribute('experiment')->getAttribute('value');

        $e = Experiment::find()->where("handle='$experiment'")->one();

        $explicitVariant = $this->getAttribute('variant');
        $explicitVariant = $explicitVariant ? $explicitVariant->getAttribute('value') : false;

        if (!$e) {
            throw new \Exception("Optimum: Unknown experiment {$experiment}");
        }

        $compiler
            ->addDebugInfo($this);

        $compiler
            ->raw("\$e{$experiment} = matfish\\Optimum\\records\\Experiment::find()->where(\"handle='{$experiment}'\")->one(); \n")
            ->raw('if (!isset($' . $experiment . 'variant)): $' . $experiment . "variant = \$e{$experiment}->getOrSetExperimentCookie();")
            ->raw("endif;\n\n")
            ->raw("\$active =\$e{$experiment}->isActive();\n\n");

        if ($explicitVariant) {
            // Only compile body for original variant when experiment is inactive OR if experiment is active and random variant is the explicit variant
            $compiler->raw("if ((\$active && \$" . $experiment . "variant==='$explicitVariant') || (!\$active && '$explicitVariant'==='original')):\n\n")
                ->subcompile($this->getNode('body'))
                ->raw("endif;");
        } else {
            $compiler->raw("if (\$" . $experiment . "variant==='original' || !\$active):\n\n")
                ->subcompile($this->getNode('body'))
                ->raw("else:")
                ->raw('$this->loadTemplate("_optimum/' . $experiment . '/{$' . $experiment . 'variant}.twig", null,' . $this->getTemplateLine() . ')->display($context);')
                ->raw("endif;");
        }
    }
}