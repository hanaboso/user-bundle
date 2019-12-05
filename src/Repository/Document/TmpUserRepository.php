<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class TmpUserRepository
 *
 * @package Hanaboso\UserBundle\Repository\Document
 *
 * @phpstan-extends DocumentRepository<\Hanaboso\UserBundle\Document\TmpUser>
 */
class TmpUserRepository extends DocumentRepository
{

}
