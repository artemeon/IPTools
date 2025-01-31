<?php

declare(strict_types=1);

namespace IPTools\Tests;

use IPTools\Exception\IpException;
use IPTools\Exception\NetworkException;
use IPTools\Exception\RangeException;
use IPTools\IP;
use IPTools\Range;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    /**
     * @throws NetworkException
     * @throws RangeException
     * @throws IpException
     */
    #[DataProvider('getTestParseData')]
    public function testParse($data, $expected): void
    {
        $range = Range::parse($data);

        $this->assertEquals($expected[0], $range->firstIP);
        $this->assertEquals($expected[1], $range->lastIP);
    }

    /**
     * @throws RangeException
     * @throws NetworkException
     * @throws IpException
     */
    #[DataProvider('getTestNetworksData')]
    public function testGetNetworks($data, $expected): void
    {
        $result = [];

        foreach (Range::parse($data)->getNetworks() as $network) {
            $result[] = (string)$network;
        }

        $this->assertEquals($expected, $result);        
    }

    /**
     * @throws RangeException
     * @throws NetworkException
     * @throws IpException
     */
    #[DataProvider('getTestContainsData')]
    public function testContains($data, $find, $expected): void
    {
        $this->assertEquals($expected, Range::parse($data)->contains(new IP($find)));
    }

    /**
     * @throws NetworkException
     * @throws IpException
     * @throws RangeException
     */
    #[DataProvider('getTestIterationData')]
    public function testRangeIteration($data, $expected): void
    {
        $result = [];
        foreach (Range::parse($data) as $range) {
           $result[] = (string)$range;
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @throws NetworkException
     * @throws RangeException
     * @throws IpException
     */
    #[DataProvider('getTestCountData')]
    public function testCount($data, $expected): void
    {
        $this->assertCount($expected, Range::parse($data));
    }

    public static function getTestParseData(): array
    {
        return [
            ['127.0.0.1-127.255.255.255', ['127.0.0.1', '127.255.255.255']],
            ['127.0.0.1/24', ['127.0.0.0', '127.0.0.255']],
            ['127.*.0.0', ['127.0.0.0', '127.255.0.0']],
            ['127.255.255.0', ['127.255.255.0', '127.255.255.0']],
        ];
    }

    public static function getTestNetworksData(): array
    {
        return [
            ['192.168.1.*', ['192.168.1.0/24']],
            ['192.168.1.208-192.168.1.255', [
                '192.168.1.208/28',
                '192.168.1.224/27' 
            ]],
            ['192.168.1.0-192.168.1.191', [
                '192.168.1.0/25',
                '192.168.1.128/26' 
            ]],
            ['192.168.1.125-192.168.1.126', [
                '192.168.1.125/32',
                '192.168.1.126/32',
            ]],
        ];
    }

    public static function getTestContainsData(): array
    {
        return [
            ['192.168.*.*', '192.168.245.15', true],
            ['192.168.*.*', '192.169.255.255', false],

            /**
             * 10.10.45.48 --> 00001010 00001010 00101101 00110000 
             * the last 0000 leads error
             */
            ['10.10.45.48/28', '10.10.45.58', true],

            ['2001:db8::/64', '2001:db8::ffff', true],
            ['2001:db8::/64', '2001:db8:ffff::', false],
        ];
    }

    public static function getTestIterationData(): array
    {
        return [
            ['192.168.2.0-192.168.2.7', 
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

    public static function getTestCountData(): array
    {
        return [
            ['127.0.0.0/31', 2],
            ['2001:db8::/120', 256],
        ];
    }
}