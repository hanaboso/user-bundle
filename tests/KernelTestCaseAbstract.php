<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class KernelTestCaseAbstract
 *
 * @package Tests
 */
abstract class KernelTestCaseAbstract extends KernelTestCase
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     *
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
    }

}
