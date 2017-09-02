<?php
namespace ngyuki\DoctrineTableGateway\Test;

use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class ExprTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $conn = ConnectionManager::getConnection();
        $t = new TableGateway($conn, 't_user');
        $t->delete();
        $cols = ['id', 'name'];
        $t->insert(array_combine($cols, [1, 'xxx']));
        $t->insert(array_combine($cols, [100, 'abc']));
        $t->insert(array_combine($cols, [101, 'xxx']));
    }

    protected function setUp()
    {
        $conn = ConnectionManager::getConnection();
        $conn->beginTransaction();
    }

    protected function tearDown()
    {
        $conn = ConnectionManager::getConnection();
        $conn->rollBack();
    }

    private function getTableGateway()
    {
        $conn = ConnectionManager::getConnection();
        return new TableGateway($conn, 't_user');
    }

    /**
     * @test
     */
    function expr()
    {
        $t = $this->getTableGateway();

        $t = $t->scope([
            'name' => $t->expr("concat('a', 'b', 'c')")
        ]);

        $res = $t->all()->current();

        assertThat($res['id'], equalTo(100));
    }

    /**
     * @test
     */
    function into()
    {
        $t = $this->getTableGateway();

        $t = $t->scope([
            'name = ?' => $t->expr()->into('abc'),
        ]);

        $res = $t->all()->current();

        assertThat($res['id'], equalTo(100));
    }

    /**
     * @test
     */
    function quoteInto_()
    {
        $t = $this->getTableGateway();
        $t = $t->scope([
            $t->expr()->quoteInto('name = ?', 'abc')
        ]);

        assertThat($t->all()->current()['id'], equalTo(100));

        $t = $this->getTableGateway();
        $t = $t->scope([
            $t->expr()->quoteInto('name = :name', ['name' => 'abc'])
        ]);

        assertThat($t->all()->current()['id'], equalTo(100));
    }

    /**
     * @test
     */
    function likeTo()
    {
        $t = $this->getTableGateway();
        $t = $this->getTableGateway()->scope([
            'name' => $t->expr()->likeTo('b'),
        ]);
        assertThat($t->all()->asColumn()->current(), equalTo(100));

        $t = $this->getTableGateway();
        $t = $this->getTableGateway()->scope([
            'name' => $t->expr()->likeTo('d'),
        ]);
        assertThat($t->all()->current(), isNull());

        $t = $this->getTableGateway();
        $t = $this->getTableGateway()->scope([
            'name' => $t->expr()->likeToL('ab'),
        ]);
        assertThat($t->all()->asColumn()->current(), equalTo(100));

        $t = $this->getTableGateway();
        $t = $this->getTableGateway()->scope([
            'name' => $t->expr()->likeToL('bc'),
        ]);
        assertThat($t->all()->current(), isNull());

        $t = $this->getTableGateway();
        $t = $this->getTableGateway()->scope([
            'name' => $t->expr()->likeToR('bc'),
        ]);
        assertThat($t->all()->asColumn()->current(), equalTo(100));

        $t = $this->getTableGateway();
        $t = $this->getTableGateway()->scope([
            'name' => $t->expr()->likeToR('ab'),
        ]);
        assertThat($t->all()->current(), isNull());
    }

    /**
     * @test
     */
    function orX()
    {
        $t = $this->getTableGateway();

        $t = $t->scope([
            $t->expr()->orX(
                ['id' => 100],
                ['id' => 101]
            ),
        ]);

        $res = $t->all()->asColumn('id')->toArray();

        assertThat($res, equalTo([100, 101]));
    }

    /**
     * @test
     */
    function insert_expr()
    {
        $t = $this->getTableGateway();

        $t->insert([
            'id' => 9999,
            'name' => $t->expr("concat('a', 'z')")
        ]);

        $res = $t->find(9999);

        assertThat($res['name'], equalTo('az'));
    }

    /**
     * @test
     */
    function update_expr()
    {
        $t = $this->getTableGateway();

        $t->by(1)->update([
            'name' => $t->expr("concat('a', 'z')")
        ]);

        $res = $t->find(1);

        assertThat($res['name'], equalTo('az'));
    }
}
