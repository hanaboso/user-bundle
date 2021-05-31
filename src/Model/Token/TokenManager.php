<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Token;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\TokenRepository as DocumentTokenRepository;
use Hanaboso\UserBundle\Repository\Entity\TokenRepository as EntityTokenRepository;
use Throwable;

/**
 * Class TokenManager
 *
 * @package Hanaboso\UserBundle\Model\Token
 */
class TokenManager
{

    /**
     * @var DocumentManager|EntityManager
     */
    private DocumentManager|EntityManager $dm;

    /**
     * TokenManager constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     */
    public function __construct(DatabaseManagerLocator $userDml, private ResourceProvider $provider)
    {
        $this->dm = $userDml->get();
    }

    /**
     * @param UserInterface $user
     *
     * @return TokenInterface
     * @throws TokenManagerException
     */
    public function create(UserInterface $user): TokenInterface
    {
        try {
            $class = $this->provider->getResource(ResourceEnum::TOKEN);
            $this->removeExistingTokens($user);
            /** @var TokenInterface $token */
            $token = new $class();
            $token->setUserOrTmpUser($user);
            $user->setToken($token);

            $this->dm->persist($token);
            $this->dm->flush();

            return $token;
        } catch (Throwable $t) {
            throw new TokenManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param string $hash
     *
     * @return TokenInterface
     * @throws TokenManagerException
     */
    public function validate(string $hash): TokenInterface
    {
        try {
            /**
             * @template    T
             * @phpstan-var class-string<T> $tokenClass
             */
            $tokenClass = $this->provider->getResource(ResourceEnum::TOKEN);
            /** @var EntityTokenRepository|DocumentTokenRepository $repo */
            $repo  = $this->dm->getRepository($tokenClass);
            $token = $repo->getFreshToken($hash);

            if (!$token) {
                throw new TokenManagerException(
                    sprintf('Token \'%s\' not valid.', $hash),
                    TokenManagerException::TOKEN_NOT_VALID,
                );
            }

            return $token;
        } catch (Throwable $t) {
            throw new TokenManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param TokenInterface $token
     *
     * @throws TokenManagerException
     */
    public function delete(TokenInterface $token): void
    {
        $this->removeExistingTokens($token->getUserOrTmpUser());
    }

    /**
     * @param UserInterface $user
     *
     * @throws TokenManagerException
     */
    private function removeExistingTokens(UserInterface $user): void
    {
        try {
            /**
             * @template    T
             * @phpstan-var class-string<T> $tokenClass
             */
            $tokenClass = $this->provider->getResource(ResourceEnum::TOKEN);
            /** @var EntityTokenRepository|DocumentTokenRepository $repo */
            $repo = $this->dm->getRepository($tokenClass);
            foreach ($repo->getExistingTokens($user) as $token) {
                $this->dm->remove($token);
            }
            $user->setToken(NULL);

            $this->dm->flush();
        } catch (Throwable $t) {
            throw new TokenManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

}
