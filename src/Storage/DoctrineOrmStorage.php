<?php

namespace Mrkrstphr\ObitScraper\Storage;

use Doctrine\ORM\EntityManager;
use Mrkrstphr\ObitScraper\Model\Obituary;

/**
 * Class DoctrineOrmStorage
 * @package ObitRipper\Storage
 */
class DoctrineOrmStorage implements StorageInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function store(Obituary $obituary)
    {
        $this->entityManager->persist($obituary);
        $this->entityManager->flush();
    }
}
