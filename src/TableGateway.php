<?php
namespace ngyuki\DoctrineTableGateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use ngyuki\DoctrineTableGateway\Query\Expr;
use ngyuki\DoctrineTableGateway\Query\ExpressionBuilder;

class TableGateway
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var ExpressionBuilder
     */
    protected $expr;

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
        $this->expr = new ExpressionBuilder($conn);
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
     * ExpressionBuilder を返す
     *
     * @param string|null $expr
     * @return Expr|ExpressionBuilder
     */
    public function expr($expr = null)
    {
        if ($expr === null) {
            return $this->expr;
        }
        return $this->expr->expr($expr);
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
        // $values が未指定なら $scope で key => value なやつだけ $values に使う
        // @todo $scope には insert に使えない値が入ることあるかも・・ scala/expr だけに限定？
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
            $where = $this->expr->andX($scope);
            $scope = function (QueryBuilder $q) use ($where) {
                $q->where($where);
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
            $q->addOrderBy($sort, $order);
            return $q;
        });
    }

    /**
     * @param int $limit
     *
     * @return static
     */
    public function limit($limit)
    {
        return $this->scope(function (QueryBuilder $q) use ($limit) {
            return $q->setMaxResults($limit);
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

    /**
     * SQL を直接実行する
     *
     * スコープは適用されず、指定した通りの SQL がそのまま実行されます
     *
     * @param string $sql
     * @param array $params
     *
     * @return ResultSet
     */
    public function query($sql, array $params = [])
    {
        $stmt = $this->getConnection()->executeQuery($sql, $params);
        return $this->createResultSet($stmt);
    }

    /**
     * コールバック関数をトランザクションの中で実行します
     *
     * 関数の開始時にトランザクションが開始され、関数の完了時にコミットされます
     * 関数の内部で例外が発生するとトランザクションは自動的にロールバックされます
     *
     * 関数の引数にはこの TableGateway のインスタンスが返します
     *
     * 関数の戻り値はそのままこの関数の戻り値として返ります
     *
     * @param callable $func トランザクションの中で実行するコールバック関数
     *
     * @return mixed コールバック関数が返した値
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function transactional(callable $func)
    {
        $conn = $this->getConnection();

        $conn->beginTransaction();
        try {
            $ret = $func($this);
            $conn->commit();
            return $ret;
        } catch (\Exception $ex) {
            $conn->rollback();
            throw $ex;
        } catch (\Throwable $ex) {
            $conn->rollback();
            throw $ex;
        }
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
            $ret[$this->conn->quoteIdentifier($key)] = $this->expr->quote($val);
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
