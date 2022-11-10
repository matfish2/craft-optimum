<?php

namespace matfish\Optimum\twig;

use Craft;
use Twig\Compiler;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\Cookie;

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
//        $variant = $this->getOrSetExperimentCookie($experiment);


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
            'original' => 33,
            'red' => 33,
            'blue' => 34
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
$compiler->raw($funcs);
$compiler->raw('$variant = getOrSetExperimentCookie("'  . $experiment.'");');
//        $compiler->raw('$variant = "' . $variant . '";');

//        Yii::debug('GETTING VARIANT: ' . $variant);
//            $compiler->write(sprintf("function optimum_%s(\$context)\n", $experiment), "{\n");
        $compiler
            ->addDebugInfo($this);
        $compiler->raw("if (\$variant==='original'):");
//        if ($variant === 'original') {
            $compiler->subcompile($this->getNode('body'));
//        } else {
                $compiler->raw("else:");
//            if (\Craft::$app->view->doesTemplateExist($template)) {
//                $expr = new ConstantExpression($template, $this->getTemplateLine());
//                $node = new IncludeNode($expr, null, false, false, $this->getTemplateLine(), 'include');
//                $compiler->subcompile($node);
               $compiler->raw('$this->loadTemplate("optimum/' . $experiment  . '/{$variant}.twig", null, 13)->display($context);');
                $compiler->raw("endif;");

//            }
//            else {
//                throw new \Exception("Optimum: Template not found for variant '{$variant}' on experiment '{$experiment}'");
//            }
//        }

//        $compiler->write("}\n\n");
    }

    /**
     * getRandomWeightedElement()
     * Utility function for getting random values with weighting.
     * Pass in an associative array, such as array('A'=>5, 'B'=>45, 'C'=>50)
     * An array like this means that "A" has a 5% chance of being selected, "B" 45%, and "C" 50%.
     * The return value is the array key, A, B, or C in this case.  Note that the values assigned
     * do not have to be percentages.  The values are simply relative to each other.  If one value
     * weight was 2, and the other weight of 1, the value with the weight of 2 has about a 66%
     * chance of being selected.  Also note that weights should be integers.
     *
     * @param array $weightedValues
     * @throws \Exception
     */
    private function getRandomWeightedElement(array $weightedValues): string
    {
        $rand = random_int(1, (int)array_sum($weightedValues));

        foreach ($weightedValues as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }

        throw new \Exception("Optimum: Failed to randomize element");
    }

    /**
     * @throws InvalidConfigException
     */
    private function getOrSetExperimentCookie($experiment): string
    {
        $key = "optimum_{$experiment}";
        $cookie = \Craft::$app->request->cookies->get($key);

        if ($cookie) {
            return $cookie->value;
        }

        $vars = [
            'original' => 33,
            'red' => 33,
            'blue' => 34
        ];

        $randomVariant = $this->getRandomWeightedElement($vars);

        // Create cookie object.
        $cookie = Craft::createObject([
            'class' => Cookie::class,
            'name' => $key,
            'httpOnly' => true,
            'value' => $randomVariant,
            'expire' => time() + (86400 * 365),
        ]);

        Craft::$app->getResponse()->getCookies()->add($cookie);

        return $randomVariant;
    }
}