<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository as OdmRepo;
use Doctrine\ORM\EntityRepository as OrmRepo;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\InvalidClaimException;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Throwable;

/**
 * Class SecurityManager
 *
 * @package Hanaboso\UserBundle\Model\Security
 */
class SecurityManager
{

    private const ACCESS_TOKEN_EXPIRATION  = 420;
    private const REFRESH_TOKEN_EXPIRATION = 900;
    private const REFRESH_TOKEN            = 'refreshToken';
    private const AUTHORIZATION            = 'Authorization';
    private const ID                       = 'id';
    private const EXP                      = 'exp';
    private const EMAIL                    = 'email';
    private const PERMISSIONS              = 'permissions';

    /**
     * @var string
     */
    protected string $resourceUser = ResourceEnum::USER;

    /**
     * @var OrmRepo<User|DmUser>|OdmRepo<User|DmUser>
     */
    protected OrmRepo|OdmRepo $userRepository;

    /**
     * @var PasswordHasherInterface
     */
    protected PasswordHasherInterface $encoder;

    /**
     * SecurityManager constructor.
     *
     * @phpstan-param 'None'|'Lax'|'Strict' $sameSite
     *
     * @param DatabaseManagerLocator $userDml
     * @param PasswordHasherFactory  $encoderFactory
     * @param ResourceProvider       $provider
     * @param RequestStack           $requestStack
     * @param JWSBuilder             $jwsBuilder
     * @param JWSLoader              $jwsLoader
     * @param JWK                    $jwk
     * @param ClaimCheckerManager    $claimCheckerManager
     * @param string                 $sameSite
     *
     * @throws ResourceProviderException
     */
    public function __construct(
        protected DatabaseManagerLocator $userDml,
        protected PasswordHasherFactory $encoderFactory,
        protected ResourceProvider $provider,
        private RequestStack $requestStack,
        private readonly JWSBuilder $jwsBuilder,
        private readonly JWSLoader $jwsLoader,
        private readonly JWK $jwk,
        private ClaimCheckerManager $claimCheckerManager,
        private readonly string $sameSite,
    ) {
        $this->setUserResource($this->resourceUser);
    }

    /**
     * @param string $resource
     *
     * @return SecurityManager
     * @throws ResourceProviderException
     */
    public function setUserResource(string $resource): SecurityManager
    {
        $this->resourceUser = $resource;
        /** @phpstan-var class-string<User|DmUser> $userClass */
        $userClass            = $this->provider->getResource($this->resourceUser);
        $this->userRepository = $this->userDml->get()->getRepository($userClass);
        $this->encoder        = $this->encoderFactory->getPasswordHasher($userClass);

        return $this;
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws DateTimeException
     * @throws SecurityManagerException
     */
    public function login(array $data): array
    {
        $user = $this->getUser($data['email']);
        $this->validateUser($user, $data);

        unset($data['email'], $data['password']);
        $this->setNewRefreshToken($user, $data);
        $token = $this->createToken(
            $user->getId(),
            $user->getEmail(),
            self::ACCESS_TOKEN_EXPIRATION,
            $this->getPermissions($user),
            $data,
        );

        return [$user, $token];
    }

    /**
     * @return mixed[]
     * @throws SecurityManagerException
     */
    public function getLoggedUser(): array
    {
        try {
            $token = $this->jwtVerifyAccessToken();
            $user  = $this->getUser($token[self::EMAIL]);
            $this->setNewRefreshToken($user, $token);
            $token = $this->createToken(
                $user->getId(),
                $user->getEmail(),
                self::ACCESS_TOKEN_EXPIRATION,
                $this->getPermissions($user),
                $token,
            );
        } catch (Throwable $t) {
            throw new SecurityManagerException($t->getMessage(), $t->getCode(), $t);
        }

        return [$user, $token];
    }

    /**
     * @param string $rawPassword
     *
     * @return string
     */
    public function encodePassword(string $rawPassword): string
    {
        return $this->encoder->hash($rawPassword);
    }

    /**
     * @param UserInterface $user
     * @param mixed[]       $data
     *
     * @throws SecurityManagerException
     */
    public function validateUser(UserInterface $user, array $data): void
    {
        try {
            if (!$this->encoder->verify($user->getPassword() ?? '', $data['password'])) {
                throw new Exception('Invalid password');
            }
        } catch (Throwable) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $user->getEmail()),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID,
            );
        }
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    public function getJwt(Request $request): ?string
    {
        return $request->headers->get(self::AUTHORIZATION) ?? $request->query->get(self::AUTHORIZATION);
    }

    /**
     * @return mixed[]
     * @throws SecurityManagerException
     */
    public function jwtVerifyAccessToken(): array
    {
        /** @var Request $request */
        $request          = $this->requestStack->getCurrentRequest();
        $exceptionMessage = 'Not valid token';
        try {
            $jwt = $this->getJwt($request);

            if (!$jwt) {
                throw new SecurityManagerException($exceptionMessage);
            }

            try {
                $token = $this->jwsLoader->loadAndVerifyWithKey(
                    str_replace('Bearer ', '', $jwt),
                    $this->jwk,
                    $signature,
                );

                $claims = Json::decode($token->getPayload());

                if ($claims['exp'] < DateTimeUtils::getUtcDateTime()->getTimestamp()) {
                    $refreshToken = $request->cookies->get(self::REFRESH_TOKEN);

                    $jwtRefreshToken = $this->jwsLoader->loadAndVerifyWithKey(
                        str_replace('Bearer ', '', $refreshToken),
                        $this->jwk,
                        $signature,
                    );

                    $claims = Json::decode($jwtRefreshToken->getPayload());
                }

                $this->claimCheckerManager->check($claims);

                return $claims;
            } catch (InvalidClaimException $exception) {
                throw $exception;
            } catch (Throwable) {
                throw new SecurityManagerException($exceptionMessage);
            }
        } catch (Throwable $t) {
            throw new SecurityManagerException($t->getMessage(), SecurityManagerException::USER_NOT_LOGGED, $t);
        }
    }

    /**
     * @param UserInterface $user
     *
     * @return mixed[]
     */
    public function getPermissions(UserInterface $user): array
    {
        $user;

        return [];
    }

    /**
     * @param string $email
     *
     * @return UserInterface
     * @throws SecurityManagerException
     */
    public function getUser(string $email): UserInterface
    {
        /** @var UserInterface|null $user */
        $user = $this->userRepository->findOneBy(
            [
                'email'   => $email,
                'deleted' => FALSE,
            ],
        );

        if (!$user) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $email),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID,
            );
        }

        return $user;
    }

    /**
     * @param string         $id
     * @param string         $email
     * @param int            $expiration
     * @param string[]       $permissions
     * @param string[]|int[] $additionalData
     *
     * @return string
     * @throws DateTimeException
     */
    public function createToken(
        string $id,
        string $email,
        int $expiration,
        array $permissions = [],
        array $additionalData = [],
    ): string {
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload(
                Json::encode(
                    array_merge(
                        $additionalData,
                        [
                            self::EXP         => DateTimeUtils::getUtcDateTimeImmutable(
                                sprintf('+%s seconds', $expiration),
                            )->getTimestamp(),
                            self::ID          => $id,
                            self::EMAIL       => $email,
                            self::PERMISSIONS => $permissions,
                        ],
                    ),
                ),
            )
            ->addSignature($this->jwk, ['alg' => 'HS512'])
            ->build();

        return $this->jwsLoader->getSerializerManager()->serialize(CompactSerializer::NAME, $jws);
    }

    /**
     * ------------------------------------------- HELPERS ---------------------------------
     */

    /**
     * @param UserInterface $user
     * @param string[]      $additionalData
     *
     * @throws DateTimeException
     */
    private function setNewRefreshToken(UserInterface $user, array $additionalData = []): void
    {
        /** @var Request $request */
        $request     = $this->requestStack->getCurrentRequest();
        $permissions = $this->getPermissions($user);

        setcookie(
            self::REFRESH_TOKEN,
            $this->createToken(
                $user->getId(),
                $user->getEmail(),
                self::REFRESH_TOKEN_EXPIRATION,
                $permissions,
                $additionalData,
            ),
            [
                'secure'   => $request->isSecure(),
                'expires'  => time() + self::REFRESH_TOKEN_EXPIRATION,
                'samesite' => $this->sameSite,
                'httponly' => TRUE,
            ],
        );
    }

}
