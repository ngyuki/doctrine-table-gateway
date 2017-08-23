<?php
namespace ngyuki\DoctrineTableGateway;

use IteratorIterator;
use Traversable;

class ResultIterator extends IteratorIterator
{
    public function __construct(Traversable $iterator)
    {
        parent::__construct($iterator);
        $this->rewind();
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }
}
