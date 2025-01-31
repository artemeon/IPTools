<?php

declare(strict_types=1);

namespace IPTools\Tests;

use IPTools\Exception\IpException;
use IPTools\Exception\NetworkException;
use IPTools\IP;
use IPTools\Network;
use PHPUnit\Framework\TestCase;

final class NetworkTest extends TestCase
{
    public function testConstructor(): void
    {
        $ipv4 = new IP('127.0.0.1');
        $ipv4Netmask = new IP('255.255.255.0');

        $ipv6 = new IP('2001::');
        $ipv6Netmask = new IP('ffff:ffff:ffff:ffff:ffff:ffff:ffff::');

        $ipv4Network = new Network($ipv4, $ipv4Netmask);
        $ipv6Network = new Network($ipv6, $ipv6Netmask);

        $this->assertEquals('127.0.0.0/24', (string)$ipv4Network);
        $this->assertEquals('2001::/112', (string)$ipv6Network);
    }

    public function testProperties(): void
    {
        $network = Network::parse('127.0.0.1/24');

        $network->ip = new IP('192.0.0.2');

        $this->assertEquals('192.0.0.2', $network->ip);
        $this->assertEquals('192.0.0.0/24', (string)$network);
        $this->assertEquals('0.0.0.255', (string)$network->wildcard);
        $this->assertEquals('192.0.0.0', (string)$network->firstIP);
        $this->assertEquals('192.0.0.255', (string)$network->lastIP);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getTestParseData')]
    public function testParse($data, $expected): void
    {
        $this->assertEquals($expected, (string)Network::parse($data));
    }

    public function testParseWrongNetwork(): void
    {
        $this->expectException(IpException::class);

        Network::parse('10.0.0.0/24 abc');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getPrefixData')]
    public function testPrefix2Mask($prefix, $version, $mask): void
    {
        $this->assertEquals($mask, Network::prefix2netmask($prefix, $version));
    }

    public function testPrefix2MaskWrongIPVersion(): void
    {
        $this->expectException(NetworkException::class);

        Network::prefix2netmask('128', 'ip_version');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getInvalidPrefixData')]
    public function testPrefix2MaskInvalidPrefix($prefix, $version): void
    {
        $this->expectException(NetworkException::class);

        Network::prefix2netmask($prefix, $version);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getHostsData')]
    public function testHosts($data, $expected): void
    {
        foreach(Network::parse($data)->getHosts as $ip) {
            $result[] = (string)$ip;
        }

        $this->assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getExcludeData')]
    public function testExclude($data, $exclude, $expected): void
    {
        $result = [];

        foreach(Network::parse($data)->exclude($exclude) as $network) {
            $result[] = (string)$network;
        }

        $this->assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getExcludeExceptionData')]
    public function testExcludeException($data, $exclude): void
    {
        $this->expectException(NetworkException::class);

        Network::parse($data)->exclude($exclude);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMoveToData')]
    public function testMoveTo($network, $prefixLength, $expected): void
    {
        $result = [];

        foreach (Network::parse($network)->moveTo($prefixLength) as $network) {
            $result[] = (string)$network;
        }

        $this->assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getMoveToExceptionData')]
    public function testMoveToException($network, $prefixLength): void
    {
        $this->expectException(NetworkException::class);

        Network::parse($network)->moveTo($prefixLength);
    }

     #[\PHPUnit\Framework\Attributes\DataProvider('getTestIterationData')]
    public function testNetworkIteration($data, $expected): void
    {
        foreach (Network::parse($data) as $network) {
           $result[] = (string)$network;
        }

        $this->assertEquals($expected, $result);
    }

     #[\PHPUnit\Framework\Attributes\DataProvider('getTestCountData')]
    public function testCount($data, $expected): void
    {
        $this->assertEquals($expected, count(Network::parse($data)));
    }

    public function getTestParseData()
    {
        return [
            ['192.168.0.54/24', '192.168.0.0/24'],
            ['2001::2001:2001/32', '2001::/32'],
            ['127.168.0.1 255.255.255.255', '127.168.0.1/32'],
            ['1234::1234', '1234::1234/128'],
        ];
    }

    public function getPrefixData()
    {
        return [
            ['24', IP::IP_V4, IP::parse('255.255.255.0')],
            ['32', IP::IP_V4, IP::parse('255.255.255.255')],
            ['64', IP::IP_V6, IP::parse('ffff:ffff:ffff:ffff::')],
            ['128', IP::IP_V6, IP::parse('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff')]
        ];
    }

    public function getInvalidPrefixData()
    {
        return [
            ['-1', IP::IP_V4],
            ['33', IP::IP_V4],
            ['prefix', IP::IP_V4],
            ['-1', IP::IP_V6],
            ['129', IP::IP_V6],
        ];
    }

    public function getHostsData()
    {
        return [
            ['192.0.2.0/29',
                [
                    '192.0.2.1',
                    '192.0.2.2',
                    '192.0.2.3',
                    '192.0.2.4',
                    '192.0.2.5',
                    '192.0.2.6',
                ]
            ],
        ];
    }

    public function getExcludeData()
    {
        return [
            ['192.0.2.0/28', '192.0.2.1/32',
                [
                    '192.0.2.0/32',
                    '192.0.2.2/31',
                    '192.0.2.4/30',
                    '192.0.2.8/29',
                ]
            ],
            ['192.0.2.2/32', '192.0.2.2/32', []],
        ];
    }

    public function getExcludeExceptionData()
    {
        return [
            ['192.0.2.0/28', '192.0.3.0/24'],
            ['192.0.2.2/32', '192.0.2.3/32'],
        ];
    }

    public function getMoveToData()
    {
        return [
            ['192.168.0.0/22', '24',
                [
                    '192.168.0.0/24',
                    '192.168.1.0/24',
                    '192.168.2.0/24',
                    '192.168.3.0/24'
                ]
            ],
            ['192.168.2.0/24', '25',
                [
                    '192.168.2.0/25',
                    '192.168.2.128/25'
                ]
            ],
            ['192.168.2.0/30', '32',
                [
                    '192.168.2.0/32',
                    '192.168.2.1/32',
                    '192.168.2.2/32',
                    '192.168.2.3/32'
                ]
            ],
        ];
    }

    public function getMoveToExceptionData()
    {
        return [
            ['192.168.0.0/22', '22'],
            ['192.168.0.0/22', '21'],
            ['192.168.0.0/22', '33'],
            ['192.168.0.0/22', 'prefixLength']
        ];
    }

    public function getTestIterationData()
    {
        return [
            ['192.168.2.0/29',
                [
                    '192.168.2.0',
                    '192.168.2.1',
                    '192.168.2.2',
                    '192.168.2.3',
                    '192.168.2.4',
                    '192.168.2.5',
                    '192.168.2.6',
                    '192.168.2.7',
                ]
            ],
            ['2001:db8::/125',
                [
                    '2001:db8::',
                    '2001:db8::1',
                    '2001:db8::2',
                    '2001:db8::3',
                    '2001:db8::4',
                    '2001:db8::5',
                    '2001:db8::6',
                    '2001:db8::7',
                ]
            ],
        ];
    }

    public function getTestCountData()
    {
        return [
            ['127.0.0.0/31', 2],
            ['2001:db8::/120', 256],
        ];
    }
}
