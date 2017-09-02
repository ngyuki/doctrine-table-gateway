<?php
namespace ngyuki\DoctrineTableGateway\Query;

class Constraint
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    public function apply($expr)
    {
        return ($this->callable)($expr);
    }
}
