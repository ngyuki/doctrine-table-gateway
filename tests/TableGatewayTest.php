<?php
namespace ngyuki\DoctrineTableGateway\Test;

use Doctrine\DBAL\Query\QueryBuilder;
use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class TableGatewayTest extends TestCase
{
    private function getTableGateway()
    {
        $conn = ConnectionManager::getConnection();
        return new TableGateway($conn, 't_user');
    }

    /**
     * @test
     */
    function find()
    {
        $t = $this->getTableGateway();

        $res = $t->find(5);
        assertThat($res['id'], equalTo(5));

        $res = $t->find(9999);
        assertThat($res, isNull());

        $res = $t->scope('aa = 1')->scope('bb = 0')->find(6);
        assertThat($res['id'], equalTo(6));

        $res = $t->scope('aa = 1')->scope('bb = 9999')->find(6);
        assertThat($res, isNull());
    }

    public function scope()
    {
        $t = $this->getTableGateway();
        
        $res = $t->scope('aa = 1')->scope('bb = 0')->all()->asColumn('id')->toArray();
        assertThat($res, equalTo([5, 6]));

        $res = $t->scope('aa = 1')->scope(function (QueryBuilder $q) { $q->orderBy('id', 'DESC'); })->all()->asColumn('id')->toArray();
        assertThat($res, equalTo([8, 7, 6, 5]));
    }

    /**
     * @test
     */
    function all()
    {
        $t = $this->getTableGateway();

        $res = $t->scope('aa = 1')->scope('bb = 0')->all()->toArray();
        $res = array_column($res, 'id');

        assertThat($res, equalTo([5, 6]));
    }

    /**
     * @test
     */
    function scope_with_or()
    {
        $t = $this->getTableGateway();

        $res = $t
            ->scope(function (QueryBuilder $q) { $q->andWhere('aa = 0'); })
            ->scope(function (QueryBuilder $q) { $q->orWhere('bb = 0')->orWhere('bb = 1'); })
            ->all()->toArray();
        $res = array_column($res, 'id');

        assertThat($res, equalTo([1, 2, 3, 4]));
    }

    /**
     * @test
     */
    public function orderBy()
    {
        $t = $this->getTableGateway();

        $res = $t
            ->scope('aa = 1')
            ->orderBy('id', 'ASC')
            ->orderBy('id', 'DESC')
            ->all()->asColumn('id')->toArray();

        assertThat($res, equalTo([8, 7, 6, 5]));
    }

    /**
     * @test
     */
    public function select()
    {
        $t = $this->getTableGateway();

        $res = $t->select(['id', 'name'])->find(1);

        assertThat(array_keys($res), equalTo(['id', 'name']));
    }

    /**
     * @test
     */
    function scope_null()
    {
        $t = $this->getTableGateway();

        $res = $t->scope('aa = 1')->all()->toArray();
        assertCount(4, $res);

        $res = $t->scope('aa = 1')->scope('bb = 1')->all()->toArray();
        assertCount(2, $res);

        $res = $t->scope('aa = 1')->scope('bb = 1')->scope(null)->all()->toArray();
        assertCount(8, $res);
    }
}
