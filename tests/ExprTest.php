<?php
namespace ngyuki\DoctrineTableGateway\Test;

use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class ExprTest extends TestCase
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
    function scope_expr()
    {
        $t = $this->getTableGateway();

        $t = $t->scope([
            'name' => $t->expr("concat('id', 5)")
        ]);

        $res = $t->all()->current();

        assertThat($res['id'], equalTo(5));
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
