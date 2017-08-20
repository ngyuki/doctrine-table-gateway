<?php
namespace ngyuki\TableGateway;

use PDOStatement;
use Doctrine\DBAL\Driver\ResultStatement;

/**
 * テーブルゲートウェイの all() が返す結果セット
 *
 * 基本的には Statement オブジェクトをイテレーターにするためのラッパー
 */
class ResultSet implements \Iterator
{
    /**
     * @var ResultStatement|PDOStatement
     */
    private $statement;

    /**
     * @var \Iterator
     */
    private $iterator;

    /**
     * @param ResultStatement|PDOStatement|\Traversable $statement
     * @param array|\ArrayAccess|\ArrayObject|null $rowPrototype
     */
    public function __construct(\Traversable $statement, $rowPrototype = null)
    {
        $this->statement = $statement;

        if ($rowPrototype === null) {
            $this->iterator = new \IteratorIterator($statement);
            $this->iterator->rewind();
        } else {
            // @todo なぜか使用しているはずの変数で PhpStorm の警告がでる？
            if (is_array($rowPrototype)) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $callback = function ($arr) use ($rowPrototype) {
                    return array_replace($rowPrototype, $arr);
                };
            } elseif ($rowPrototype instanceof \ArrayObject) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $callback = function ($arr) use ($rowPrototype) {
                    $row = clone $rowPrototype;
                    $row->exchangeArray($arr);
                    return $row;
                };
            } elseif ($rowPrototype instanceof \ArrayAccess) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $callback = function ($arr) use ($rowPrototype) {
                    $row = clone $rowPrototype;
                    foreach ($arr as $name => $val) {
                        $row[$name] = $val;
                    }
                    return $row;
                };
            } else {
                throw new \InvalidArgumentException("invalid row prototype");
            }

            $this->iterator = (function () use ($callback) {
                foreach ($this->statement as $key => $arr) {
                    $row = $callback($arr);
                    yield $key => $row;
                }
            })();
        }
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->iterator->current();
    }

    public function next()
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }
}
