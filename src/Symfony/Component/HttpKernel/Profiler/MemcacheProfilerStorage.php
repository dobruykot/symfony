<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Profiler;

use Memcache;

/**
 * Memcache Profiler Storage
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class MemcacheProfilerStorage extends BaseMemcacheProfilerStorage
{

    /**
     * @var Memcache
     */
    private $memcache;

    /**
     * Internal convenience method that returns the instance of the Memcache
     *
     * @return Memcache
     */
    protected function getMemcache()
    {
        if (null === $this->memcache) {
            if (!preg_match('#^memcache://(.*)/(.*)$#', $this->dsn, $matches)) {
                throw new \RuntimeException('Please check your configuration. You are trying to use Memcache with an invalid dsn. "' . $this->dsn . '"');
            }

            $host = $matches[1];
            $port = $matches[2];

            $memcache = new Memcache;
            $memcache->addServer($host, $port);

            $this->memcache = $memcache;
        }

        return $this->memcache;
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($key)
    {
        return $this->getMemcache()->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value, $expiration = 0)
    {
        return $this->getMemcache()->set($key, $value, false, $expiration);
    }

    /**
     * {@inheritdoc}
     */
    protected function flush()
    {
        return $this->getMemcache()->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function appendValue($key, $value, $expiration = 0)
    {
        $memcache = $this->getMemcache();

        if (method_exists($memcache, 'append')) {

            //Memcache v3.0
            if (!$result = $memcache->append($key, $value, false, $expiration)) {
                return $memcache->set($key, $value, false, $expiration);
            }

            return $result;
        }

        //simulate append in Memcache <3.0
        $content = $memcache->get($key);

        return $memcache->set($key, $content . $value, false, $expiration);
    }

}
