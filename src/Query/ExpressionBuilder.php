<?php
namespace ngyuki\DoctrineTableGateway\Query;

use Doctrine\DBAL\Connection;

class ExpressionBuilder
{
    use ExpressionBuilderTrait;

    /**
     * @var Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
