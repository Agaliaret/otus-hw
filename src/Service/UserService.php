<?php

namespace OtusHw\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OtusHw\Exception\UsernameInUseRegistrationException;
use OtusHw\Exception\UsernameNotFoundException;
use OtusHw\Security\User;

class UserService extends AbstractDbConnectionAwareService
{
    /** @var InterestService */
    private InterestService $interestService;

    /**
     * UserService constructor.
     * @param Connection $conn
     * @param InterestService $interestService
     */
    public function __construct(Connection $conn, InterestService $interestService)
    {
        parent::__construct($conn);
        $this->interestService = $interestService;
    }

    /**
     * @param string $username
     * @param string $encodedPassword
     * @return int ID добавленной записи
     * @throws DBALException
     */
    public function addUserSettingRow(string $username, string $encodedPassword): int
    {
        try {
            $stmt = $this->conn->prepare("INSERT INTO user_settings (username, password) VALUES (:username, :password);");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $encodedPassword);
            $stmt->execute();
            return (int)$this->conn->lastInsertId();
        } catch (UniqueConstraintViolationException $e) {
            throw new UsernameInUseRegistrationException('Username is already in use');
        }
    }

    /**
     * @param int $userId
     * @param string $name
     * @param string $surname
     * @param int $age
     * @param string $gender
     * @param string $city
     * @throws DBALException
     */
    public function addUserInfo(
        int $userId,
        string $name,
        string $surname,
        int $age,
        string $gender,
        string $city
    ): void {
        $stmt = $this->conn->prepare('INSERT INTO user_info (user_id, name, surname, age, gender, city) VALUES (:userId, :name, :surname, :age, :gender, :city);');
        $stmt->execute([
            ':userId' => $userId,
            ':name' => $name,
            ':surname' => $surname,
            ':age' => $age,
            ':gender' => $gender,
            ':city' => $city,
        ]);
    }

    /**
     * @param int $userId
     * @return mixed
     * @throws DBALException
     */
    public function getUserInfo(int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT * FROM user_info WHERE user_id = :userId;');
        $stmt->execute([':userId' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    }

    /**
     * @param string $username
     * @return User
     * @throws DBALException
     * @throws UsernameNotFoundException
     */
    public function getUserByUsername(string $username): User
    {
        $stmt = $this->conn->prepare('SELECT * FROM user_settings WHERE username = :username;');
        $stmt->execute([':username' => $username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($result)) {
            throw new UsernameNotFoundException('User with username ' . $username . ' not found');
        }
        return User::createFromArray($result);
    }

    /**
     * @param int $userId
     * @param array $interests
     * @throws DBALException
     * @throws ConnectionException
     */
    public function addUserInterests(int $userId, array $interests = []): void
    {
        if ($interests === []) {
            return;
        }
        $this->conn->beginTransaction();
        $interestsData = $this->interestService->findInterestsByValues($interests);
        $stmt = $this->conn->prepare("DELETE FROM user_has_interest WHERE user_id = :userId;");
        $stmt->execute([':userId' => $userId]);
        foreach ($interestsData as $interestData) {
            if (!isset($interestData['id'])) {
                continue;
            }
            $stmt = $this->conn->prepare("INSERT INTO user_has_interest (interest_id, user_id) VALUES (:interestId, :userId);");
            $stmt->execute([
                ':interestId' => $interestData['id'],
                ':userId' => $userId
            ]);
        }
        $this->conn->commit();
    }

    /**
     * @param int $userId
     * @return array|mixed[]
     * @throws DBALException
     */
    public function getUserInterests(int $userId): array
    {
        $sql = <<<SQLSTATEMENT
SELECT i.value as interest 
FROM user_has_interest u_i
    INNER JOIN interest i ON u_i.interest_id = i.id
WHERE u_i.user_id = :userId;
SQLSTATEMENT;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return is_array($result) ? $result : [];
    }

    /**
     * @param array $criteria
     * @return array|mixed[]
     * @throws DBALException
     */
    public function searchUsers(array $criteria): array
    {
        $sql = <<<SQLSTATEMENT
SELECT us.username, ui.*
FROM user_info ui
    INNER JOIN user_settings us ON ui.user_id = us.id
SQLSTATEMENT;
        $where = $params = [];
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                if ($key === 'ageFrom') {
                    $where[] = 'ui.age >= :'.$key;
                } elseif ($key === 'ageTo') {
                    $where[] = 'ui.age <= :'.$key;
                } else {
                    $where[] = 'ui.'.$key.' = :'.$key;
                }
                $params[':'.$key] = $value;
            }
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $userId
     * @param array $userInfo
     * @throws DBALException
     */
    public function editUserInfo(int $userId, array $userInfo): void
    {
        $stmt = $this->conn->prepare("UPDATE user_info SET name = :name, surname = :surname, age = :age, gender = :gender, city = :city WHERE user_id = :userId;");
        $stmt->execute([
            ':userId' => $userId,
            ':name' => $userInfo['name'],
            ':surname' => $userInfo['surname'],
            ':age' => $userInfo['age'],
            ':gender' => $userInfo['gender'],
            ':city' => $userInfo['city'],
        ]);

        $this->editUserInterests($userId, $userInfo['interests']);
    }

    /**
     * @param int $userId
     * @param array $interests
     * @throws ConnectionException
     * @throws DBALException
     */
    public function editUserInterests(int $userId, array $interests): void
    {
        $this->interestService->addInterests($interests);
        $this->addUserInterests($userId, $interests);
    }
}