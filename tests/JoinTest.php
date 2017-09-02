<?php
namespace ngyuki\DoctrineTableGateway\Test;

use Doctrine\DBAL\Query\QueryBuilder;
use ngyuki\DoctrineTableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $conn = ConnectionManager::getConnection();

        $cols = ['id', 'name', 'aa', 'bb', 'cc'];
        $t = new TableGateway($conn, 't_user');
        $t->delete();
        $t->insert(array_combine($cols, [1, 'id1', 0, 0, 0]));
        $t->insert(array_combine($cols, [2, 'id2', 0, 0, 1]));
        $t->insert(array_combine($cols, [3, 'id3', 0, 1, 0]));
        $t->insert(array_combine($cols, [4, 'id4', 0, 1, 1]));
        $t->insert(array_combine($cols, [5, 'id5', 1, 0, 0]));
        $t->insert(array_combine($cols, [6, 'id6', 1, 0, 1]));
        $t->insert(array_combine($cols, [7, 'id7', 1, 1, 0]));
        $t->insert(array_combine($cols, [8, 'id8', 1, 1, 1]));

        $cols = ['user_id', 'post_id', 'title'];
        $t = new TableGateway($conn, 't_post');
        $t->delete();
        $t->insert(array_combine($cols, [1, 1, 'post1']));
        $t->insert(array_combine($cols, [1, 2, 'post2']));
        $t->insert(array_combine($cols, [1, 3, 'post3']));
    }

    private function getTableGateway()
    {
        $conn = ConnectionManager::getConnection();
        return new TableGateway($conn, 't_user');
    }

    /**
     * @test
     */
    function join()
    {
        $t = $this->getTableGateway()->scope(['id' => 1])->scope(function (QueryBuilder $q){
            return $q->join('t_user', 't_post', 'p', 't_user.id = p.user_id');
        });

        $res = $t->all()->toArray();

        assertCount(3, $res);
        assertThat($res[0]['title'], equalTo('post1'));
    }
}
