<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

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
    private $dm;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var OdmRepo|OrmRepo|ObjectRepository
     */
    private $userRepository;

    /**
     * @var OdmTmpRepo|OrmTmpRepo|ObjectRepository
     */
    private $tmpUserRepository;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var string
     */
    private $activateLink;

    /**
     * UserManager constructor.
     *
     * @param DatabaseManagerLocator   $userDml
     * @param SecurityManager          $securityManager
     * @param TokenManager             $tokenManager
     * @param EncoderFactory           $encoderFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param ResourceProvider         $provider
     * @param Mailer                   $mailer
     * @param string                   $activateLink
     *
     * @throws UserException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        SecurityManager $securityManager,
        TokenManager $tokenManager,
        EncoderFactory $encoderFactory,
        EventDispatcherInterface $eventDispatcher,
        ResourceProvider $provider,
        Mailer $mailer,
        string $activateLink
    )
    {
        $this->dm                = $userDml->get();
        $this->securityManager   = $securityManager;
        $this->tokenManager      = $tokenManager;
        $this->userRepository    = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
        $this->tmpUserRepository = $this->dm->getRepository($provider->getResource(ResourceEnum::TMP_USER));
        $this->encoder           = $encoderFactory->getEncoder($provider->getResource(ResourceEnum::USER));
        $this->eventDispatcher   = $eventDispatcher;
        $this->provider          = $provider;
        $this->mailer            = $mailer;
        $this->activateLink      = $activateLink;
    }

    /**
     * @param array $data
     *
     * @return UserInterface
     * @throws SecurityManagerException
     * @throws UserException
     */
    public function login(array $data): UserInterface
    {
        $user = $this->securityManager->login($data);
        $this->eventDispatcher->dispatch(UserEvent::USER_LOGIN, new UserEvent($user));

        return $user;
    }

    /**
     *
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
     * @throws ContainerExceptionInterface
     * @throws MailerException
     * @throws NotFoundExceptionInterface
     * @throws UserException
     * @throws UserManagerException
     */
    public function register(array $data): void
    {
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            throw new UserManagerException(
                sprintf('Email \'%s\' already exists.', $data['email']),
                UserManagerException::USER_EMAIL_ALREADY_EXISTS
            );
        }

        /** @var UserInterface $user */
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
        $user  = $class::from($token->getTmpUser())->setToken($token);
        $this->dm->persist($user);
        $this->eventDispatcher->dispatch(UserEvent::USER_ACTIVATE, new UserEvent($user, NULL, $token->getTmpUser()));

        $this->dm->remove($token->getTmpUser());
        $token->setUser($user)->setTmpUser(NULL);
        $this->dm->flush();

        return $user;
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @throws TokenManagerException
     * @throws UserException
     */
    public function setPassword(string $id, array $data): void
    {
        $token = $this->tokenManager->validate($id);
        $token
            ->getUserOrTmpUser()
            ->setPassword($this->encoder->encodePassword($data['password'], ''))
            ->setToken(NULL);

        $this->dm->remove($token);
        $this->dm->flush();
    }

    /**
     * @param array $data
     *
     * @throws SecurityManagerException
     */
    public function changePassword(array $data): void
    {
        $loggedUser = $this->securityManager->getLoggedUser();
        $this->eventDispatcher->dispatch(UserEvent::USER_CHANGE_PASSWORD, new UserEvent($loggedUser));

        $loggedUser->setPassword($this->encoder->encodePassword($data['password'], ''));
        $this->dm->flush();
    }

    /**
     * @param array $data
     *
     * @throws UserManagerException
     * @throws MailerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws UserException
     */
    public function resetPassword(array $data): void
    {
        /** @var UserInterface $user */
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('Email \'%s\' not exists.', $data['email']),
                UserManagerException::USER_EMAIL_NOT_EXISTS
            );
        }

        $this->tokenManager->create($user);

        $msg = new ResetPasswordMessage($user);
        $this->mailer->send($msg);

        $this->eventDispatcher->dispatch(UserEvent::USER_RESET_PASSWORD, new UserEvent($user));
    }

    /**
     * @param UserInterface $user
     *
     * @return UserInterface
     * @throws UserManagerException
     * @throws SecurityManagerException
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
