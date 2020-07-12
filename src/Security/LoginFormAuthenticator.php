<?php

namespace OtusHw\Security;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    /** @var UrlGeneratorInterface */
    private UrlGeneratorInterface $urlGenerator;

    /** @var CsrfTokenManagerInterface */
    private CsrfTokenManagerInterface $csrfTokenManager;

    /** @var Connection */
    private Connection $conn;

    /** @var UserPasswordEncoderInterface */
    private UserPasswordEncoderInterface $encoder;

    public function __construct(
        Connection $connection,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $encoder
    ) {
        $this->conn = $connection;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->encoder = $encoder;
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * @param Request $request
     * @return array|mixed
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return User
     * @throws AuthenticationException
     */
    public function getUser($credentials, UserProviderInterface $userProvider): User
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $stmt = $this->conn->prepare('SELECT * FROM user_settings WHERE username = :username');
        $stmt->bindValue(':username', $credentials[User::FIELD_USERNAME], \PDO::PARAM_STR);
        $stmt->execute();
        $userInfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$userInfo) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }

        return User::createFromArray($userInfo);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        //Check the user's password or other credentials and return true or false
        //If there are no credentials to check, you can just return true
        return $this->encoder->isPasswordValid($user, $credentials[User::FIELD_PASSWORD]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('current_user_page'));
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    public function getPassword($credentials): ?string
    {
        return $credentials[User::FIELD_PASSWORD] ?? null;
    }
}
