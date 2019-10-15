<?php declare(strict_types=1);

namespace UserBundleTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package UserBundleTests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    use DatabaseTestTrait;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
    }

}
