<?php
namespace ngyuki\TableGateway\Test\Typed;

use ArrayObject;

class TypedRow extends ArrayObject
{
    public function getId()
    {
        return $this['id'];
    }

    public function getName()
    {
        return $this['name'];
    }
}
