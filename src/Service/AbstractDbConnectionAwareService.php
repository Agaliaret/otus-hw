<?php

namespace OtusHw\Service;

use Doctrine\DBAL\Connection;

abstract class AbstractDbConnectionAwareService
{
    /** @var Connection */
    protected Connection $conn;

    /**
     * InterestsService constructor.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
}