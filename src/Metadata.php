<?php
namespace ngyuki\TableGateway;

use Doctrine\DBAL\Connection;
use Psr\SimpleCache\CacheInterface;

class Metadata
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $tables = [];

    public function __construct(Connection $conn, CacheInterface $cache = null)
    {
        $this->conn = $conn;
        $this->cache = $cache;
    }

    private function getTableMetadata($table)
    {
        if (isset($this->tables[$table]) === false) {
            if ($this->cache && $this->cache->has($table)) {
                $this->tables[$table] = $this->cache->get($table);
            } else {
                $sm = $this->conn->getSchemaManager();

                $primary = [];
                foreach ($sm->listTableIndexes($table) as $index) {
                    if ($index->isPrimary()) {
                        $primary = $index->getUnquotedColumns();
                        break;
                    }
                }

                $columns = array_keys($sm->listTableColumns($table));

                $this->tables[$table] = [
                    'primary' => $primary,
                    'columns' => $columns,
                ];

                if ($this->cache) {
                    $this->cache->set($table, $this->tables[$table]);
                }
            }
        }

        return $this->tables[$table];
    }

    public function getPrimaryKey($table)
    {
        return $this->getTableMetadata($table)['primary'];
    }

    public function getColumns($table)
    {
        return $this->getTableMetadata($table)['columns'];
    }
}
