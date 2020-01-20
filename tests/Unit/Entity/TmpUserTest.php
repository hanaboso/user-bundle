<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Entity;

use Exception;
use Hanaboso\UserBundle\Entity\TmpUser;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class TmpUserTest
 *
 * @package UserBundleTests\Unit\Entity
 *
 * @covers  \Hanaboso\UserBundle\Entity\TmpUser
 */
final class TmpUserTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testEntity(): void
    {
        $user = new TmpUser();
        $user->setEmail('');
        $user->setToken($user->getToken());
        $this->setProperty($user, 'id', '');

        self::assertEquals(UserTypeEnum::TMP_USER, $user->getType());
        self::assertEquals('', $user->setPassword('')->getPassword());
        self::assertEquals(['id' => '', 'email' => ''], $user->toArray());
        self::assertEquals('', TmpUser::from($user)->getPassword());
    }

}
