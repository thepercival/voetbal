<?php

namespace Voetbal\CacheItemDb;

use Voetbal\CacheItemDb;

/**
 * Class Repository
 * @package Voetbal
 */
class Repository extends \Voetbal\Repository
{
    public function getItem(string $name): ?string
    {
        /** @var CacheItemDb|null $cacheItem */
        $cacheItem = $this->findOneBy(["name" => $name]);
        if ($cacheItem !== null &&
            ($cacheItem->getExpireDateTime() === null || $cacheItem->getExpireDateTime() > (new \DateTimeImmutable()))
        ) {
            $handle = $cacheItem->getValue();
            if (is_string($handle)) {
                return $handle;
            }
            $content = stream_get_contents($handle);
            rewind($handle);
            return $content;
        }
        return null;
    }

    public function saveItem(string $name, $value, int $nrOfMinutesToExpire = null)
    {
        $cacheItem = $this->findOneBy(["name" => $name]);
        $expireDateTime = null;
        if ($nrOfMinutesToExpire !== null) {
            $expireDateTime = new \DateTimeImmutable();
            $expireDateTime = $expireDateTime->modify("+".$nrOfMinutesToExpire." minutes");
        }
        if ($cacheItem === null) {
            $cacheItem = new CacheItemDb($name, $value, $expireDateTime);
        } else {
            $cacheItem->setValue($value);
            $cacheItem->setExpireDateTime($expireDateTime);
        }
        $this->save($cacheItem);
        return $cacheItem->getValue();
    }

    public function removeItem(string $name)
    {
        $cacheItem = $this->getItem($name);
        if ($cacheItem !== null) {
            $this->removeItem($cacheItem);
        }
    }
}
