<?php
namespace ngyuki\DoctrineTableGateway\Test;

use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class InsertUpdateDeleteTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $conn = ConnectionManager::getConnection();
        $t = new TableGateway($conn, 't_user');
        $t->delete();
        $cols = ['id', 'name', 'aa'];
        $t->insert(array_combine($cols, [1, 'aaa', 999]));
        $t->insert(array_combine($cols, [2, 'bbb', 999]));
        $t->insert(array_combine($cols, [3, 'ccc', 888]));
        $t->insert(array_combine($cols, [4, 'ddd', 888]));
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
    public function insert()
    {
        $t = $this->getTableGateway();

        $t->insert(['id' => 9999, 'name' => 'x x']);

        assertThat($t->lastInsertId(), equalTo(9999));
        assertThat($t->find(9999), logicalNot(isEmpty()));
    }

    /**
     * @test
     */
    public function insert_missing_column()
    {
        $t = $this->getTableGateway();
        $t->insert(['id' => 9999, 'xxx' => 9999]);

        assertThat($t->lastInsertId(), equalTo(9999));
        assertThat($t->find(9999), logicalNot(isEmpty()));
    }

    /**
     * @test
     */
    public function insert_scope()
    {
        $t = $this->getTableGateway()->scope(['aa' => 888]);
        $t->insert(['id' => 9999]);

        assertThat($t->find(9999)['aa'], equalTo(888));
    }

    /**
     * @test
     */
    public function insert_null()
    {
        $t = $this->getTableGateway();
        $t->insert(['id' => 9999, 'aa' => null]);

        assertThat($t->lastInsertId(), equalTo(9999));
        assertThat($t->find(9999), logicalNot(isEmpty()));
    }

    /**
     * @test
     */
    public function update()
    {
        $t = $this->getTableGateway();
        $t->by(2)->update(['name' => 'xxx']);

        $res = $t->all()->asColumn('name')->toArray();
        assertThat($res, equalTo([
            'aaa',
            'xxx',
            'ccc',
            'ddd',
        ]));
    }

    /**
     * @test
     */
    public function update_with_scope()
    {
        $t = $this->getTableGateway();
        $t->scope('aa = 999')->update(['name' => 'xxx']);

        $res = $t->all()->asColumn('name')->toArray();
        assertThat($res, equalTo([
            'xxx',
            'xxx',
            'ccc',
            'ddd',
        ]));
    }

    /**
     * @test
     */
    public function delete()
    {
        $t = $this->getTableGateway();
        $t->by(3)->delete();

        $res = $t->all()->asColumn('name')->toArray();
        assertThat($res, equalTo([
            'aaa',
            'bbb',
            'ddd',
        ]));
    }

    /**
     * @test
     */
    public function delete_with_scope()
    {
        $t = $this->getTableGateway();
        $t->scope('aa = 888')->delete();

        $res = $t->all()->asColumn('name')->toArray();
        assertThat($res, equalTo([
            'aaa',
            'bbb',
        ]));
    }
}
