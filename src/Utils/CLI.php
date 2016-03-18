<?php

/**
 * CLI interface
 *
 * @package    SqlParser
 * @subpackage Utils
 */
namespace SqlParser\Utils;

/**
 * CLI interface
 *
 * @category   Exceptions
 * @package    SqlParser
 * @subpackage Utils
 * @author     Michal Čihař <michal@cihar.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CLI
{
    public function usage()
    {
        echo "Usage: highlight-query --query SQL [--format html|cli|text]\n";
    }

    public function mergeLongOpts(&$params, &$longopts)
    {
        foreach ($longopts as $value) {
            $value = rtrim($value, ':');
            if (isset($params[$value])) {
                $params[$value[0]] = $params[$value];
            }
        }
    }

    public function parseHighlight()
    {
        $longopts = array('help', 'query:', 'format:');
        $params = getopt(
            'hq:f:', $longopts
        );
        $this->mergeLongOpts($params, $longopts);
        if (! isset($params['f'])) {
            $params['f'] = 'cli';
        }
        if (! in_array($params['f'], array('html', 'cli', 'text'))) {
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
            $this->usage();
            return 0;
        }
        if (isset($params['q'])) {
            echo Formatter::format(
                $params['q'], array('type' => $params['f'])
            );
            echo "\n";
            return 0;
        }
        echo "ERROR: Missing parameters!\n";
        $this->usage();
        return 1;
    }
}
