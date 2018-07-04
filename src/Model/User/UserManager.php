<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use EmailServiceBundle\Exception\MailerException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\TmpUserInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManager;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\Event\UserEvent;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\TmpUserRepository as OdmTmpRepo;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\TmpUserRepository as OrmTmpRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UserManager
 *
 * @package Hanaboso\UserBundle\Model\User
 */
class UserManager
{

    /**
     * @var DocumentManager|EntityManager
     */
    protected $dm;

    /**
     * @var SecurityManager
     */
    protected $securityManager;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @var OdmRepo|OrmRepo|ObjectRepository
     */
    protected $userRepository;

    /**
     * @var OdmTmpRepo|OrmTmpRepo|ObjectRepository
     */
    protected $tmpUserRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var string
     */
    protected $activateLink;

    /**
     * @var string
     */
    protected $passwordLink;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * UserManager constructor.
     *
     * @param DatabaseManagerLocator   $userDml
     * @param SecurityManager          $securityManager
     * @param TokenManager             $tokenManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param ResourceProvider         $provider
     * @param Mailer                   $mailer
     * @param string                   $activateLink
     * @param string                   $passwordLink
     *
     * @throws UserException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        SecurityManager $securityManager,
        TokenManager $tokenManager,
        EventDispatcherInterface $eventDispatcher,
        ResourceProvider $provider,
        Mailer $mailer,
        string $activateLink,
        string $passwordLink
    )
    {
        $this->dm                = $userDml->get();
        $this->securityManager   = $securityManager;
        $this->tokenManager      = $tokenManager;
        $this->userRepository    = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
        $this->tmpUserRepository = $this->dm->getRepository($provider->getResource(ResourceEnum::TMP_USER));
        $this->eventDispatcher   = $eventDispatcher;
        $this->provider          = $provider;
        $this->mailer            = $mailer;
        $this->activateLink      = $activateLink;
        $this->passwordLink      = $passwordLink;
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
        $user = $this->securityManager->login($data);
        $this->eventDispatcher->dispatch(UserEvent::USER_LOGIN, new UserEvent($user));

        return $user;
    }

    /**
     * @throws LockException
     * @throws MappingException
     * @throws SecurityManagerException
     */
    public function logout(): void
    {
        $this->eventDispatcher->dispatch(
            UserEvent::USER_LOGOUT,
            new UserEvent($this->securityManager->getLoggedUser())
        );
        $this->securityManager->logout();
    }

    /**
     * @param array $data
     *
     * @throws MailerException
     * @throws UserException
     * @throws UserManagerException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function register(array $data): void
    {
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            throw new UserManagerException(
                sprintf('Email \'%s\' already exists.', $data['email']),
                UserManagerException::USER_EMAIL_ALREADY_EXISTS
            );
        }

        /** @var UserInterface|null $user */
        $user = $this->tmpUserRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            $class = $this->provider->getResource(ResourceEnum::TMP_USER);
            /** @var TmpUserInterface $user */
            $user = new $class();
            $user->setEmail($data['email']);
            $this->dm->persist($user);
            $this->dm->flush();
        }

        $token = $this->tokenManager->create($user);
        $user->setToken($token);
        $token->setTmpUser($user);
        $this->dm->flush();

        $msg = new ActivateMessage($user);
        $msg->setHost($this->activateLink);
        $this->mailer->send($msg);

        $this->eventDispatcher->dispatch(UserEvent::USER_REGISTER, new UserEvent($user));
    }

    /**
     * @param string $token
     *
     * @return UserInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TokenManagerException
     * @throws UserException
     */
    public function activate(string $token): UserInterface
    {
        $token = $this->tokenManager->validate($token);

        if (!$token->getTmpUser()) {
            throw new TokenManagerException(
                'Token has already been used.',
                TokenManagerException::TOKEN_ALREADY_USED
            );
        }

        /** @var UserInterface $class */
        $class = $this->provider->getResource(ResourceEnum::USER);
        /** @var TmpUserInterface $tmpUser */
        $tmpUser = $token->getTmpUser();
        $user    = $class::from($tmpUser)->setToken($token);
        $this->dm->persist($user);
        $this->eventDispatcher->dispatch(UserEvent::USER_ACTIVATE, new UserEvent($user, NULL, $token->getTmpUser()));

        $this->dm->remove($tmpUser);
        $token->setUser($user)->setTmpUser(NULL);
        $this->dm->flush();

        return $user;
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TokenManagerException
     * @throws UserException
     * @throws SecurityManagerException
     */
    public function setPassword(string $id, array $data): void
    {
        $token = $this->tokenManager->validate($id);
        $token
            ->getUserOrTmpUser()
            ->setPassword($this->securityManager->encodePassword($data['password']))
            ->setToken(NULL);

        $this->dm->remove($token);
        $this->dm->flush();
    }

    /**
     * @param array $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SecurityManagerException
     * @throws LockException
     * @throws MappingException
     */
    public function changePassword(array $data): void
    {
        $loggedUser = $this->securityManager->getLoggedUser();
        $this->eventDispatcher->dispatch(UserEvent::USER_CHANGE_PASSWORD, new UserEvent($loggedUser));

        $loggedUser->setPassword($this->securityManager->encodePassword($data['password']));
        $this->dm->flush();
    }

    /**
     * @param array $data
     *
     * @throws MailerException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws UserException
     * @throws UserManagerException
     */
    public function resetPassword(array $data): void
    {
        /** @var UserInterface|null $user */
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('Email \'%s\' not exists.', $data['email']),
                UserManagerException::USER_EMAIL_NOT_EXISTS
            );
        }

        $this->tokenManager->create($user);

        $msg = new ResetPasswordMessage($user);
        $msg->setHost($this->passwordLink);
        $this->mailer->send($msg);

        $this->eventDispatcher->dispatch(UserEvent::USER_RESET_PASSWORD, new UserEvent($user));
    }

    /**
     * @param UserInterface $user
     *
     * @return UserInterface
     * @throws LockException
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SecurityManagerException
     * @throws UserManagerException
     */
    public function delete($user): UserInterface
    {
        $this->eventDispatcher->dispatch(
            UserEvent::USER_DELETE_BEFORE,
            new UserEvent($user, $this->securityManager->getLoggedUser())
        );

        if ($this->securityManager->getLoggedUser()->getId() === $user->getId()) {
            throw new UserManagerException(
                sprintf('User \'%s\' delete not allowed.', $user->getId()),
                UserManagerException::USER_DELETE_NOT_ALLOWED
            );
        }

        $user->setDeleted(TRUE);
        $this->dm->flush();
        $this->eventDispatcher->dispatch(
            UserEvent::USER_DELETE_AFTER,
            new UserEvent($user, $this->securityManager->getLoggedUser())
        );

        return $user;
    }

}
