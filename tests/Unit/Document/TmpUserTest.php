<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class TmpUserTest
 *
 * @package UserBundleTests\Unit\Document
 *
 * @covers  \Hanaboso\UserBundle\Document\TmpUser
 */
final class TmpUserTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $user = new TmpUser();
        $user->setEmail('');
        $this->setProperty($user, 'id', '');

        self::assertEquals(UserTypeEnum::TMP_USER, $user->getType());
        self::assertEquals('', $user->setPassword('')->getPassword());
        self::assertEquals(['id' => '', 'email' => ''], $user->toArray());
        self::assertEquals('', TmpUser::from($user)->getPassword());
    }

}
