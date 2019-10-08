<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use EmailServiceBundle\Exception\MailerException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
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
    protected $dm;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var ResourceProvider
     */
    protected $provider;

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
     * @throws SecurityManagerException
     * @throws LockException
     * @throws MappingException
     */
    public function login(array $data): UserInterface
    {
        ControllerUtils::checkParameters(['email', 'password'], $data);

        return $this->userManager->login($data);
    }

    /**
     * @return UserInterface
     * @throws LockException
     * @throws MappingException
     * @throws SecurityManagerException
     */
    public function loggedUser(): UserInterface
    {
        return $this->userManager->loggedUser();
    }

    /**
     * @return array
     * @throws LockException
     * @throws MappingException
     * @throws SecurityManagerException
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
     * @throws MailerException
     * @throws PipesFrameworkException
     * @throws ResourceProviderException
     * @throws UserManagerException
     * @throws ORMException
     */
    public function register(array $data): array
    {
        ControllerUtils::checkParameters(['email'], $data);

        $this->userManager->register($data);

        return [];
    }

    /**
     * @param string $token
     *
     * @return array
     * @throws ORMException
     * @throws TokenManagerException
     * @throws ResourceProviderException
     * @throws DateTimeException
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
     * @throws ORMException
     * @throws PipesFrameworkException
     * @throws TokenManagerException
     * @throws ResourceProviderException
     * @throws DateTimeException
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
     * @throws LockException
     * @throws MappingException
     * @throws ORMException
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
     * @throws MailerException
     * @throws ORMException
     * @throws PipesFrameworkException
     * @throws ResourceProviderException
     * @throws UserManagerException
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
     * @throws LockException
     * @throws MappingException
     * @throws ORMException
     * @throws SecurityManagerException
     * @throws ResourceProviderException
     * @throws UserManagerException
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
     * @return Response
     */
    public function onLogoutSuccess(Request $request): Response
    {
        $request;

        return new Response('{}', 200, ['Content-Type' => 'application/json']);
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
     * @param ExceptionEvent $event
     */
    public function onCoreException(ExceptionEvent $event): void
    {
        $exception = $event->getException();
        $body      = [
            'status'     => 'ERROR',
            'error_code' => $exception->getCode(),
            'type'       => get_class($exception),
            'message'    => $exception->getMessage(),
        ];

        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse($body, 401));
        }

        if ($exception instanceof AuthenticationCredentialsNotFoundException) {
            $body['message'] = 'User not logged!';
            $event->setResponse(new JsonResponse($body, 401));
        }

    }

    /**
     * @param string $id
     *
     * @return UserInterface
     * @throws UserManagerException
     * @throws ResourceProviderException
     */
    private function getUser(string $id): UserInterface
    {
        /** @var UserInterface|null $user */
        $user = $this->dm->getRepository(
            $this->provider->getResource(ResourceEnum::USER)
        )->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS
            );
        }

        return $user;
    }

}
