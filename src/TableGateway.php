<?php
namespace ngyuki\DoctrineTableGateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use OutOfBoundsException;

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
     * @return QueryBuilder
     */
    private function buildQuery()
    {
        /* @var $list TableGateway[] */
        $list = [];

        for ($t = $this; $t; $t = $t->delegate) {
            if ($t->scope) {
                $list[] = $t;
            }
        }

        $list = array_reverse($list);

        $q = $this->conn->createQueryBuilder();

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
                                $this->conn->quote($scope)
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

    private function scopeById($id)
    {
        if ($id === null) {
            return $this;
        }

        $ids = (array)$id;

        $keys = $this->metadata->getPrimaryKey($this->table);

        if (count($ids) !== count($keys)) {
            throw new \InvalidArgumentException();
        }

        $vars = array_combine($keys, $ids);

        return $this->scope($vars);
    }

    /**
     * @param mixed|array|null $id
     * @return mixed
     */
    public function find($id = null)
    {
        foreach ($this->scopeById($id)->all() as $row) {
            return $row;
        }

        throw new OutOfBoundsException("data not found");
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
        $query = $this->buildQuery()->select('*')->from($this->table);
        $stmt = $query->execute();
        return $this->createResultSet($stmt);
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    private function quotes(array $data)
    {
        $data = $data + $this->values;

        $columns = $this->metadata->getColumns($this->table);
        $data = array_intersect_key($data, array_flip($columns));
        $ret = [];
        foreach ($data as $key => $val) {
            $ret[$this->conn->quoteIdentifier($key)] = $this->conn->quote($val);
        }
        return $ret;
    }

    public function insert(array $data)
    {
        $data = $data + $this->values;

        $query = $this->buildQuery()->insert($this->table);
        $query->values($this->quotes($data));
        $query->execute();
    }

    public function update($id = null, array $data)
    {
        if ($id !== null) {
            $this->scopeById($id)->update(null, $data);
            return;
        }

        $query = $this->buildQuery()->update($this->table);

        foreach ($this->quotes($data) as $key => $val) {
            $query->set($key, $val);
        }

        $query->execute();
    }

    public function delete($id = null)
    {
        if ($id !== null) {
            $this->scopeById($id)->delete();
            return;
        }

        $query = $this->buildQuery()->delete($this->table);
        $query->execute();
    }
}
