<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;

use function getopt;
use function implode;
use function in_array;
use function rtrim;
use function stream_get_contents;
use function stream_select;
use function var_export;

use const STDIN;

/**
 * CLI interface.
 */
class CLI
{
    public function __construct()
    {
        Context::load();
    }

    public function run(): int
    {
        $params = $this->getopt('', ['lint', 'highlight', 'tokenize']);
        if ($params !== false) {
            if (isset($params['lint'])) {
                return $this->runLint(false);
            }

            if (isset($params['highlight'])) {
                return $this->runHighlight(false);
            }

            if (isset($params['tokenize'])) {
                return $this->runTokenize(false);
            }
        }

        $this->usageLint(false);
        $this->usageHighlight(false);
        $this->usageTokenize(false);

        return 1;
    }

    /**
     * @param string[]|false[] $params
     * @param string[]         $longopts
     */
    public function mergeLongOpts(array &$params, array &$longopts): void
    {
        foreach ($longopts as $value) {
            $value = rtrim($value, ':');
            if (! isset($params[$value])) {
                continue;
            }

            $params[$value[0]] = $params[$value];
        }
    }

    public function usageHighlight(bool $isStandalone = true): void
    {
        $command = $isStandalone ? 'highlight-query' : 'sql-parser --highlight';

        echo 'Usage: ' . $command . ' --query SQL [--format html|cli|text] [--ansi]' . "\n";
        echo '       cat file.sql | ' . $command . "\n";
    }

    /**
     * @param string[] $long
     *
     * @return string[]|false[]|false
     */
    public function getopt(string $opt, array $long): array|false
    {
        return getopt($opt, $long);
    }

    /** @return string[]|false[]|false */
    public function parseHighlight(): array|false
    {
        $longopts = [
            'help',
            'query:',
            'format:',
            'ansi',
        ];
        $params = $this->getopt('hq:f:a', $longopts);
        if ($params === false) {
            return false;
        }

        $this->mergeLongOpts($params, $longopts);
        if (! isset($params['f'])) {
            $params['f'] = 'cli';
        }

        if (! in_array($params['f'], ['html', 'cli', 'text'])) {
            echo "ERROR: Invalid value for format!\n";

            return false;
        }

        return $params;
    }

    public function runHighlight(bool $isStandalone = true): int
    {
        $params = $this->parseHighlight();
        if ($params === false) {
            return 1;
        }

        if (isset($params['h'])) {
            $this->usageHighlight($isStandalone);

            return 0;
        }

        if (! isset($params['q'])) {
            $stdIn = $this->readStdin();

            if ($stdIn) {
                $params['q'] = $stdIn;
            }
        }

        if (isset($params['a'])) {
            Context::setMode(Context::SQL_MODE_ANSI_QUOTES);
        }

        if (isset($params['q'])) {
            echo Formatter::format(
                $params['q'],
                ['type' => $params['f']],
            );
            echo "\n";

            return 0;
        }

        echo "ERROR: Missing parameters!\n";
        $this->usageHighlight($isStandalone);

        return 1;
    }

    public function usageLint(bool $isStandalone = true): void
    {
        $command = $isStandalone ? 'lint-query' : 'sql-parser --lint';

        echo 'Usage: ' . $command . ' --query SQL [--ansi]' . "\n";
        echo '       cat file.sql | ' . $command . "\n";
    }

    /** @return string[]|false[]|false */
    public function parseLint(): array|false
    {
        $longopts = [
            'help',
            'query:',
            'context:',
            'ansi',
        ];
        $params = $this->getopt('hq:c:a', $longopts);
        if ($params === false) {
            return false;
        }

        $this->mergeLongOpts($params, $longopts);

        return $params;
    }

    public function runLint(bool $isStandalone = true): int
    {
        $params = $this->parseLint();
        if ($params === false) {
            return 1;
        }

        if (isset($params['h'])) {
            $this->usageLint($isStandalone);

            return 0;
        }

        if (isset($params['c'])) {
            Context::load($params['c']);
        }

        if (! isset($params['q'])) {
            $stdIn = $this->readStdin();

            if ($stdIn) {
                $params['q'] = $stdIn;
            }
        }

        if (isset($params['a'])) {
            Context::setMode(Context::SQL_MODE_ANSI_QUOTES);
        }

        if (isset($params['q'])) {
            $lexer = new Lexer($params['q'], false);
            $parser = new Parser($lexer->list);
            $errors = Error::get([$lexer, $parser]);
            if ($errors === []) {
                return 0;
            }

            $output = Error::format($errors);
            echo implode("\n", $output);
            echo "\n";

            return 10;
        }

        echo "ERROR: Missing parameters!\n";
        $this->usageLint($isStandalone);

        return 1;
    }

    public function usageTokenize(bool $isStandalone = true): void
    {
        $command = $isStandalone ? 'tokenize-query' : 'sql-parser --tokenize';

        echo 'Usage: ' . $command . ' --query SQL [--ansi]' . "\n";
        echo '       cat file.sql | ' . $command . "\n";
    }

    /** @return string[]|false[]|false */
    public function parseTokenize(): array|false
    {
        $longopts = [
            'help',
            'query:',
            'ansi',
        ];
        $params = $this->getopt('hq:a', $longopts);
        if ($params === false) {
            return false;
        }

        $this->mergeLongOpts($params, $longopts);

        return $params;
    }

    public function runTokenize(bool $isStandalone = true): int
    {
        $params = $this->parseTokenize();
        if ($params === false) {
            return 1;
        }

        if (isset($params['h'])) {
            $this->usageTokenize($isStandalone);

            return 0;
        }

        if (! isset($params['q'])) {
            $stdIn = $this->readStdin();

            if ($stdIn) {
                $params['q'] = $stdIn;
            }
        }

        if (isset($params['a'])) {
            Context::setMode(Context::SQL_MODE_ANSI_QUOTES);
        }

        if (isset($params['q'])) {
            $lexer = new Lexer($params['q'], false);
            foreach ($lexer->list->tokens as $idx => $token) {
                echo '[TOKEN ', $idx, "]\n";
                echo 'Type = ', $token->type->value, "\n";
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
        $this->usageTokenize($isStandalone);

        return 1;
    }

    public function readStdin(): string|false|null
    {
        $read = [STDIN];
        $write = [];
        $except = [];

        // Assume there's nothing to be read from STDIN.
        $stdin = null;

        // Try to read from STDIN.  Wait 0.2 second before timing out.
        $result = stream_select($read, $write, $except, 0, 2000);

        if ($result > 0) {
            $stdin = stream_get_contents(STDIN);
        }

        return $stdin;
    }
}
