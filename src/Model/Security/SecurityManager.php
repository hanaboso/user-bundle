<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Model\Token;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

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
    private $resourceUser = ResourceEnum::USER;

    /**
     * @var OrmRepo|OdmRepo|ObjectRepository
     */
    private $userRepository;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $sessionName;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var DatabaseManagerLocator
     */
    private $userDml;

    /**
     * @var string
     */
    private $area;

    /**
     * SecurityManager constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param EncoderFactory         $encoderFactory
     * @param Session                $session
     * @param TokenStorage           $tokenStorage
     * @param ResourceProvider       $provider
     *
     * @throws UserException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        EncoderFactory $encoderFactory,
        Session $session,
        TokenStorage $tokenStorage,
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
     * @throws UserException
     */
    public function setUserResource(string $resource): SecurityManager
    {
        $this->resourceUser   = $resource;
        $user                 = $this->provider->getResource($this->resourceUser);
        $this->userRepository = $this->userDml->get()->getRepository($user);
        $this->encoder        = $this->encoderFactory->getEncoder($user);

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
        $this->sessionName = self::SECURITY_KEY . $this->area;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return UserInterface
     * @throws LockException
     * @throws MappingException
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
     * @param array         $data
     */
    public function setToken(UserInterface $user, array $data): void
    {
        $token = new Token($user, $data['password'], $this->area, ['USER_LOGGED']);
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
     * @throws LockException
     * @throws MappingException
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
     * ------------------------------------------- HELPERS ---------------------------------
     */

    /**
     * @return UserInterface
     * @throws LockException
     * @throws MappingException
     * @throws SecurityManagerException
     */
    private function getUserFromSession(): UserInterface
    {
        /** @var Token $token */
        $token = unserialize($this->session->get($this->sessionName));

        /** @var UserInterface|null $user */
        $user = $this->userRepository->find($token->getUser()->getId());

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
    private function getUser(string $email): UserInterface
    {
        /** @var UserInterface|null $user */
        $user = $this->userRepository->findOneBy([
            'email'   => $email,
            'deleted' => FALSE,
        ]);

        if (!$user) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $email),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @param array         $data
     *
     * @throws SecurityManagerException
     */
    private function validateUser(UserInterface $user, array $data): void
    {
        if (!$this->encoder->isPasswordValid($user->getPassword(), $data['password'], '')) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $data['email']),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }
    }

}
