<?php
namespace ngyuki\DoctrineTableGateway\Test\Typed;

use ngyuki\DoctrineTableGateway\ResultSet;

class TypedSet extends ResultSet
{
    /**
     * @return TypedRow
     */
    public function current()
    {
        return parent::current();
    }
}
