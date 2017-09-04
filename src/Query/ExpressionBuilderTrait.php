<?php
namespace ngyuki\DoctrineTableGateway\Query;

use Doctrine\DBAL\Connection;

trait ExpressionBuilderTrait
{
    /**
     * @return Connection
     */
    abstract public function getConnection();

    /**
     * @param string $type
     * @param array ...$args
     *
     * @return string
     */
    private function combine($type, ...$args)
    {
        $parts = [];

        foreach ((array)$args as $arr) {
            foreach ((array)$arr as $key => $val) {
                if ($val instanceof Constraint) {
                    if (is_int($key)) {
                        throw new \LogicException("constraint expression must be string array index");
                    }
                    $parts[] = $val->apply($key);
                } else {
                    if (is_int($key)) {
                        if ($val !== null) {
                            $parts[] = $val;
                        }
                    } else {
                        $parts[] = $this->eq($key, $val);
                    }
                }
            }
        }

        if (count($parts) == 0) {
            $val = $this->quote(true);
            return "($val)";
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
     * @param string|int|float|bool|array|Expr|null $value
     * @return string
     */
    public function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if ($value instanceof Expr) {
            return (string)$value;
        }
        if (is_bool($value)) {
            return (string)$this->getConnection()->getDatabasePlatform()->convertBooleansToDatabaseValue($value);
        }
        if (is_int($value)) {
            return (string)$value;
        }
        if (is_float($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            if (count($value) === 0) {
                return "(NULL)";
            } else {
                $value = array_map(
                    function ($val) {
                        return $this->quote($val);
                    },
                    $value
                );
                $value = implode(', ', $value);
                return "($value)";
            }
        }
        return $this->getConnection()->quote($value);
    }

    public function escapeLike($value)
    {
        return strtr($value, ['%' => '\\%', '_' => '\\_']);
    }

    /**
     * @param string|int|float|bool|array|Expr|null $value
     * @return string
     */
    private function eq($name, $value)
    {
        $name = $this->getConnection()->quoteIdentifier($name);
        $op = '=';

        if (is_null($value)) {
            $op = 'IS';
        }
        if (is_array($value)) {
            $op = 'IN';
        }

        $value = $this->quote($value);
        return "$name $op $value";
    }

    /**
     * quoteInto
     *
     * {code}
     *      $t = $t->scope([
     *          $t->expr()->quoteInto('name = ?', $value),
     *      ]);
     * {/code}
     *
     * @param string $expr
     * @param mixed  $value
     * @return string
     */
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

    /**
     * into
     *
     * {code}
     *      $t = $t->scope([
     *          'name = ?' => $t->expr()->into($value),
     *      ]);
     * {/code}
     *
     * @param $value
     * @return Constraint
     */
    public function into($value)
    {
        return new Constraint(function ($expr) use ($value) {
            return $this->quoteInto($expr, $value);
        });
    }

    public function like($name, $value, $cmp = 0)
    {
        $name = $this->getConnection()->quoteIdentifier($name);
        $value = $this->escapeLike($value);
        if ($cmp == 0) {
            $value = "%$value%";
        } elseif ($cmp == -1) {
            $value = "%$value";
        } else {
            $value = "$value%";
        }
        $value = $this->quote($value);
        return "$name LIKE $value";
    }

    public function equalTo($value)
    {
        return new Constraint(function ($expr) use ($value) {
            return $this->eq($expr, $value);
        });
    }

    /**
     * @param $value
     * @return Constraint
     */
    public function likeTo($value)
    {
        return new Constraint(function ($expr) use ($value) {
            return $this->like($expr, $value);
        });
    }

    /**
     * @param $value
     * @return Constraint
     */
    public function likeToL($value)
    {
        return new Constraint(function ($expr) use ($value) {
            return $this->like($expr, $value, 1);
        });
    }

    /**
     * @param $value
     * @return Constraint
     */
    public function likeToR($value)
    {
        return new Constraint(function ($expr) use ($value) {
            return $this->like($expr, $value, -1);
        });
    }

    /**
     * expr
     *
     * {code}
     *      $t = $t->scope([
     *          'date' => $t->expr('CURRENT_DATE'),
     *      ]);
     * {/code}
     *
     * @param string $expr
     * @return Expr
     */
    public function expr($expr)
    {
        return new Expr($expr);
    }

    /**
     * AND
     *
     * {code}
     *      $t = $t->scope([
     *          $t->expr()->andX([
     *              'id' => 1,
     *              'no' => 2,
     *          ]),
     *      ]);
     * {/code}
     *
     * @param array|string $args
     * @return string
     */
    public function andX($args)
    {
        return $this->combine('AND', ...func_get_args());
    }

    /**
     * OR
     *
     * {code}
     *      $t = $t->scope([
     *          $t->expr()->orX([
     *              'id' => 1,
     *              'no' => 2,
     *          ]),
     *      ]);
     * {/code}
     *
     * @param array|string $args
     * @return string
     */
    public function orX($args)
    {
        return $this->combine('OR', ...func_get_args());
    }
}
