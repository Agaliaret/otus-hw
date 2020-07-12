<?php

namespace OtusHw\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use OtusHw\Security\User;

class FriendsService extends AbstractDbConnectionAwareService
{
    /** @var UserService */
    private UserService $userService;

    /**
     * FriendsService constructor.
     * @param Connection $conn
     * @param UserService $userService
     */
    public function __construct(Connection $conn, UserService $userService)
    {
        parent::__construct($conn);
        $this->userService = $userService;
    }

    /**
     * @param User $user
     * @param string $usernameToAddToFriends
     * @throws DBALException
     */
    public function addUserToFriends(User $user, string $usernameToAddToFriends): void
    {
        $friendUser = $this->userService->getUserByUsername($usernameToAddToFriends);
        if (!$this->checkIfUserIsAddedToFriends($user, $friendUser)) {
            $stmt = $this->conn->prepare("INSERT INTO friends (user_id, friend_user_id) VALUES (:userId, :friendUserId);");
            $stmt->execute([
                ':userId' => $user->getUserId(),
                ':friendUserId' => $friendUser->getUserId()
            ]);
        }
    }

    /**
     * @param User $user
     * @param string $usernameToCheck
     * @return bool
     * @throws DBALException
     */
    public function checkByUsernameIfUserIsAddedToFriends(User $user, string $usernameToCheck): bool
    {
        $friendUser = $this->userService->getUserByUsername($usernameToCheck);
        return $this->checkIfUserIsAddedToFriends($user, $friendUser);
    }

    /**
     * @param User $user
     * @param User $friendUser
     * @return bool
     * @throws DBALException
     */
    public function checkIfUserIsAddedToFriends(User $user, User $friendUser): bool
    {
        $stmt = $this->conn->prepare('SELECT * FROM friends WHERE user_id = :userId AND friend_user_id = :friendUserId;');
        $stmt->execute([
            ':userId' => $user->getUserId(),
            ':friendUserId' => $friendUser->getUserId()
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return !empty($result);
    }

    /**
     * @param User $user
     * @return array
     * @throws DBALException
     */
    public function getUserIncomingFriendshipRequests(User $user): array
    {
        $sql = <<<SQLSTATEMENT
SELECT fus.username, fui.* 
FROM friends f
    LEFT JOIN friends fr ON f.user_id = fr.friend_user_id AND f.friend_user_id = fr.user_id 
    INNER JOIN user_settings fus ON f.user_id = fus.id
    INNER JOIN user_info fui ON f.user_id = fui.user_id
WHERE f.friend_user_id = :userId AND fr.user_id IS NULL;
SQLSTATEMENT;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':userId' => $user->getUserId(),
        ]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    }

    /**
     * @param User $user
     * @return array
     * @throws DBALException
     */
    public function getUserOutgoingFriendshipRequests(User $user): array
    {
        $sql = <<<SQLSTATEMENT
SELECT fus.username, fui.* 
FROM friends f
    LEFT JOIN friends fr ON f.user_id = fr.friend_user_id AND f.friend_user_id = fr.user_id 
    INNER JOIN user_settings fus ON f.friend_user_id = fus.id
    INNER JOIN user_info fui ON f.friend_user_id = fui.user_id
WHERE f.user_id = :userId AND fr.friend_user_id IS NULL;
SQLSTATEMENT;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':userId' => $user->getUserId(),
        ]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    }

    /**
     * @param User $user
     * @return array
     * @throws DBALException
     */
    public function getUserMutualFriends(User $user): array
    {
        $sql = <<<SQLSTATEMENT
SELECT fus.username, fui.* 
FROM friends f
    INNER JOIN friends fr ON f.user_id = fr.friend_user_id AND f.friend_user_id = fr.user_id
    INNER JOIN user_settings fus ON f.friend_user_id = fus.id
    INNER JOIN user_info fui ON f.friend_user_id = fui.user_id
WHERE f.user_id = :userId;
SQLSTATEMENT;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':userId' => $user->getUserId(),
        ]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($result) ? $result : [];
    }
}