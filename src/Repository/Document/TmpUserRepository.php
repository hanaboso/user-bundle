<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\UserBundle\Document\TmpUser;

/**
 * Class TmpUserRepository
 *
 * @package Hanaboso\UserBundle\Repository\Document
 *
 * @phpstan-extends DocumentRepository<TmpUser>
 */
class TmpUserRepository extends DocumentRepository
{

}
