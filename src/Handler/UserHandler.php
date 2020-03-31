<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;
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
    protected UserManager $userManager;

    /**
     * @var ResourceProvider
     */
    protected ResourceProvider $provider;

    /**
     * UserHandler constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param UserManager            $userManager
     * @param ResourceProvider       $provider
     */
    public function __construct(DatabaseManagerLocator $userDml, UserManager $userManager, ResourceProvider $provider)
    {
        $this->dm          = $userDml->get();
        $this->userManager = $userManager;
        $this->provider    = $provider;
    }

    /**
     * @param mixed[] $data
     *
     * @return UserInterface
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     */
    public function login(array $data): UserInterface
    {
        ControllerUtils::checkParameters(['email', 'password'], $data);

        return $this->userManager->login($data);
    }

    /**
     * @return UserInterface
     * @throws SecurityManagerException
     */
    public function loggedUser(): UserInterface
    {
        return $this->userManager->loggedUser();
    }

    /**
     * @return mixed[]
     * @throws SecurityManagerException
     */
    public function logout(): array
    {
        $this->userManager->logout();

        return [];
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws UserManagerException
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
     * @return mixed[]
     * @throws TokenManagerException
     * @throws UserManagerException
     */
    public function activate(string $token): array
    {
        $user = $this->userManager->activate($token);

        return ['email' => $user->getEmail()];
    }

    /**
     * @param string $token
     *
     * @return mixed[]
     * @throws TokenManagerException
     */
    public function verify(string $token): array
    {
        $user = $this->userManager->verify($token);

        return ['email' => $user->getEmail()];
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws TokenManagerException
     * @throws UserManagerException
     */
    public function setPassword(string $id, array $data): array
    {
        ControllerUtils::checkParameters(['password'], $data);

        $this->userManager->setPassword($id, $data);

        return [];
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     * @throws UserManagerException
     */
    public function changePassword(array $data): array
    {
        ControllerUtils::checkParameters(['password'], $data);

        $this->userManager->changePassword($data);

        return [];
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
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
     * @throws ResourceProviderException
     * @throws SecurityManagerException
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
     * Don't redirect when not authenticated
     *
     * @param ExceptionEvent $event
     */
    public function onCoreException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
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
     * @return array<string, array<int|string, array<int|string, int|string>|int|string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onCoreException', 1_000],
            ],
        ];
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
        /**
         * @template    T
         * @phpstan-var class-string<T> $userClass
         */
        $userClass = $this->provider->getResource(ResourceEnum::USER);
        /** @var UserInterface|null $user */
        $user = $this->dm->getRepository($userClass)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS
            );
        }

        return $user;
    }

}
