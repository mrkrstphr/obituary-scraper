<?php

namespace Mrkrstphr\ObitScraper;

use DateTime;
use InvalidArgumentException;
use Goutte\Client;
use Mrkrstphr\ObitScraper\Model\Obituary;
use Mrkrstphr\ObitScraper\Storage\StorageInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Scraper
 * @package Mrkrstphr\ObitScraper
 */
class Scraper
{
    /**
     * @var \Goutte\Client
     */
    protected $client;

    /**
     * @var Storage\StorageInterface
     */
    protected $storage;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param Client $client
     * @param StorageInterface $storage
     * @param array $config
     * @throws InvalidArgumentException
     */
    public function __construct(Client $client, StorageInterface $storage, array $config = array())
    {
        if (!isset($config['url'])) {
            throw new InvalidArgumentException('config must declare a "url"');
        }

        $this->client = $client;
        $this->storage = $storage;
        $this->config = $config;
    }

    /**
     * Store all obituaries from $start to $end.
     *
     * @param DateTime $start
     * @param DateTime $end
     */
    public function runFrom(\DateTime $start, \DateTime $end)
    {
        $namesScraped = [];

        while ($start <= $end) {
            $crawler = $this->client->request(
                'GET',
                str_replace(':date:', $start->format('Ymd'), $this->config['url'])
            );

            $crawler->filter('div.Name a')->each(function (Crawler $node) use ($namesScraped) {
                $name = trim($node->text());
                if (in_array($name, $namesScraped)) {
                    return;
                }

                $this->ripObituary($node->attr('href'));

                $namesScraped[] = $name;
            });

            $start->add(new \DateInterval('P1D'));
        }
    }

    /**
     * Parse the obituary stored at $obitUrl, store it in the storage.
     *
     * @param string $obitUrl
     */
    public function ripObituary($obitUrl)
    {
        $personCrawler = $this->client->request('GET', $obitUrl);

        $obituary = new Obituary();

        $personCrawler->filter('span[itemprop="givenName"]')->each(function (Crawler $node) use ($obituary) {
            $obituary->setName($node->text());
        });

        $personCrawler->filter('span[itemprop="familyName"]')->each(function (Crawler $node) use ($obituary) {
            $obituary->setName($obituary->getName() . ' ' . $node->text());
        });

        $personCrawler->filter('div#obitText span[itemprop="description"]')->each(function (Crawler $node) use ($obituary) {
            $obituary->setBody($node->text());
        });

        $personCrawler->filter('div.obitPublished')->each(function (Crawler $node) use ($obituary) {
            $text = trim(html_entity_decode(preg_replace('~\x{00a0}~siu', ' ', $node->text())));

            if (preg_match('/Published in (.*) from (.*) to (.*)/i', $text, $matches)) {
                $obituary->setSource($matches[1]);
                $obituary->setPublished(new DateTime($matches[3]));
            } else if (preg_match('/Published in (.*) on (.*)/i', $text, $matches)) {
                $obituary->setSource($matches[1]);
                $obituary->setPublished(new DateTime($matches[2]));
            }
        });

        $this->storage->store($obituary);
    }
}
