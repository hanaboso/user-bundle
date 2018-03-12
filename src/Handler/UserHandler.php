<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use EmailServiceBundle\Exception\MailerException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Class UserHandler
 *
 * @package Hanaboso\UserBundle\Handler
 */
class UserHandler implements LogoutSuccessHandlerInterface, EventSubscriberInterface
{

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * UserHandler constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param UserManager            $userManager
     * @param ResourceProvider       $provider
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        UserManager $userManager,
        ResourceProvider $provider
    )
    {
        $this->dm          = $userDml->get();
        $this->userManager = $userManager;
        $this->provider    = $provider;
    }

    /**
     * @param array $data
     *
     * @return UserInterface
     * @throws PipesFrameworkException
     * @throws UserException
     * @throws SecurityManagerException
     */
    public function login(array $data): UserInterface
    {
        ControllerUtils::checkParameters(['email', 'password'], $data);

        return $this->userManager->login($data);
    }

    /**
     * @return array
     */
    public function logout(): array
    {
        $this->userManager->logout();

        return [];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws UserException
     * @throws UserManagerException
     * @throws MailerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(array $data): array
    {
        $this->userManager->register($data);

        return [];
    }

    /**
     * @param string $token
     *
     * @return array
     * @throws UserException
     * @throws TokenManagerException
     */
    public function activate(string $token): array
    {
        $user = $this->userManager->activate($token);

        return ['email' => $user->getEmail()];
    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws PipesFrameworkException
     * @throws UserException
     * @throws TokenManagerException
     */
    public function setPassword(string $id, array $data): array
    {
        ControllerUtils::checkParameters(['password'], $data);

        $this->userManager->setPassword($id, $data);

        return [];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     */
    public function changePassword(array $data): array
    {
        ControllerUtils::checkParameters(['password'], $data);

        $this->userManager->changePassword($data);

        return [];
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws MailerException
     * @throws NotFoundExceptionInterface
     * @throws UserException
     * @throws UserManagerException
     * @throws PipesFrameworkException
     */
    public function resetPassword(array $data): array
    {
        ControllerUtils::checkParameters(['email'], $data);

        $this->userManager->resetPassword($data);

        return [];
    }

    /**
     * @param string $id
     *
     * @return UserInterface
     * @throws SecurityManagerException
     * @throws UserManagerException
     * @throws UserException
     */
    public function delete(string $id): UserInterface
    {
        return $this->userManager->delete($this->getUser($id));
    }

    /**
     * Don't redirect after logout
     *
     * @param Request $request
     *
     * @return bool
     */
    public function onLogoutSuccess(Request $request): bool
    {
        return TRUE;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onCoreException', 1000],
            ],
        ];
    }

    /**
     * Don't redirect when not authenticated
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onCoreException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException || $exception instanceof AuthenticationCredentialsNotFoundException) {
            $jsonResponse = new JsonResponse($exception->getMessage(), 403);

            $event->setResponse($jsonResponse);
        }
    }

    /**
     * @param string $id
     *
     * @return UserInterface
     * @throws UserManagerException
     * @throws UserException
     */
    private function getUser(string $id): UserInterface
    {
        /** @var UserInterface $user */
        $user = $this->dm->getRepository(
            $this->provider->getResource(ResourceEnum::USER)
        )->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User \'%s\' not exists.', $id),
                UserManagerException::USER_NOT_EXISTS
            );
        }

        return $user;
    }

}