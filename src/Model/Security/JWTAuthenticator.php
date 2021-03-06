<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Throwable;

/**
 * Class JWTAuthenticator
 *
 * @package Hanaboso\UserBundle\Model\Security
 */
final class JWTAuthenticator extends AbstractAuthenticator
{

    private const EMAIL = 'email';

    /**
     * JWTAuthenticator constructor.
     *
     * @param SecurityManager $securityManager
     */
    public function __construct(private SecurityManager $securityManager)
    {
    }

    /**
     * @param Request $request
     *
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        $request;

        return TRUE;
    }

    /**
     * @param Request $request
     *
     * @return PassportInterface
     */
    public function authenticate(Request $request): PassportInterface
    {
        try {
            $request;

            $token = $this->securityManager->jwtVerifyAccessToken();

            /** @var string $email */
            $email = $token->claims()->get(self::EMAIL);

            return new SelfValidatingPassport(
                new UserBadge(
                    $email,
                    fn($email) => clone $this->securityManager->getUser($email),
                ),
            );
        } catch (Throwable $t) {
            throw new AuthenticationException('Not valid token', $t->getCode(), $t);
        }
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $firewallName
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request;
        $token;
        $firewallName;

        return NULL;
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request;
        $data = [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

}
