<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Entity;

use Exception;
use Hanaboso\UserBundle\Entity\TmpUser;
use Hanaboso\UserBundle\Entity\Token;
use Hanaboso\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Throwable;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class TokenTest
 *
 * @package UserBundleTests\Unit\Entity
 */
#[CoversClass(Token::class)]
final class TokenTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testEntity(): void
    {
        $unknownUser = self::createMock(User::class);
        $unknownUser->method('getType')->willReturn('Unknown');

        $token = new Token();
        $token->getCreated();

        try {
            $token->setUserOrTmpUser($unknownUser);
            self::fail('Something gone wrong!');
        } catch (Throwable $throwable) {
            self::assertEquals("Unknown user type 'Unknown'!", $throwable->getMessage());
        }

        try {
            $token->getUserOrTmpUser();
            self::fail('Something gone wrong!');
        } catch (Throwable $throwable) {
            self::assertEquals('User is not set.', $throwable->getMessage());
        }

        $token->setUserOrTmpUser(new TmpUser())->getUserOrTmpUser();
        $token->setUserOrTmpUser(new User())->getUserOrTmpUser();
        $token->setUser(new User())->getUser();
        $token->setTmpUser(new TmpUser())->getTmpUser();

        self::assertMatchesRegularExpression('/\w{13}/', $token->getHash());
    }

}
