<?php

declare(strict_types=1);

namespace IPTools;

use Countable;
use IPTools\Exception\IpException;
use IPTools\Exception\NetworkException;
use IPTools\Exception\RangeException;
use Iterator;
use ReturnTypeWillChange;

/**
 * @author Safarov Alisher <alisher.safarov@outlook.com>
 * @link https://github.com/S1lentium/IPTools
 */
class Range implements Countable, Iterator
{
    use PropertyTrait;

    private IP $firstIP;
    private IP $lastIP;
    private int $position = 0;

    /**
     * @throws RangeException
     */
    public function __construct(IP $firstIP, IP $lastIP)
    {
        $this->firstIP = $firstIP;
        $this->lastIP = $lastIP;

        $this->setFirstIP($firstIP);
        $this->setLastIP($lastIP);
    }

    /**
     * @throws IpException
     * @throws RangeException
     * @throws NetworkException
     */
    public static function parse(string $data): self
    {
        if (strpos($data, '/') || strpos($data, ' ')) {
            $network = Network::parse($data);
            $firstIP = $network->getFirstIP();
            $lastIP = $network->getLastIP();
        } elseif (str_contains($data, '*')) {
            $firstIP = IP::parse(str_replace('*', '0', $data));
            $lastIP = IP::parse(str_replace('*', '255', $data));
        } elseif (strpos($data, '-')) {
            [$first, $last] = explode('-', $data, 2);
            $firstIP = IP::parse($first);
            $lastIP = IP::parse($last);
        } else {
            $firstIP = IP::parse($data);
            $lastIP = clone $firstIP;
        }

        return new self($firstIP, $lastIP);
    }

    /**
     * @throws RangeException
     * @throws IpException
     */
    public function contains(IP | Network | Range $find): bool
    {
        return match (true) {
            $find instanceof IP => (strcmp($find->inAddr(), $this->firstIP->inAddr()) >= 0)
                                   && (strcmp($find->inAddr(), $this->lastIP->inAddr()) <= 0),
            $find instanceof Range, $find instanceof Network => (strcmp($find->getFirstIP()->inAddr(), $this->firstIP->inAddr()) >= 0)
                                                                && (strcmp($find->getLastIP()->inAddr(), $this->lastIP->inAddr()) <= 0),
            default => throw new RangeException('Invalid type'),
        };
    }

    /**
     * @throws RangeException
     */
    public function setFirstIP(IP $ip): void
    {
        if (strcmp($ip->inAddr(), $this->lastIP->inAddr()) > 0) {
            throw new RangeException('First IP is grater than second');
        }

        $this->firstIP = $ip;
    }

    /**
     * @throws RangeException
     */
    public function setLastIP(IP $ip): void
    {
        if (strcmp($ip->inAddr(), $this->firstIP->inAddr()) < 0) {
            throw new RangeException('Last IP is less than first');
        }

        $this->lastIP = $ip;
    }

    public function getFirstIP(): IP
    {
        return $this->firstIP;
    }

    public function getLastIP(): IP
    {
        return $this->lastIP;
    }

    /**
     * @throws IpException
     * @throws NetworkException
     * @throws RangeException
     * @return Network[]
     */
    public function getNetworks(): array
    {
        $span = $this->getSpanNetwork();

        $networks = [];

        if ($span->getFirstIP()->inAddr() === $this->firstIP->inAddr()
            && $span->getLastIP()->inAddr() === $this->lastIP->inAddr()
        ) {
            $networks = [$span];
        } else {
            if ($span->getFirstIP()->inAddr() !== $this->firstIP->inAddr()) {
                $excluded = $span->exclude($this->firstIP->prev()->__toString());
                foreach ($excluded as $network) {
                    if (strcmp($network->getFirstIP()->inAddr(), $this->firstIP->inAddr()) >= 0) {
                        $networks[] = $network;
                    }
                }
            }

            if ($span->getLastIP()->inAddr() !== $this->lastIP->inAddr()) {
                if ($networks === []) {
                    $excluded = $span->exclude($this->lastIP->next()->__toString());
                } else {
                    $excluded = array_pop($networks);
                    $excluded = $excluded->exclude($this->lastIP->next()->__toString());
                }

                foreach ($excluded as $network) {
                    $networks[] = $network;
                    if ($network->getLastIP()->inAddr() === $this->lastIP->inAddr()) {
                        break;
                    }
                }
            }
        }

        return $networks;
    }

    /**
     * @throws NetworkException
     * @throws IpException
     */
    public function getSpanNetwork(): Network
    {
        $xorIP = IP::parseInAddr($this->getFirstIP()->inAddr() ^ $this->getLastIP()->inAddr());

        preg_match('/^(0*)/', $xorIP->toBin(), $match);

        $prefixLength = strlen($match[1]);

        $ip = IP::parseBin(str_pad(substr($this->getFirstIP()->toBin(), 0, $prefixLength), $xorIP->getMaxPrefixLength(), '0'));

        return new Network($ip, Network::prefix2netmask($prefixLength, $ip->getVersion()));
    }

    /**
     * @throws IpException
     */
    #[ReturnTypeWillChange]
    public function current(): IP
    {
        return $this->firstIP->next($this->position);
    }

    #[ReturnTypeWillChange]
    public function key(): int
    {
        return $this->position;
    }

    #[ReturnTypeWillChange]
    public function next(): void
    {
        ++$this->position;
    }

    #[ReturnTypeWillChange]
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @throws IpException
     */
    #[ReturnTypeWillChange]
    public function valid(): bool
    {
        return strcmp($this->firstIP->next($this->position)->inAddr(), $this->lastIP->inAddr()) <= 0;
    }

    #[ReturnTypeWillChange]
    public function count(): int
    {
        return (int) bcadd(bcsub($this->lastIP->toLong(), $this->firstIP->toLong()), '1');
    }
}