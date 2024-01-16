<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Components;

use PhpMyAdmin\SqlParser\Component;
use PhpMyAdmin\SqlParser\Parseable;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function is_array;

/**
 * Parses a function call.
 */
final class FunctionCall implements Component, Parseable
{
    /**
     * The name of this function.
     *
     * @var string|null
     */
    public $name;

    /**
     * The list of parameters.
     *
     * @var ArrayObj|null
     */
    public $parameters;

    /**
     * @param string|null            $name       the name of the function to be called
     * @param string[]|ArrayObj|null $parameters the parameters of this function
     */
    public function __construct(string|null $name = null, array|ArrayObj|null $parameters = null)
    {
        $this->name = $name;
        if (is_array($parameters)) {
            $this->parameters = new ArrayObj($parameters);
        } elseif ($parameters instanceof ArrayObj) {
            $this->parameters = $parameters;
        }
    }

    /**
     * @param Parser               $parser  the parser that serves as context
     * @param TokensList           $list    the list of tokens that are being parsed
     * @param array<string, mixed> $options parameters for parsing
     */
    public static function parse(Parser $parser, TokensList $list, array $options = []): FunctionCall
    {
        $ret = new static();

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ----------------------[ name ]-----------------------> 1
         *
         *      1 --------------------[ parameters ]-------------------> (END)
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === TokenType::Delimiter) {
                --$list->idx; // Let last token to previous one to avoid "This type of clause was previously parsed."
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === TokenType::Whitespace) || ($token->type === TokenType::Comment)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === TokenType::Operator && $token->value === '(') {
                    --$list->idx; // ArrayObj needs to start with `(`
                    $state = 1;
                    continue;// do not add this token to the name
                }

                $ret->name .= $token->value;
            } elseif ($state === 1) {
                    $ret->parameters = ArrayObj::parse($parser, $list);
                break;
            }
        }

        return $ret;
    }

    public function build(): string
    {
        return $this->name . $this->parameters;
    }

    public function __toString(): string
    {
        return $this->build();
    }
}
