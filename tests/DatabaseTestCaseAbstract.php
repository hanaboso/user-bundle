<?php declare(strict_types=1);

namespace Tests;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package Tests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    /**
     * @var Session
     */
    protected $session;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new Session();
        $this->dm->getConnection()->dropDatabase('pipes');
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     * @param mixed $document
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush($document);
    }

}
