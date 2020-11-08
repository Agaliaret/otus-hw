<?php

namespace OtusHw\Service;

use Doctrine\DBAL\DBALException;
use PDO;

class InterestService extends AbstractDbConnectionAwareService
{
    /**
     * @param array $interests
     * @throws DBALException
     */
    public function addInterests(array $interests): void
    {
        $this->conn->beginTransaction();
        $stmt = $this->conn->prepare("INSERT INTO interest (value) VALUES (:interest) ON DUPLICATE KEY UPDATE value = :interest;");
        foreach ($interests as $interest) {
            $stmt->bindValue(':interest', $interest);
            $stmt->execute();
        }
        $this->conn->commit();
    }

    /**
     * @param string $interestValue
     * @return int
     * @throws DBALException
     */
    public function findInterestIdByValue(string $interestValue): int
    {
        $stmt = $this->conn->prepare("SELECT id FROM interest WHERE value = :interest;");
        $stmt->bindValue(':interest', $interestValue);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * @param array $interestsValues
     * @return array
     * @throws DBALException
     */
    public function findInterestsByValues(array $interestsValues): array
    {
        //Заполняем параметры знаками вопроса
        $params = implode(',', array_fill(0, count($interestsValues), '?'));
        $stmt = $this->conn->prepare("SELECT id, value FROM interest WHERE value IN ({$params});");
        $stmt->execute(array_values($interestsValues));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed[]
     * @throws DBALException
     */
    public function getAllInterests(): array
    {
        $stmt = $this->conn->prepare("SELECT id, value FROM interest;");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}