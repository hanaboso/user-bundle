<?php declare(strict_types=1);

namespace UserBundleTests;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

/**
 * Trait JwtUserTrait
 *
 * @package UserBundleTests
 */
trait JwtUserTrait
{

    /**
     * @var NativePasswordHasher|null
     */
    protected ?NativePasswordHasher $encoder = NULL;

    /**
     * @var string
     */
    protected static string $AUTHORIZATION = 'HTTP_Authorization';

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     * @throws Exception
     */
    protected function createUser(string $username = 'email@example.com', string $password = 'passw0rd'): User
    {
        $password = $this->getEncoder()->hash($password);
        $user     = new User();
        $user
            ->setEmail($username)
            ->setPassword($password);
        $this->pfd($user);

        return $user;
    }

    /**
     * @param string $username
     * @param string $password
     * @param int    $expiration
     *
     * @return mixed[]
     * @throws Exception
     */
    protected function loginUser(
        string $username = 'email@example.com',
        string $password = 'passw0rd',
        int $expiration = 3_600,
    ): array
    {
        $user            = $this->createUser($username, $password);
        $securityManager = self::getContainer()->get('hbpf.user.manager.security');
        $jwt             = $securityManager->createToken($user->getId(), $user->getEmail(), $expiration);

        return [$user, $jwt];
    }

    /**
     * @param int $cost
     *
     * @return NativePasswordHasher
     */
    protected function getEncoder(int $cost = 4): NativePasswordHasher
    {
        if (!$this->encoder) {
            $this->encoder = new NativePasswordHasher(cost: $cost);
        }

        return $this->encoder;
    }

    /**
     * @param string $jwt
     * @throws Exception
     */
    protected function injectJwt(string $jwt): void
    {
        $req = self::getContainer()->get('request_stack');
        $req->push(new Request(server: [self::$AUTHORIZATION => $jwt]));
    }

}
