<?php

namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class RandomFunction extends FunctionNode
{
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function dispatch(SqlWalker $sqlWalker): string
    {
        return 'RANDOM()';
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return $this->dispatch($sqlWalker);
    }
}
