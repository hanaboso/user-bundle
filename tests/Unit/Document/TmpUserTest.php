<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class TmpUserTest
 *
 * @package UserBundleTests\Unit\Document
 */
#[CoversClass(TmpUser::class)]
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

        self::assertSame(UserTypeEnum::TMP_USER, $user->getType());
        self::assertSame('', $user->setPassword('')->getPassword());
        self::assertEquals(['id' => '', 'email' => ''], $user->toArray());
        self::assertSame('', TmpUser::from($user)->getPassword());
    }

}
