<?php
namespace ngyuki\DoctrineTableGateway\Test;

use Doctrine\DBAL\Query\QueryBuilder;
use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class InsertUpdateDeleteTest extends TestCase
{
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
    public function update()
    {
        $t = $this->getTableGateway();

        $t->by(1)->update(['name' => 'xxx']);

        assertThat($t->find(1)['name'], equalTo('xxx'));
    }

    /**
     * @test
     */
    public function update_with_scope()
    {
        $t = $this->getTableGateway();

        $t->scope('aa = 1')->update(['name' => 'xxx']);

        $res = $t->all()->asColumn('name')->toArray();
        assertThat($res, equalTo([
            'id1',
            'id2',
            'id3',
            'id4',
            'xxx',
            'xxx',
            'xxx',
            'xxx',
        ]));
    }

    /**
     * @test
     */
    public function delete()
    {
        $t = $this->getTableGateway();

        $t->by(3)->delete();

        $res = $t->all()->asColumn('id')->toArray();
        assertThat($res, equalTo([1, 2, 4, 5, 6, 7, 8]));
    }

    /**
     * @test
     */
    public function delete_with_scope()
    {
        $t = $this->getTableGateway();

        $t->scope('aa = 1')->delete();

        $res = $t->all()->asColumn('id')->toArray();
        assertThat($res, equalTo([1, 2, 3, 4]));
    }
}
