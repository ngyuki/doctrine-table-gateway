<?php
namespace ngyuki\DoctrineTableGateway\Test\Typed;

use ngyuki\DoctrineTableGateway\Test\ConnectionManager;
use PHPUnit\Framework\TestCase;

class TypedTest extends TestCase
{
    private function getTableGateway()
    {
        $conn = ConnectionManager::getConnection();
        return new TypedTable($conn);
    }

    /**
     * @test
     */
    function find()
    {
        $t = $this->getTableGateway();

        $res = $t->scope(['id' => 4])->find(4);

        assertThat($res->getId(), equalTo(4));
        assertThat($res, isInstanceOf(TypedRow::class));
    }

    /**
     * @test
     */
    function all()
    {
        $t = $this->getTableGateway();

        $res = $t->scope(['id' => 4])->all();

        assertThat($res->current()->getId(), equalTo(4));
        assertThat($res->current(), isInstanceOf(TypedRow::class));
    }

    /**
     * @test
     */
    function all_with_default_scope()
    {
        $t = $this->getTableGateway();

        $res = $t->all();

        assertThat($res->asColumn('id')->toArray(), equalTo([1, 2, 3, 4]));
    }

    /**
     * @test
     */
    function asUnique()
    {
        $t = $this->getTableGateway();

        $res = $t->scope(['id' => 4])->all()->asUnique('id');

        assertThat($res->key(), equalTo(4));
        assertThat($res->current()->getId(), equalTo(4));
        assertThat($res->current(), isInstanceOf(TypedRow::class));
    }
}
