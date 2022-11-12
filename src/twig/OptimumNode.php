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
        parent::__construct(['body' => $nodes[0]], ['experiment' => $nodes[1]], $lineno, $tag);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function compile(Compiler $compiler): void
    {
        $experiment = $this->getAttribute('experiment')->getAttribute('value');
        $e = Experiment::find()->where("handle='$experiment'")->one();

        if (!$e) {
            throw new \Exception("Optimum: Unknown experiment {$experiment}");
        }

        $variants = $e->getVariants()->all();

        $vars = [];
        $varsLookup = [];

        foreach ($variants as $variant) {
            $vars[$variant->handle] = $variant->weight;
            $varsLookup[$variant->handle] = $variant->name;
        }

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
     function getOrSetExperimentCookie(\$experiment, \$vars): string
    {
        \$testVar = Craft::\$app->request->getParam('optimum');
        
        if (\$testVar && in_array(\$testVar, array_keys(\$vars))) {
            return \$testVar;
        }
        
        \$key = "optimum_{\$experiment}";
        \$cookie = \Craft::\$app->request->cookies->get(\$key);

        if (\$cookie) {
            return \$cookie->value;
        }

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

        $gaevent = 'echo "<script>gtag(\'event\',\'' . $experiment . '\', {\"' . $experiment . '\":\'" . $variantLookup[$variant] . "\'});</script>";';
        $startCondition = (bool)$e->startAt ? " || \Carbon\Carbon::now() < \Carbon\Carbon::parse('$e->startAt')" : "";

        $compiler
            ->addDebugInfo($this)
            ->raw($funcs)
            ->raw('$variantLookup =')
            ->repr($varsLookup)
            ->raw(";\n\n")
            ->raw('$variant = getOrSetExperimentCookie("' . $experiment . '",')
            ->repr($vars)
            ->raw(");\n\n")
            ->raw("\$inactive = !$e->enabled || \Carbon\Carbon::now() > \Carbon\Carbon::parse('" . $e->endAt . "') $startCondition;")
            ->raw("if (!\$inactive): " . $gaevent . " endif;")
            ->raw("if (\$variant==='original' || \$inactive):\n\n")
            ->subcompile($this->getNode('body'))
            ->raw("else:")
            ->raw('$this->loadTemplate("_optimum/' . $experiment . '/{$variant}.twig", null,' . $this->getTemplateLine() . ')->display($context);')
            ->raw("endif;");
    }
}