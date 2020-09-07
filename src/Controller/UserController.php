<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Controller;

use Exception;
use Hanaboso\UserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     * @var UserHandler
     */
    protected UserHandler $userHandler;

    /**
     * UserController constructor.
     *
     * @param UserHandler $userHandler
     */
    public function __construct(UserHandler $userHandler)
    {
        $this->userHandler = $userHandler;
        $this->logger      = new NullLogger();
    }

    /**
     * @Route("/user/login", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function loginAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->userHandler->login($request->request->all())->toArray());
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (PipesFrameworkException | Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/user/logged_user", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function loggedUserAction(): Response
    {
        try {
            return $this->getResponse($this->userHandler->loggedUser()->toArray());
        } catch (SecurityManagerException $e) {
            return $this->getErrorResponse($e, 401);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/user/logout", methods={"POST", "OPTIONS"})
     *
     * @return Response
     */
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
     * @Route("/user/register", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
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
     * @Route("/user/change_password", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
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
     * @Route("/user/reset_password", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
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
     * @Route("/user/{token}/activate", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
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
     * @Route("/user/{token}/verify", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return Response
     */
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
     * @Route("/user/{token}/set_password", requirements={"token": "\w+"}, methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
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
     * @Route("/user/{id}/delete", methods={"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
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
