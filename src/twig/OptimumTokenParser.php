<?php

namespace matfish\Optimum\twig;

use Twig\Error\SyntaxError;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class OptimumTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): OptimumNode
    {
        $lineno = $token->getLine();

        $stream = $this->parser->getStream();

        // recovers all inline parameters close to your tag name
        $params = $this->getInlineParams();

        $continue = true;
        while ($continue) {
            // create subtree until the decideMyTagFork() callback returns true
            $body = $this->parser->subparse(array($this, 'decideMyTagFork'));

            // I like to put a switch here, in case you need to add middle tags, such
            // as: {% mytag %}, {% nextmytag %}, {% endmytag %}.
            $tag = $stream->next()->getValue();

            $continue = match ($tag) {
                'endoptimum' => false,
                default => throw new SyntaxError(sprintf('Unexpected end of template. Twig was looking for the following tags "endoptimum" to close the "optimum" block started at line %d)', $lineno), -1),
            };

            // you want $body at the beginning of your arguments
            array_unshift($params, $body);

            // if your endoptimum can also contains params, you can uncomment this line:
            // $params = array_merge($params, $this->getInlineParams($token));
            // and comment this one:
            $stream->expect(Token::BLOCK_END_TYPE);
        }

        return new OptimumNode($params, $lineno, $this->getTag());
    }

    /**
     * Callback called at each tag name when subparsing, must return
     * true when the expected end tag is reached.
     *
     * @param Token $token
     * @return bool
     */
    public function decideMyTagFork(Token $token) : bool
    {
        return $token->test(['endoptimum']);
    }

    public function getTag() : string
    {
        return 'optimum';
    }

    /**
     * @throws SyntaxError
     */
    private function getInlineParams() : array
    {
        $stream = $this->parser->getStream();
        $params = array();
        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $params[] = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(Token::BLOCK_END_TYPE);

        return $params;
    }
}