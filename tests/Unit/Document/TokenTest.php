<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class TokenTest
 *
 * @package UserBundleTests\Unit\Document
 *
 * @covers  \Hanaboso\UserBundle\Document\Token
 */
final class TokenTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        /** @var UserInterface|MockObject $unknownUser */
        $unknownUser = self::createMock(User::class);
        $unknownUser->method('getType')->willReturn('Unknown');

        $token = new Token();

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

        self::assertRegExp('/\w{13}/', $token->getHash());
    }

}
