<?php
namespace ngyuki\TableGateway\Test\Typed;

use ngyuki\TableGateway\ResultSet;

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
