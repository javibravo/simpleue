<?php

namespace Simpleue\Unitary\Locker;

use Simpleue\Locker\MemcachedLocker;

class MemcachedLockerTest extends \PHPUnit_Framework_TestCase
{
    private $memcachedClientMock;
    /**
     * @var MemcachedLocker
     */
    private $memcachedLocker;

    protected function setUp()
    {
        $this->memcachedClientMock = $this->getMock(
            '\Memcached',
            array('addServer', 'setOptions', 'getServerList', 'add', 'quit')
        );
        $this->memcachedClientMock->expects($this->any())->method('getServerList')->willReturn(
            [['host' => 'localhost', 'port' => 11211, 'weight'=>10]]
        );

        $this->memcachedLocker =  new MemcachedLocker($this->memcachedClientMock);
    }

    public function testGetJobUniqId()
    {
        $job = '{"string": "example", "uniqid":"123"}';
        $this->assertEquals(
            md5(strtolower($job)),
            $this->memcachedLocker->getJobUniqId($job)
        );
        $this->memcachedLocker->setJobUniqIdFunction(function ($job) {
            return json_decode($job, true)['uniqid'];
        });
        $this->assertEquals(
            '123',
            $this->memcachedLocker->getJobUniqId($job)
        );
    }

    public function testGetLockerInfo()
    {
        $this->assertEquals(
            'Memcached ( ' . json_encode([['host' => 'localhost', 'port' => 11211, 'weight'=>10]]) . ' )',
            $this->memcachedLocker->getLockerInfo()
        );
    }

    public function testLock()
    {
        $job = '{"string": "example", "uniqid":"123"}';
        $this->memcachedClientMock->expects($this->at(0))->method('add')->willReturn(true);
        $this->memcachedClientMock->expects($this->at(1))->method('add')->willReturn(false);
        $this->assertTrue($this->memcachedLocker->lock($job, 60));
        $this->assertFalse($this->memcachedLocker->lock($job, 60));

        $this->setExpectedException('RuntimeException', 'Job for lock is invalid!');
        $this->memcachedLocker->lock(false, 60);
    }
}
