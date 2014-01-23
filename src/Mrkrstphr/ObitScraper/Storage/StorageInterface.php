<?php

namespace Mrkrstphr\ObitScraper\Storage;

use Mrkrstphr\ObitScraper\Model\Obituary;

/**
 * Interface StorageInterface
 * @package ObitRipper\Storage
 */
interface StorageInterface
{
    /**
     * Stores the passed obituary in the storage.
     *
     * @param Obituary $obituary
     * @return boolean
     */
    public function store(Obituary $obituary);
}
