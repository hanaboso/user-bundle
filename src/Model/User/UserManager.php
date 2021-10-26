<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\TmpUserInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManager;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\Event\ActivateUserEvent;
use Hanaboso\UserBundle\Model\User\Event\ChangePasswordUserEvent;
use Hanaboso\UserBundle\Model\User\Event\DeleteAfterUserEvent;
use Hanaboso\UserBundle\Model\User\Event\DeleteBeforeUserEvent;
use Hanaboso\UserBundle\Model\User\Event\LoginUserEvent;
use Hanaboso\UserBundle\Model\User\Event\LogoutUserEvent;
use Hanaboso\UserBundle\Model\User\Event\RegisterUserEvent;
use Hanaboso\UserBundle\Model\User\Event\ResetPasswordUserEvent;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\UserBundle\Repository\Document\TmpUserRepository as OdmTmpRepo;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\TmpUserRepository as OrmTmpRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Hanaboso\Utils\Exception\DateTimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

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
    protected DocumentManager|EntityManager $dm;

    /**
     * @var OdmRepo|OrmRepo<object>
     */
    protected $userRepository;

    /**
     * @var OdmTmpRepo|OrmTmpRepo
     */
    protected $tmpUserRepository;

    /**
     * @var string
     */
    protected string $activateLink;

    /**
     * @var string
     */
    protected string $passwordLink;

    /**
     * UserManager constructor.
     *
     * @param DatabaseManagerLocator   $userDml
     * @param SecurityManager          $securityManager
     * @param TokenManager             $tokenManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param ResourceProvider         $provider
     * @param Mailer                   $mailer
     * @param string                   $feHost
     * @param string                   $activateLink
     * @param string                   $passwordLink
     *
     * @throws ResourceProviderException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        protected SecurityManager $securityManager,
        protected TokenManager $tokenManager,
        protected EventDispatcherInterface $eventDispatcher,
        private ResourceProvider $provider,
        protected Mailer $mailer,
        string $feHost,
        string $activateLink,
        string $passwordLink,
    )
    {
        /**
         * @template    T of object
         * @phpstan-var class-string<T> $userClass
         */
        $userClass = $provider->getResource(ResourceEnum::USER);
        /**
         * @template    T of object
         * @phpstan-var class-string<T> $tmpUserClass
         */
        $tmpUserClass = $provider->getResource(ResourceEnum::TMP_USER);

        $this->dm                = $userDml->get();
        $this->userRepository    = $this->dm->getRepository($userClass);
        $this->tmpUserRepository = $this->dm->getRepository($tmpUserClass);
        $this->activateLink      = sprintf('%s/%s', rtrim($feHost, '/'), ltrim($activateLink, '/'));
        $this->passwordLink      = sprintf('%s/%s', rtrim($feHost, '/'), ltrim($passwordLink, '/'));
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws SecurityManagerException
     * @throws DateTimeException
     */
    public function login(array $data): array
    {
        [$user, $token] = $this->securityManager->login($data);
        $this->eventDispatcher->dispatch(new LoginUserEvent($user), LoginUserEvent::NAME);

        return [$user, $token];
    }

    /**
     * @return mixed[]
     * @throws SecurityManagerException
     */
    public function loggedUser(): array
    {
        return $this->securityManager->getLoggedUser();
    }

    /**
     * @throws SecurityManagerException
     */
    public function logout(): void
    {
        [$user,] = $this->securityManager->getLoggedUser();
        $this->eventDispatcher->dispatch(
            new LogoutUserEvent($user),
            LogoutUserEvent::NAME,
        );
    }

    /**
     * @param mixed[] $data
     *
     * @throws UserManagerException
     */
    public function register(array $data): void
    {
        // Disable enumerate list of used e-mails
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return;
        }

        try {
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

            $this->eventDispatcher->dispatch(new RegisterUserEvent($user), RegisterUserEvent::NAME);
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param string $token
     *
     * @return UserInterface
     * @throws TokenManagerException
     * @throws UserManagerException
     */
    public function activate(string $token): UserInterface
    {
        $token = $this->tokenManager->validate($token);

        if (!$token->getTmpUser()) {
            throw new TokenManagerException('Token has already been used.', TokenManagerException::TOKEN_ALREADY_USED);
        }

        try {
            /** @var UserInterface $class */
            $class = $this->provider->getResource(ResourceEnum::USER);
            /** @var TmpUserInterface $tmpUser */
            $tmpUser = $token->getTmpUser();
            $user    = $class::from($tmpUser)->setToken($token);
            $this->dm->persist($user);
            $this->eventDispatcher->dispatch(
                new ActivateUserEvent($user, NULL, $token->getTmpUser()),
                ActivateUserEvent::NAME,
            );

            $this->dm->remove($tmpUser);
            $token->setUser($user)->setTmpUser(NULL);
            $this->dm->flush();

            return $user;
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param string $token
     *
     * @return UserInterface
     * @throws TokenManagerException
     */
    public function verify(string $token): UserInterface
    {
        return $this->tokenManager->validate($token)->getUserOrTmpUser();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @throws TokenManagerException
     * @throws UserManagerException
     */
    public function setPassword(string $id, array $data): void
    {
        $token = $this->tokenManager->validate($id);
        $token
            ->getUserOrTmpUser()
            ->setPassword($this->securityManager->encodePassword($data['password']))
            ->setToken(NULL);

        try {
            $this->dm->remove($token);
            $this->dm->flush();
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param mixed[] $data
     *
     * @throws SecurityManagerException
     * @throws UserManagerException
     */
    public function changePassword(array $data): void
    {
        [$loggedUser,] = $this->securityManager->getLoggedUser();
        $this->eventDispatcher->dispatch(new ChangePasswordUserEvent($loggedUser), ChangePasswordUserEvent::NAME);

        try {
            if (isset($data['old_password'])) {
                $this->securityManager->validateUser($loggedUser, ['password' => $data['old_password']]);
            }

            $loggedUser->setPassword($this->securityManager->encodePassword($data['password']));
            $this->dm->flush();
        } catch (SecurityManagerException $e) {
            throw new UserManagerException('Incorrect old password', $e->getCode(), $e);
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param mixed[] $data
     *
     * @throws UserManagerException
     */
    public function resetPassword(array $data): void
    {
        /** @var UserInterface|null $user */
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);
        // Disable enumerate list of used e-mails
        if (!$user) {
            return;
        }

        try {
            $this->tokenManager->create($user);

            $msg = new ResetPasswordMessage($user);
            $msg->setHost($this->passwordLink);
            $this->mailer->send($msg);

            $this->eventDispatcher->dispatch(new ResetPasswordUserEvent($user), ResetPasswordUserEvent::NAME);
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param UserInterface $user
     *
     * @return UserInterface
     * @throws SecurityManagerException
     * @throws UserManagerException
     */
    public function delete(UserInterface $user): UserInterface
    {
        [$loggedUser,] = $this->securityManager->getLoggedUser();
        $this->eventDispatcher->dispatch(
            new DeleteBeforeUserEvent($user, $loggedUser),
            DeleteBeforeUserEvent::NAME,
        );
        if ($loggedUser->getId() === $user->getId()) {
            throw new UserManagerException(
                sprintf('User \'%s\' delete not allowed.', $user->getId()),
                UserManagerException::USER_DELETE_NOT_ALLOWED,
            );
        }

        try {
            $user->setDeleted(TRUE);
            $this->dm->flush();
            $this->eventDispatcher->dispatch(
                new DeleteAfterUserEvent($user, $loggedUser),
                DeleteAfterUserEvent::NAME,
            );

            return $user;
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

}
