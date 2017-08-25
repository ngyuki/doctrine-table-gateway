<?php
namespace ngyuki\DoctrineTableGateway;

class Expr
{
    /**
     * @var string
     */
    private $expr;

    public function __construct($expr)
    {
        $this->expr = $expr;
    }

    public function __toString()
    {
        return (string)$this->expr;
    }
}
