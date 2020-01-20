<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class UserTest
 *
 * @package UserBundleTests\Unit\Document
 *
 * @covers  \Hanaboso\UserBundle\Document\User
 * @covers  \Hanaboso\UserBundle\Document\UserAbstract
 */
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

        $user->setPassword('');
        $user->setUpdated($timestamp);
        $user->getCreated();
        $user->setToken($user->getToken());
        $user->setDeleted(TRUE);

        self::assertEquals(UserTypeEnum::USER, $user->getType());
        self::assertEquals('', User::from($tmpUser)->getEmail());
        self::assertEquals('', $user->getEmail());
        self::assertEquals('', $user->getPassword());
        self::assertEquals('', $user->getUsername());
        self::assertEquals('', $user->getSalt());
        self::assertEquals(['admin'], $user->getRoles());
        self::assertEquals(
            $timestamp->format(DateTimeUtils::DATE_TIME),
            $user->getUpdated()->format(DateTimeUtils::DATE_TIME)
        );
        self::assertEquals(['id' => '', 'email' => ''], $user->toArray());

        self::expectException(Exception::class);
        $user->eraseCredentials();
    }

}
