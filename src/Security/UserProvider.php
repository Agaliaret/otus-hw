<?php

namespace OtusHw\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /** @var Connection */
    private Connection $conn;

    /**
     * UserProvider constructor.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @param string $username
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     * @throws DBALException
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        $stmt = $this->conn->prepare('SELECT * FROM user_settings WHERE username = :username');
        $stmt->bindValue(':username', $username, \PDO::PARAM_STR);
        $stmt->execute();
        $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$userInfo) {
            throw new UsernameNotFoundException('Username could not be found.');
        }

        return User::createFromArray($userInfo);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @param UserInterface $user
     * @return UserInterface
     * @throws DBALException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Tells Symfony to use this provider for this User class.
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /**
     * Upgrades the encoded password of a user, typically for using a better hash algorithm.
     * @param UserInterface $user
     * @param string $newEncodedPassword
     * @throws DBALException
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newEncodedPassword);
        $stmt = $this->conn->prepare('UPDATE user_settings SET password = :password WHERE username = :username');
        $stmt->bindValue(':password', $newEncodedPassword, \PDO::PARAM_STR);
        $stmt->bindValue(':username', $user->getUsername(), \PDO::PARAM_STR);
        $stmt->execute();
        if ($user instanceof User) {
            $user->setPassword($newEncodedPassword);
        }
    }
}
