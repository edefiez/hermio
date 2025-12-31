<?php

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * JsonExtract DQL function for MySQL/MariaDB
 * Usage: JSON_EXTRACT(field, path)
 * Example: JSON_EXTRACT(c.content, '$.name')
 */
class JsonExtract extends FunctionNode
{
    public $field = null;
    public $path = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->field = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->path = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'JSON_EXTRACT(' .
            $this->field->dispatch($sqlWalker) . ', ' .
            $this->path->dispatch($sqlWalker) .
        ')';
    }
}

