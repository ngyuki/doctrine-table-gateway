<?php
namespace ngyuki\TableGateway\Test;

use Doctrine\DBAL\Query\QueryBuilder;
use ngyuki\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase
{
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
