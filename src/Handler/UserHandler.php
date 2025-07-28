<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class UserHandler
 *
 * @package Hanaboso\UserBundle\Handler
 */
class UserHandler implements EventSubscriberInterface
{

    /**
     * @var DocumentManager|EntityManager
     */
    protected DocumentManager|EntityManager $dm;

    /**
     * UserHandler constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param UserManager            $userManager
     * @param ResourceProvider       $provider
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        protected UserManager $userManager,
        protected ResourceProvider $provider,
    )
    {
        $this->dm = $userDml->get();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     * @throws DateTimeException
     */
    public function login(array $data): array
    {
        ControllerUtils::checkParameters(['email', 'password'], $data);
        [$user, $token] = $this->userManager->login($data);

        return [
            'token' => $token,
            'user'  => $user->toArray(),
        ];
    }

    /**
     * @return mixed[]
     * @throws SecurityManagerException
     */
    public function loggedUser(): array
    {
        [$user, $token] = $this->userManager->loggedUser();

        return [
            'token' => $token,
            'user'  => $user->toArray(),
        ];
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
     * @return User|DmUser
     * @throws ResourceProviderException
     * @throws SecurityManagerException
     * @throws UserManagerException
     */
    public function delete(string $id): User|DmUser
    {
        return $this->userManager->delete($this->getUser($id));
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
            'error_code' => $exception->getCode(),
            'message'    => $exception->getMessage(),
            'status'     => 'ERROR',
            'type'       => $exception::class,
        ];

        if ($exception instanceof AuthenticationException ||
            $exception instanceof AccessDeniedException ||
            $exception instanceof SecurityManagerException
        ) {
            $event->setResponse(new JsonResponse($body, 401));
        }

        if ($exception instanceof AuthenticationCredentialsNotFoundException) {
            $body['message'] = 'User not logged!';
            $event->setResponse(new JsonResponse($body, 401));
        }
    }

    /**
     * @return array<string, list<array{0: string, 1?: int}|int|string>|string>
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
     * @return User|DmUser
     * @throws UserManagerException
     * @throws ResourceProviderException
     */
    private function getUser(string $id): User|DmUser
    {
        /**
         * @template    T of object
         * @phpstan-var class-string<T> $userClass
         */
        $userClass = $this->provider->getResource(ResourceEnum::USER);
        /** @var User|DmUser|null $user */
        $user = $this->dm->getRepository($userClass)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS,
            );
        }

        return $user;
    }

}
