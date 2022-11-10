<?php

namespace matfish\Optimum\twig;

use Twig\Compiler;
use Twig\Node\Node;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class OptimumNode extends Node
{
    public function __construct($nodes, $lineno = 0, $tag = null)
    {
        parent::__construct(['body' => $nodes[0]], ['experiment' => $nodes[1]], $lineno, $tag);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function compile(Compiler $compiler): void
    {
        $experiment = $this->getAttribute('experiment')->getAttribute('value');

        $funcs = <<<EOT
function getRandomWeightedElement(array \$weightedValues): string
    {
        \$rand = random_int(1, (int)array_sum(\$weightedValues));

        foreach (\$weightedValues as \$key => \$value) {
            \$rand -= \$value;
            if (\$rand <= 0) {
                return \$key;
            }
        }

        throw new \Exception("Optimum: Failed to randomize element");
    } 
     function getOrSetExperimentCookie(\$experiment): string
    {
        \$key = "optimum_{\$experiment}";
        \$cookie = \Craft::\$app->request->cookies->get(\$key);

        if (\$cookie) {
            return \$cookie->value;
        }

        \$vars = [
            'original' => 90,
            'red' => 5,
            'blue' => 5
        ];

        \$randomVariant = getRandomWeightedElement(\$vars);

        // Create cookie object.
        \$cookie = Craft::createObject([
            'class' => yii\web\Cookie::class,
            'name' => \$key,
            'httpOnly' => true,
            'value' => \$randomVariant,
            'expire' => time() + (86400 * 365),
        ]);

        Craft::\$app->getResponse()->getCookies()->add(\$cookie);

        return \$randomVariant;
    }    
EOT;

        $gaevent = 'echo "<script>gtag(\'event\',\'' .  $experiment . '\', {\"data\":\'" . $variant . "\'})</script>";';

        $compiler
            ->addDebugInfo($this)
            ->raw($funcs)
            ->raw('$variant = getOrSetExperimentCookie("' . $experiment . '");')
            ->raw($gaevent)
            ->raw("if (\$variant==='original'):")
            ->subcompile($this->getNode('body'))
            ->raw("else:")
            ->raw('$this->loadTemplate("optimum/' . $experiment . '/{$variant}.twig", null, $lineno)->display($context);')
            ->raw("endif;");
    }
}