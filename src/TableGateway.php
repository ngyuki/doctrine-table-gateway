<?php
namespace ngyuki\DoctrineTableGateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;

class TableGateway
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var TableGateway
     */
    protected $delegate;

    /**
     * @var mixed
     */
    private $scope;

    /**
     * @var array
     */
    private $values = [];

    public function __construct(Connection $conn, $table, Metadata $metadata = null)
    {
        $this->conn = $conn;
        $this->table = $table;
        $this->metadata = $metadata ?: new Metadata($conn);
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param QueryBuilder|null $q
     * @return QueryBuilder
     */
    private function buildQuery(QueryBuilder $q = null)
    {
        /* @var $list TableGateway[] */
        $list = [];

        for ($t = $this; $t; $t = $t->delegate) {
            if ($t->scope) {
                $list[] = $t;
            }
        }

        $list = array_reverse($list);

        $q = $q ?? $this->conn->createQueryBuilder();

        foreach ($list as $t) {
            $old = $q->getQueryPart('where');
            $q->resetQueryPart('where');

            $ret = ($t->scope)($q);
            if ($ret) {
                $q = $ret;
            }

            $new = $q->getQueryPart('where');
            $q->resetQueryPart('where');

            if ($old) {
                $q->andWhere($old);
            }
            if ($new) {
                $q->andWhere($new);
            }
        }
        return $q;
    }

    /**
     * @param mixed      $scope
     * @param array|null $values
     *
     * @return $this
     */
    protected function setScope($scope, array $values = null)
    {
        if ($values === null) {
            if (is_array($scope)) {
                foreach ($scope as $key => $val) {
                    if (is_string($key)) {
                        $values[$key] = $val;
                    }
                }
            }
        }

        if ($scope instanceof \Closure === false) {
            $list = (array)$scope;
            $scope = function (QueryBuilder $q) use ($list) {
                foreach ($list as $key => $scope) {
                    if (is_string($key)) {
                        $q->andWhere(
                            $q->expr()->eq(
                                $this->conn->quoteIdentifier($key),
                                $this->quoteValue($scope)
                            )
                        );
                    } elseif (is_string($scope)) {
                        $q->andWhere($scope);
                    } else {
                        throw new \LogicException("invalid scope type");
                    }
                }
            };
        }

        $this->scope = $scope;

        if ($values) {
            $this->values = $values + $this->values;
        }
        return $this;
    }

    /**
     * @param mixed      $scope
     * @param array|null $values
     *
     * @return static
     */
    public function scope($scope, array $values = null)
    {
        if ($scope === null) {
            $obj = clone $this;
            $obj->delegate = null;
            $obj->scope = null;
            $obj->values = [];
            return $obj;
        }

        $obj = clone $this;
        $obj->delegate = $this;
        $obj->setScope($scope, $values);
        return $obj;
    }

    /**
     * @param string|array $select
     *
     * @return static
     */
    public function select($select)
    {
        return $this->scope(function (QueryBuilder $q) use ($select) {
            $q->select($select);
            return $q;
        });
    }

    /**
     * @param string      $sort
     * @param string|null $order
     *
     * @return static
     */
    public function orderBy($sort, $order = null)
    {
        return $this->scope(function (QueryBuilder $q) use ($sort, $order) {
            $q->orderBy($sort, $order);
            return $q;
        });
    }

    /**
     * 主キーでスコープを適用する
     *
     * @param mixed $id プライマリキーの値、複合主キーなら配列で指定する
     *
     * @return static
     */
    public function by($id)
    {
        $ids = (array)$id;

        $keys = $this->metadata->getPrimaryKey($this->table);

        if (count($ids) !== count($keys)) {
            throw new \InvalidArgumentException(
                sprintf("invalid id count ... %s primary key(%s), but actual %s keys",
                    $this->table, implode(', ', $keys), count($ids))
            );
        }

        $vars = array_combine($keys, $ids);

        return $this->scope($vars);
    }

    /**
     * @param mixed|array $id
     * @return mixed|null
     */
    public function find($id)
    {
        return $this->by($id)->all()->current() ?: null;
    }

    /**
     * @param ResultStatement|\PDOStatement|\Traversable $statement
     * @return ResultSet
     */
    protected function createResultSet(\Traversable $statement)
    {
        return new ResultSet($statement);
    }

    /**
     * @return ResultSet
     */
    public function all()
    {
        $query = $this->conn->createQueryBuilder()->select('*');
        $query = $this->buildQuery($query)->from($this->table);
        $stmt = $query->execute();
        return $this->createResultSet($stmt);
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    private function quoteValue($val)
    {
        if ($val === null) {
            return 'NULL';
        }
        if (is_object($val) && $val instanceof Expr) {
            return $val;
        }
        if (is_bool($val)) {
            return var_export($val, true);
        }
        if (is_float($val)) {
            return $val;
        }
        if (is_int($val)) {
            return $val;
        }
        return $this->conn->quote($val);
    }

    private function quotes(array $data)
    {
        $data = $data + $this->values;

        $columns = $this->metadata->getColumns($this->table);
        $data = array_intersect_key($data, array_flip($columns));
        $ret = [];
        foreach ($data as $key => $val) {
            $ret[$this->conn->quoteIdentifier($key)] = $this->quoteValue($val);
        }
        return $ret;
    }

    public function expr($expr)
    {
        return new Expr($expr);
    }

    public function insert(array $data)
    {
        $data = $data + $this->values;

        $query = $this->buildQuery()->insert($this->table);
        $query->values($this->quotes($data));
        $query->execute();
    }

    public function update(array $data)
    {
        $query = $this->buildQuery()->update($this->table);

        foreach ($this->quotes($data) as $key => $val) {
            $query->set($key, $val);
        }

        $query->execute();
    }

    public function delete()
    {
        $query = $this->buildQuery()->delete($this->table);
        $query->execute();
    }
}
