<?php

namespace OtusHw\Security;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class User implements UserInterface
{
    public const FIELD_USER_ID = 'id';
    public const FIELD_USERNAME = 'username';
    public const FIELD_PASSWORD = 'password';
    public const FIELD_ROLES = 'roles';

    /** @var int */
    private int $userId;

    /** @var string */
    private string $username;

    /** @var array  */
    private array $roles = [];

    /** @var string The hashed password */
    private string $password;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public static function createFromArray(array $userInfo): self
    {
        $user = new self();
        if (!isset($userInfo[self::FIELD_USERNAME])) {
            throw new \InvalidArgumentException('Не передано имя пользователя');
        }
        if (!isset($userInfo[self::FIELD_PASSWORD])) {
            throw new \InvalidArgumentException('Не передан пароль пользователя');
        }
        $user->setUserId($userInfo[self::FIELD_USER_ID])
            ->setUsername($userInfo[self::FIELD_USERNAME])
            ->setPassword($userInfo[self::FIELD_PASSWORD]);
        if (isset($userInfo[self::FIELD_ROLES])) {
            $user->setRoles($userInfo[self::FIELD_ROLES]);
        }
        return $user;
    }
}
