<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Token;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\TokenRepository as DocumentTokenRepository;
use Hanaboso\UserBundle\Repository\Entity\TokenRepository as EntityTokenRepository;

/**
 * Class TokenManager
 *
 * @package Hanaboso\UserBundle\Manager
 */
class TokenManager
{

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * TokenManager constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     */
    public function __construct(DatabaseManagerLocator $userDml, ResourceProvider $provider)
    {
        $this->dm       = $userDml->get();
        $this->provider = $provider;
    }

    /**
     * @param UserInterface $user
     *
     * @return TokenInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws UserException
     */
    public function create(UserInterface $user): TokenInterface
    {
        $class = $this->provider->getResource(ResourceEnum::TOKEN);
        /** @var TokenInterface $token */
        $this->removeExistingTokens($user);
        $token = new $class();
        $user->getType() === UserTypeEnum::USER ? $token->setUser($user) : $token->setTmpUser($user);
        $user->setToken($token);

        $this->dm->persist($token);
        $this->dm->flush();

        return $token;
    }

    /**
     * @param string $hash
     *
     * @return TokenInterface
     * @throws TokenManagerException
     * @throws UserException
     * @throws NonUniqueResultException
     */
    public function validate(string $hash): TokenInterface
    {
        /** @var EntityTokenRepository|DocumentTokenRepository $repo */
        $repo  = $this->dm->getRepository($this->provider->getResource(ResourceEnum::TOKEN));
        $token = $repo->getFreshToken($hash);

        if (!$token) {
            throw new TokenManagerException(
                sprintf('Token \'%s\' not valid.', $hash),
                TokenManagerException::TOKEN_NOT_VALID
            );
        }

        return $token;

    }

    /**
     * @param TokenInterface $token
     *
     * @throws ORMException
     * @throws UserException
     * @throws OptimisticLockException
     */
    public function delete(TokenInterface $token): void
    {
        $this->removeExistingTokens($token->getUserOrTmpUser());
        $this->dm->flush();
    }

    /**
     * @param UserInterface $user
     *
     * @throws UserException
     * @throws ORMException
     */
    private function removeExistingTokens(UserInterface $user): void
    {
        /** @var EntityTokenRepository|DocumentTokenRepository $repo */
        $repo = $this->dm->getRepository($this->provider->getResource(ResourceEnum::TOKEN));
        foreach ($repo->getExistingTokens($user) as $token) {
            $this->dm->remove($token);
        }
        $user->setToken(NULL);
    }

}