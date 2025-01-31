<?php

declare(strict_types=1);

namespace IPTools;

use Countable;
use IPTools\Exception\IpException;
use IPTools\Exception\NetworkException;
use IPTools\Exception\RangeException;
use Iterator;
use ReturnTypeWillChange;
use Stringable;

/**
 * @author Safarov Alisher <alisher.safarov@outlook.com>
 * @link https://github.com/S1lentium/IPTools
 */
class Network implements Iterator, Countable, Stringable
{
	use PropertyTrait;

	private IP $ip;
	private IP $netmask;
	private int $position = 0;

    /**
     * @throws NetworkException
     */
    public function __construct(IP $ip, IP $netmask)
	{
		$this->setIP($ip);
		$this->setNetmask($netmask);
	}

    /**
     * @throws IpException
     */
    public function __toString(): string
	{
		return $this->getCIDR();
	}

    /**
     * @throws IpException
     * @throws NetworkException
     */
    public static function parse(string $data): self
	{
		if (preg_match('~^(.+?)/(\d+)$~', $data, $matches)) {
			$ip      = IP::parse($matches[1]);
			$netmask = self::prefix2netmask((int)$matches[2], $ip->getVersion());
		} elseif (strpos($data,' ')) {
			[$ip, $netmask] = explode(' ', $data, 2);
			$ip      = IP::parse($ip);
			$netmask = IP::parse($netmask);
		} else {
			$ip      = IP::parse($data);
			$netmask = self::prefix2netmask($ip->getMaxPrefixLength(), $ip->getVersion());
		}

		return new self($ip, $netmask);
	}

    /**
     * @throws IpException
     * @throws NetworkException
     */
	public static function prefix2netmask(int $prefixLength, string $version): IP
    {
		if (!in_array($version, [IP::IP_V4, IP::IP_V6])) {
			throw new NetworkException("Wrong IP version");
		}

		$maxPrefixLength = $version === IP::IP_V4
			? IP::IP_V4_MAX_PREFIX_LENGTH
			: IP::IP_V6_MAX_PREFIX_LENGTH;

		if (!is_numeric($prefixLength)
			|| !($prefixLength >= 0 && $prefixLength <= $maxPrefixLength)
		) {
			throw new NetworkException('Invalid prefix length');
		}

		$binIP = str_pad(str_pad('', (int)$prefixLength, '1'), $maxPrefixLength, '0');

		return IP::parseBin($binIP);
	}

	/**
     * @param IP $ip ip
     */
    public static function netmask2prefix(IP $ip): int
	{
		return strlen(rtrim($ip->toBin(), '0'));
	}

	/**
	 * @throws NetworkException
	 */
	public function setIP(IP $ip): void
    {
		if ($this->netmask->getVersion() !== $ip->getVersion()) {
			throw new NetworkException('IP version is not same as Netmask version');
		}

		$this->ip = $ip;
	}

	/**
	 * @throws NetworkException
	 */
	public function setNetmask(IP $ip): void
    {
		if (!preg_match('/^1*0*$/',$ip->toBin())) {
			throw new NetworkException('Invalid Netmask address format');
		}

		if ($ip->getVersion() !== $this->ip->getVersion()) {
			throw new NetworkException('Netmask version is not same as IP version');
		}

		$this->netmask = $ip;
	}

    /**
     * @throws NetworkException
     * @throws IpException
     */
	public function setPrefixLength(int $prefixLength): void
    {
		$this->setNetmask(self::prefix2netmask($prefixLength, $this->ip->getVersion()));
	}

	public function getIP(): IP
    {
		return $this->ip;
	}

	public function getNetmask(): IP
    {
		return $this->netmask;
	}

    /**
     * @throws IpException
     */
    public function getNetwork(): IP
	{
		return new IP(inet_ntop($this->getIP()->inAddr() & $this->getNetmask()->inAddr()));
	}

	/**
	 * @return int
	 */
	public function getPrefixLength(): int
	{
		return self::netmask2prefix($this->getNetmask());
	}

    /**
     * @throws IpException
     */
    public function getCIDR(): string
	{
		return sprintf('%s/%s', $this->getNetwork(), $this->getPrefixLength());
	}

    /**
     * @throws IpException
     */
    public function getWildcard(): IP
	{
		return new IP(inet_ntop(~$this->getNetmask()->inAddr()));
	}

    /**
     * @throws IpException
     */
    public function getBroadcast(): IP
	{
		return new IP(inet_ntop($this->getNetwork()->inAddr() | ~$this->getNetmask()->inAddr()));
	}

    /**
     * @throws IpException
     */
	public function getFirstIP(): IP
    {
		return $this->getNetwork();
	}

    /**
     * @throws IpException
     */
	public function getLastIP(): IP
    {
		return $this->getBroadcast();
	}

	public function getBlockSize(): int|string
    {
		$maxPrefixLength = $this->ip->getMaxPrefixLength();
		$prefixLength = $this->getPrefixLength();

		if ($this->ip->getVersion() === IP::IP_V6) {
			return bcpow('2', (string)($maxPrefixLength - $prefixLength));
		}

		return 2 ** ($maxPrefixLength - $prefixLength);
	}

    /**
     * @throws RangeException
     * @throws IpException
     */
    public function getHosts(): Range
	{
		$firstHost = $this->getNetwork();
		$lastHost = $this->getBroadcast();

		if ($this->ip->getVersion() === IP::IP_V4 && $this->getBlockSize() > 2) {
            $firstHost = IP::parseBin(substr($firstHost->toBin(), 0, $firstHost->getMaxPrefixLength() - 1) . '1');
            $lastHost  = IP::parseBin(substr($lastHost->toBin(), 0, $lastHost->getMaxPrefixLength() - 1) . '0');
        }

		return new Range($firstHost, $lastHost);
	}

    /**
     * @throws IpException
     * @throws RangeException
     * @throws NetworkException
     */
    public function exclude($exclude): array
	{
		$exclude = self::parse($exclude);

		if (strcmp($exclude->getFirstIP()->inAddr() , $this->getLastIP()->inAddr()) > 0
			|| strcmp($exclude->getLastIP()->inAddr() , $this->getFirstIP()->inAddr()) < 0
		) {
			throw new NetworkException('Exclude subnet not within target network');
		}

		$networks = [];

		$newPrefixLength = $this->getPrefixLength() + 1;
		if ($newPrefixLength > $this->ip->getMaxPrefixLength()) {
		    return $networks;
        }

		$lower = clone $this;
		$lower->setPrefixLength($newPrefixLength);

		$upper = clone $lower;
		$upper->setIP($lower->getLastIP()->next());

		while ($newPrefixLength <= $exclude->getPrefixLength()) {
			$range = new Range($lower->getFirstIP(), $lower->getLastIP());
			if ($range->contains($exclude)) {
				$matched   = $lower;
				$unmatched = $upper;
			} else {
				$matched   = $upper;
				$unmatched = $lower;
			}

			$networks[] = clone $unmatched;

			if (++$newPrefixLength > $this->getNetwork()->getMaxPrefixLength()) {
                break;
            }

			$matched->setPrefixLength($newPrefixLength);
			$unmatched->setPrefixLength($newPrefixLength);
			$unmatched->setIP($matched->getLastIP()->next());
		}

		sort($networks);

		return $networks;
	}

    /**
     * @throws IpException
     * @throws NetworkException
     */
	public function moveTo(int $prefixLength): array
	{
		$maxPrefixLength = $this->ip->getMaxPrefixLength();

		if ($prefixLength <= $this->getPrefixLength() || $prefixLength > $maxPrefixLength) {
			throw new NetworkException('Invalid prefix length ');
		}

		$ip = self::prefix2netmask($prefixLength, $this->ip->getVersion());
		$networks = [];

		$subnet = clone $this;
		$subnet->setPrefixLength($prefixLength);

		while ($subnet->ip->inAddr() <= $this->getLastIP()->inAddr()) {
			$networks[] = $subnet;
			$subnet = new self($subnet->getLastIP()->next(), $ip);
		}

		return $networks;
	}

    /**
     * @throws IpException
     */
    #[ReturnTypeWillChange]
	public function current(): IP
	{
		return $this->getFirstIP()->next($this->position);
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
		return strcmp($this->getFirstIP()->next($this->position)->inAddr(), $this->getLastIP()->inAddr()) <= 0;
	}

	#[ReturnTypeWillChange]
	public function count(): int
    {
		return (int)$this->getBlockSize();
	}
}