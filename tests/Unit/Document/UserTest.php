<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Document\UserAbstract;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class UserTest
 *
 * @package UserBundleTests\Unit\Document
 */
#[CoversClass(User::class)]
#[CoversClass(UserAbstract::class)]
final class UserTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $timestamp = DateTimeUtils::getUtcDateTime();

        $tmpUser = new TmpUser();
        $tmpUser->setEmail('');
        $tmpUser->setDeleted(TRUE);

        $user = new User();
        $user->setEmail('');
        $this->setProperty($user, 'id', '');

        $user->setPassword('aa');
        $user->setUpdated($timestamp);
        $user->getCreated();
        $user->setToken($user->getToken());
        $user->setDeleted(TRUE);

        self::assertEquals(UserTypeEnum::USER, $user->getType());
        self::assertEquals('', User::from($tmpUser)->getEmail());
        self::assertEquals('', $user->getEmail());
        self::assertEquals('aa', $user->getPassword());
        self::assertEquals('', $user->getUsername());
        self::assertEquals('', $user->getSalt());
        self::assertEquals('', $user->getUserIdentifier());
        self::assertEquals(['admin'], $user->getRoles());
        self::assertEquals(
            $timestamp->format(DateTimeUtils::DATE_TIME),
            $user->getUpdated()->format(DateTimeUtils::DATE_TIME),
        );
        self::assertEquals(['id' => '', 'email' => ''], $user->toArray());

        $user->eraseCredentials();
        self::assertEmpty($user->getPassword());
    }

}
