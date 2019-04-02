<?php
/**
 * CLI interface.
 */
declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;

/**
 * CLI interface.
 *
 * @category   Exceptions
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class CLI
{
    public function mergeLongOpts(&$params, &$longopts)
    {
        foreach ($longopts as $value) {
            $value = rtrim($value, ':');
            if (isset($params[$value])) {
                $params[$value[0]] = $params[$value];
            }
        }
    }

    public function usageHighlight()
    {
        echo "Usage: highlight-query [--query SQL|--path path/to/my.sql] [--format html|cli|text]\n";
    }

    public function getopt($opt, $long)
    {
        return getopt($opt, $long);
    }

    public function parseHighlight()
    {
        $longopts = [
            'help',
            'query:',
            'path:',
            'format:',
        ];
        $params = $this->getopt(
            'hq:p:f:',
            $longopts
        );
        if ($params === false) {
            return false;
        }
        $this->mergeLongOpts($params, $longopts);
        if (! isset($params['f'])) {
            $params['f'] = 'cli';
        }
        if (isset($params['p']) && isset($params['q'])) {
            echo "ERROR: pass either 'path' or 'query'; but not both";

            return false;
        }
        if (isset($params['p'])) {
            if (!file_exists($params['p'])) {
                echo "ERROR: path ". $params['p'] ." doesn't exist";

                return false;
            } elseif (!is_readable($params['p'])) {
                echo "ERROR: path ". $params['p'] ." is not readable";

                return false;
            }
        }
        if (! in_array($params['f'], ['html', 'cli', 'text'])) {
            echo "ERROR: Invalid value for format!\n";

            return false;
        }

        return $params;
    }

    public function runHighlight()
    {
        $params = $this->parseHighlight();
        if ($params === false) {
            return 1;
        }
        if (isset($params['h'])) {
            $this->usageHighlight();

            return 0;
        }
        if (isset($params['p'])) {
            $params['q'] = file_get_contents($params['p']);
        }
        if (isset($params['q'])) {
            echo Formatter::format(
                $params['q'],
                ['type' => $params['f']]
            );
            echo "\n";

            return 0;
        }
        echo "ERROR: Missing parameters!\n";
        $this->usageHighlight();

        return 1;
    }

    public function usageLint()
    {
        echo "Usage: lint-query --query SQL\n";
    }

    public function parseLint()
    {
        $longopts = [
            'help',
            'query:',
            'context:',
        ];
        $params = $this->getopt(
            'hq:c:',
            $longopts
        );
        $this->mergeLongOpts($params, $longopts);

        return $params;
    }

    public function runLint()
    {
        $params = $this->parseLint();
        if ($params === false) {
            return 1;
        }
        if (isset($params['h'])) {
            $this->usageLint();

            return 0;
        }
        if (isset($params['c'])) {
            Context::load($params['c']);
        }
        if (isset($params['q'])) {
            $lexer = new Lexer($params['q'], false);
            $parser = new Parser($lexer->list);
            $errors = Error::get([$lexer, $parser]);
            if (count($errors) === 0) {
                return 0;
            }
            $output = Error::format($errors);
            echo implode("\n", $output);
            echo "\n";

            return 10;
        }
        echo "ERROR: Missing parameters!\n";
        $this->usageLint();

        return 1;
    }

    public function usageTokenize()
    {
        echo "Usage: tokenize-query --query SQL\n";
    }

    public function parseTokenize()
    {
        $longopts = [
            'help',
            'query:',
        ];
        $params = $this->getopt(
            'hq:',
            $longopts
        );
        $this->mergeLongOpts($params, $longopts);

        return $params;
    }

    public function runTokenize()
    {
        $params = $this->parseTokenize();
        if ($params === false) {
            return 1;
        }
        if (isset($params['h'])) {
            $this->usageTokenize();

            return 0;
        }
        if (isset($params['q'])) {
            $lexer = new Lexer($params['q'], false);
            foreach ($lexer->list->tokens as $idx => $token) {
                echo '[TOKEN ', $idx, "]\n";
                echo 'Type = ', $token->type, "\n";
                echo 'Flags = ', $token->flags, "\n";
                echo 'Value = ';
                var_export($token->value);
                echo "\n";
                echo 'Token = ';
                var_export($token->token);
                echo "\n";
                echo "\n";
            }

            return 0;
        }
        echo "ERROR: Missing parameters!\n";
        $this->usageTokenize();

        return 1;
    }
}
