<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Token;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Throwable;

/**
 * Class SecurityManager
 *
 * @package Hanaboso\UserBundle\Model\Security
 */
class SecurityManager
{

    public const SECURITY_KEY = '_security_';
    public const SECURED_AREA = 'secured_area';

    /**
     * @var string
     */
    protected $resourceUser = ResourceEnum::USER;

    /**
     * @var OrmRepo|OdmRepo
     */
    protected $userRepository;

    /**
     * @var Session<mixed>
     */
    protected $session;

    /**
     * @var UsageTrackingTokenStorage
     */
    protected $tokenStorage;

    /**
     * @var string
     */
    protected $sessionName;

    /**
     * @var ResourceProvider
     */
    protected $provider;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var EncoderFactory
     */
    protected $encoderFactory;

    /**
     * @var DatabaseManagerLocator
     */
    protected $userDml;

    /**
     * @var string
     */
    protected $area;

    /**
     * SecurityManager constructor.
     *
     * @param DatabaseManagerLocator    $userDml
     * @param EncoderFactory            $encoderFactory
     * @param Session<mixed>            $session
     * @param UsageTrackingTokenStorage $tokenStorage
     * @param ResourceProvider          $provider
     *
     * @throws ResourceProviderException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        EncoderFactory $encoderFactory,
        Session $session,
        UsageTrackingTokenStorage $tokenStorage,
        ResourceProvider $provider
    )
    {
        $this->tokenStorage   = $tokenStorage;
        $this->session        = $session;
        $this->encoderFactory = $encoderFactory;
        $this->provider       = $provider;
        $this->userDml        = $userDml;

        $this->setUserResource($this->resourceUser);
        $this->setArea(self::SECURED_AREA);
    }

    /**
     * @param string $resource
     *
     * @return SecurityManager
     * @throws ResourceProviderException
     */
    public function setUserResource(string $resource): SecurityManager
    {
        /** @phpstan-var class-string<\Hanaboso\UserBundle\Entity\User|\Hanaboso\UserBundle\Document\User> $userClass */
        $userClass            = $this->provider->getResource($this->resourceUser);
        $this->resourceUser   = $resource;
        $this->userRepository = $this->userDml->get()->getRepository($userClass);
        $this->encoder        = $this->encoderFactory->getEncoder($userClass);

        return $this;
    }

    /**
     * @param string $area
     *
     * @return SecurityManager
     */
    public function setArea(string $area): SecurityManager
    {
        $this->area        = $area;
        $this->sessionName = sprintf('%s%s', self::SECURITY_KEY, $this->area);

        return $this;
    }

    /**
     * @param mixed[] $data
     *
     * @return UserInterface
     * @throws SecurityManagerException
     */
    public function login(array $data): UserInterface
    {
        if ($this->isLoggedIn()) {
            return $this->getUserFromSession();
        }

        $user = $this->getUser($data['email']);
        $this->validateUser($user, $data);
        $this->setToken($user, $data);

        return $user;
    }

    /**
     * @param UserInterface $user
     * @param mixed[]       $data
     */
    public function setToken(UserInterface $user, array $data): void
    {
        $token = new Token($user, $data['password'], $this->area, ['admin']);
        $this->tokenStorage->setToken($token);
        $this->session->set($this->sessionName, serialize($token));
    }

    /**
     *
     */
    public function logout(): void
    {
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->session->has($this->sessionName);
    }

    /**
     * @return UserInterface
     * @throws SecurityManagerException
     */
    public function getLoggedUser(): UserInterface
    {
        if (!$this->isLoggedIn()) {
            throw new SecurityManagerException('User not logged.', SecurityManagerException::USER_NOT_LOGGED);
        }

        return $this->getUserFromSession();
    }

    /**
     * @param string $rawPassword
     *
     * @return string
     */
    public function encodePassword(string $rawPassword): string
    {
        return $this->encoder->encodePassword($rawPassword, '');
    }

    /**
     * @param UserInterface $user
     * @param mixed[]       $data
     *
     * @throws SecurityManagerException
     */
    public function validateUser(UserInterface $user, array $data): void
    {
        try {
            if (!$this->encoder->isPasswordValid($user->getPassword() ?? '', $data['password'], '')) {
                throw new Exception('Invalid password');
            }
        } catch (Throwable $e) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $user->getEmail()),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }
    }

    /**
     * ------------------------------------------- HELPERS ---------------------------------
     */

    /**
     * @return UserInterface
     * @throws SecurityManagerException
     */
    protected function getUserFromSession(): UserInterface
    {
        /** @var Token $token */
        $token = unserialize($this->session->get($this->sessionName));

        /** @var UserInterface $user */
        $user = $token->getUser();

        /** @var UserInterface|null $user */
        $user = $this->userRepository->find($user->getId());

        if (!$user) {
            $this->logout();
            throw new SecurityManagerException('User not logged.', SecurityManagerException::USER_NOT_LOGGED);
        }

        return $user;
    }

    /**
     * @param string $email
     *
     * @return UserInterface
     * @throws SecurityManagerException
     */
    protected function getUser(string $email): UserInterface
    {
        /** @var UserInterface|null $user */
        $user = $this->userRepository->findOneBy(
            [
                'email'   => $email,
                'deleted' => FALSE,
            ]
        );

        if (!$user) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $email),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }

        return $user;
    }

}
