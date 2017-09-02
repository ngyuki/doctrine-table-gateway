<?php
namespace ngyuki\DoctrineTableGateway\Query;

use Doctrine\DBAL\Connection;

trait ExpressionBuilderTrait
{
    /**
     * @return Connection
     */
    abstract public function getConnection();

    private function combine($type, ...$args)
    {
        $parts = [];

        foreach ((array)$args as $arr) {
            foreach ((array)$arr as $key => $val) {
                if (is_int($key)) {
                    if ($val !== null) {
                        $parts[] = $val;
                    }
                } else {
                    $parts[] = $this->equals($key, $val);
                }
            }
        }

        if (count($parts) == 0) {
            return null;
        }

        if (count($parts) == 1) {
            foreach ($parts as $part) {
                return "($part)";
            }
        }

        $parts = array_map(function ($part) { return "($part)"; }, $parts);
        $sql = implode(" $type ", $parts);

        return $sql;
    }

    /**
     * @param string|int|float|bool|Expr|null $value
     * @return string
     */
    public function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return (string)$this->getConnection()->getDatabasePlatform()->convertBooleansToDatabaseValue($value);
        }
        if ($value instanceof Expr) {
            return (string)$value;
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_float($value)) {
            return (string)$value;
        }
        return $this->getConnection()->quote($value);
    }

    public function equals($name, $value)
    {
        $name = $this->getConnection()->quoteIdentifier($name);

        if (is_null($value)) {
            return "$name IS NULL";
        }

        if (is_array($value)) {
            if (count($value) === 0) {
                return "$name IN (NULL)";
            }
            $value = array_map(function ($val) { return $this->quote($val); }, $value);
            return "$name IN ($value)";
        }

        $value = $this->quote($value);
        return "$name = $value";
    }

    public function quoteInto($expr, $value)
    {
        $index = 0;
        $value = (array)$value;
        return preg_replace_callback('/:(\w+)|(\?)/', function ($m) use ($value, &$index) {
            list (, $name) = $m;
            if (strlen($name)) {
                return $this->quote($value[$name]);
            } else {
                return $this->quote($value[$index++]);
            }
        }, $expr);
    }

    public function expr($expr)
    {
        return new Expr($expr);
    }

    public function andX(...$args)
    {
        return $this->combine('AND', ...$args);
    }

    public function orX(...$args)
    {
        return $this->combine('OR', ...$args);
    }
}
