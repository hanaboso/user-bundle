<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Controller;

use Exception;
use Hanaboso\UserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class UserController
 *
 * @package Hanaboso\UserBundle\Controller
 */
class UserController
{

    use ControllerTrait;

    /**
     * UserController constructor.
     *
     * @param UserHandler $userHandler
     */
    public function __construct(protected UserHandler $userHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/login', methods: ['POST', 'OPTIONS'])]
    public function loginAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->login($request->request->all()));
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @return Response
     */
    #[Route('/user/logged_user', methods: ['GET', 'OPTIONS'])]
    public function loggedUserAction(): Response
    {
        try {
            return $this->getResponse($this->userHandler->loggedUser());
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 401);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @return Response
     */
    #[Route('/user/logout', methods: ['POST', 'OPTIONS'])]
    public function logoutAction(): Response
    {
        try {
            return $this->getResponse($this->userHandler->logout());
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 401);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/register', methods: ['POST', 'OPTIONS'])]
    public function registerAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->register($request->request->all()));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/change_password', methods: ['POST', 'OPTIONS'])]
    public function changePasswordAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->changePassword($request->request->all()));
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 401);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/user/reset_password', methods: ['POST', 'OPTIONS'])]
    public function resetPasswordAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->resetPassword($request->request->all()));
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/activate', requirements: ['token' => '\w+'], methods: ['POST', 'OPTIONS'])]
    public function activateAction(string $token): Response
    {
        try {
            return $this->getResponse($this->userHandler->activate($token));
        } catch (TokenManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $token
     *
     * @return Response
     */
    #[Route('/user/{token}/verify', requirements: ['token' => '\w+'], methods: ['POST', 'OPTIONS'])]
    public function verifyAction(string $token): Response
    {
        try {
            return $this->getResponse($this->userHandler->verify($token));
        } catch (TokenManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    #[Route('/user/{token}/set_password', requirements: ['token' => '\w+'], methods: ['POST', 'OPTIONS'])]
    public function setPasswordAction(Request $request, string $token): Response
    {
        try {
            return $this->getResponse($this->userHandler->setPassword($token, $request->request->all()));
        } catch (TokenManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/user/{id}/delete', methods: ['DELETE', 'OPTIONS'])]
    public function deleteAction(string $id): Response
    {
        try {
            return $this->getResponse($this->userHandler->delete($id)->toArray());
        } catch (UserManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

}
