<?php
namespace ngyuki\TableGateway;

use Doctrine\DBAL\Connection;

class Metadata
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var array
     */
    protected $tables;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    private function getTableMetadata($table)
    {
        if (isset($this->tables[$table]) === false) {
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
