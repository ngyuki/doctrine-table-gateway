<?php
namespace ngyuki\TableGateway\Test\Typed;

use Doctrine\DBAL\Connection;
use ngyuki\TableGateway\ResultSet;
use ngyuki\TableGateway\TableGateway;

class TypedTable extends TableGateway
{
    public function __construct(Connection $conn)
    {
        parent::__construct($conn, 't_user');
        $this->setScope('aa = 0');
    }

    /**
     * @param mixed|array|null $id
     *
     * @return TypedRow
     */
    public function find($id = null)
    {
        return parent::find($id);
    }

    protected function createResultSet(\Traversable $statement)
    {
        return new TypedSet((function () use ($statement) {
            foreach ($statement as $key => $arr) {
                yield $key => new TypedRow($arr);
            }
        })());
    }

    /**
     * @return TypedSet|ResultSet
     */
    public function all()
    {
        return parent::all();
    }
}
