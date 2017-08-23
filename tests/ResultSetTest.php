<?php
namespace ngyuki\DoctrineTableGateway\Test;

use Doctrine\DBAL\Query\QueryBuilder;
use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class ResultSetTest extends TestCase
{
    private function getTableGateway()
    {
        $conn = ConnectionManager::getConnection();
        return new TableGateway($conn, 't_user');
    }

    /**
     * @test
     */
    public function as_pair()
    {
        $t = $this->getTableGateway();
        $res = $t->scope('bb = 0')->all()->asPair('id', 'name')->toArray();

        assertThat($res, equalTo([
            1 => 'id1',
            2 => 'id2',
            5 => 'id5',
            6 => 'id6',
        ]));
    }

    /**
     * @test
     */
    public function as_unique()
    {
        $t = $this->getTableGateway();
        $res = $t->scope('bb = 0')->all()->asUnique('id')->toArray();

        assertThat(array_keys($res), equalTo([1, 2, 5, 6]));
        assertThat($res[1]['name'], equalTo('id1'));
    }

    /**
     * @test
     */
    public function as_unique_with_no_unique()
    {
        $t = $this->getTableGateway();

        $res = $t->scope('bb = 1')->all()->asUnique('aa');
        $arr = [];
        foreach ($res as $key => $val) {
            $arr[] = $key;
        }
        assertThat($arr, equalTo([0, 1]));
    }

    /**
     * イテレーターを立ち代わり入れ替わり呼ぶ
     *
     * as 系の next を呼ぶと親のイテレータも進む
     *
     * @test
     */
    public function iterator()
    {
        $t = $this->getTableGateway();
        $iterator = $t->all();

        assertEquals(1, $iterator->current()['id']);

        $asColumn = $iterator->asColumn('id');
        $asPair = $iterator->asPair('id', 'name');
        $asUnique = $iterator->asUnique('id');

        assertEquals(1, $iterator->current()['id']);
        assertEquals(1, $asColumn->current());
        assertEquals(1, $asPair->key());
        assertEquals(1, $asUnique->current()['id']);

        $iterator->next();

        assertEquals(2, $iterator->current()['id']);
        assertEquals(1, $asColumn->current());
        assertEquals(1, $asPair->key());
        assertEquals(1, $asUnique->current()['id']);

        $asColumn->next();

        assertEquals(3, $iterator->current()['id']);
        assertEquals(3, $asColumn->current());
        assertEquals(1, $asPair->key());
        assertEquals(1, $asUnique->current()['id']);

        $asPair->next();

        assertEquals(4, $iterator->current()['id']);
        assertEquals(3, $asColumn->current());
        assertEquals(4, $asPair->key());
        assertEquals(1, $asUnique->current()['id']);

        $asUnique->next();

        assertEquals(5, $iterator->current()['id']);
        assertEquals(3, $asColumn->current());
        assertEquals(4, $asPair->key());
        assertEquals(5, $asUnique->current()['id']);

        $iterator->next();

        assertEquals(6, $iterator->current()['id']);
        assertEquals(3, $asColumn->current());
        assertEquals(4, $asPair->key());
        assertEquals(5, $asUnique->current()['id']);

        ///

        assertCount(8, iterator_to_array($t->all()));
        assertCount(8, iterator_to_array($t->all()->asColumn('id')));
        assertCount(8, iterator_to_array($t->all()->asPair('id', 'name')));
        assertCount(8, iterator_to_array($t->all()->asUnique('id')));
    }
}
