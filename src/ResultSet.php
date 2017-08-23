<?php
namespace ngyuki\DoctrineTableGateway;

class ResultSet extends ResultIterator
{
    /**
     * @param string $column
     *
     * @return ResultIterator
     */
    public function asColumn($column)
    {
        return new ResultIterator((function () use ($column) {
            foreach ($this as $key => $arr) {
                yield $key => $arr[$column];
            }
        })());
    }

    /**
     * @param string $column
     *
     * @return static
     */
    public function asUnique($column)
    {
        return new static((function () use ($column) {
            $prev = null;
            foreach ($this as $arr) {
                $key = $arr[$column];
                if ($prev !== $key) {
                    $prev = $key;
                    yield $key => $arr;
                }
            }
        })());
    }

    /**
     * @param string $key
     * @param string $val
     *
     * @return ResultIterator
     */
    public function asPair($key, $val)
    {
        return $this->asUnique($key)->asColumn($val);
    }
}
