<?php

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Date DQL function for MySQL/MariaDB
 * Usage: DATE(field)
 * Example: DATE(cs.scannedAt)
 *
 * Extracts the date part from a datetime expression
 */
class Date extends FunctionNode
{
    public $dateExpression = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->dateExpression = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'DATE(' . $this->dateExpression->dispatch($sqlWalker) . ')';
    }
}

