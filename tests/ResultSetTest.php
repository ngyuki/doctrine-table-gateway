<?php
namespace ngyuki\DoctrineTableGateway\Test;

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
    public function as_column()
    {
        $t = $this->getTableGateway();

        $res = $t->scope('bb = 0')->all()->asColumn('name')->toArray();
        assertThat($res, equalTo([
            'id1',
            'id2',
            'id5',
            'id6',
        ]));

        $res = $t->scope('bb = 0')->all()->asColumn(['name'])->toArray();
        assertThat($res, equalTo([
            ['id1'],
            ['id2'],
            ['id5'],
            ['id6'],
        ]));


        $res = $t->scope('bb = 0')->all()->asColumn(1)->toArray();
        assertThat($res, equalTo([
            'id1',
            'id2',
            'id5',
            'id6',
        ]));


        $res = $t->scope('bb = 0')->all()->asColumn([1])->toArray();
        assertThat($res, equalTo([
            ['id1'],
            ['id2'],
            ['id5'],
            ['id6'],
        ]));
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
     * @test
     */
    public function as_group()
    {
        $t = $this->getTableGateway();

        $res = $t->all()->asGroup(['aa', 'id'], ['bb', 'name']);

        assertThat($res[0][1], equalTo([0, 'id1']));
        assertThat($res[1][7], equalTo([1, 'id7']));

        $res = $t->all()->asGroup([2, 0], [3, 1]);

        assertThat($res[0][1], equalTo([0, 'id1']));
        assertThat($res[1][7], equalTo([1, 'id7']));

        $res = $t->all()->asGroup(['aa', 'id'], 'name');

        assertThat($res[0][1], equalTo('id1'));
        assertThat($res[1][7], equalTo('id7'));

        $res = $t->all()->asGroup(['aa', 'id'], 1);

        assertThat($res[0][1], equalTo('id1'));
        assertThat($res[1][7], equalTo('id7'));

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
