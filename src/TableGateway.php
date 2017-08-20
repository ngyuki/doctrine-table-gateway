<?php
namespace ngyuki\TableGateway;

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
     * @param mixed $scope
     *
     * @return $this
     */
    protected function setScope($scope)
    {
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
        return $this;
    }

    /**
     * @param mixed $scope
     *
     * @return static
     */
    public function scope($scope)
    {
        $obj = clone $this;
        $obj->delegate = $this;
        $obj->setScope($scope);
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

    /**
     * @param mixed|array|null $id
     * @return mixed
     */
    public function find($id = null)
    {
        $t = $this;

        if ($id !== null) {
            $ids = (array)$id;

            $keys = $this->metadata->getPrimaryKey($this->table);

            if (count($ids) !== count($keys)) {
                throw new \InvalidArgumentException();
            }

            $vars = array_combine($keys, $ids);

            return $this->scope($vars)->find();
        }

        foreach ($t->all() as $row) {
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
}
