<?php
namespace ngyuki\TableGateway\Test;

use Cache\Adapter\Doctrine\DoctrineCachePool;
use Doctrine\Common\Cache\ArrayCache;
use ngyuki\TableGateway\Metadata;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    /**
     * @test
     */
    function metadata()
    {
        $conn = ConnectionManager::getConnection();
        $arr = new ArrayCache();
        $cache = new DoctrineCachePool($arr);

        $m =  new Metadata($conn, $cache);

        assertEmpty($cache->get('t_user'));

        $columns = $m->getColumns('t_user');
        assertNotEmpty($columns);

        $keys = $m->getPrimaryKey('t_user');
        assertNotEmpty($keys);

        assertNotEmpty($cache->get('t_user'));
    }
}
