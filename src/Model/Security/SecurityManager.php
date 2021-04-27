<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository as OdmRepo;
use Doctrine\ORM\EntityRepository as OrmRepo;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Token;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
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
    protected string $resourceUser = ResourceEnum::USER;

    /**
     * @var OrmRepo<User|DmUser>|OdmRepo<User|DmUser>
     */
    protected OrmRepo|OdmRepo $userRepository;

    /**
     * @var string
     */
    protected string $sessionName;

    /**
     * @var PasswordEncoderInterface
     */
    protected PasswordEncoderInterface $encoder;

    /**
     * @var string
     */
    protected string $area;

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
        protected DatabaseManagerLocator $userDml,
        protected EncoderFactory $encoderFactory,
        protected Session $session,
        protected UsageTrackingTokenStorage $tokenStorage,
        protected ResourceProvider $provider
    )
    {
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
        $this->resourceUser = $resource;
        /** @phpstan-var class-string<User|DmUser> $userClass */
        $userClass            = $this->provider->getResource($this->resourceUser);
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
        } catch (Throwable) {
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
        try {
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
        } catch (Throwable $t) {
            throw new SecurityManagerException($t->getMessage(), $t->getCode(), $t);
        }
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
