<?php declare(strict_types=1);

namespace UserBundleTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\UserBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package UserBundleTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use ControllerTestTrait;
    use DatabaseTestTrait;
    use JwtUserTrait;

    /**
     * @var User
     */
    protected User $loggedUser;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->startClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
        [$this->loggedUser,] = $this->loginUser('test@example.com', 'password');
    }

}
