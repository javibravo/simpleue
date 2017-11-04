<?php

namespace Simpleue\Unitary\Locker;

use Simpleue\Locker\RedisLocker;

class RedisLockerTest extends \PHPUnit_Framework_TestCase
{
    private $redisClientMock;
    /**
     * @var RedisLocker
     */
    private $redisLocker;

    protected function setUp()
    {
        $this->redisClientMock = $this->getMock(
            '\Redis',
            array('isConnected', 'getHost', 'getPort', 'getDbNum', 'set', 'get', 'getSet')
        );

        $this->redisLocker = new RedisLocker($this->redisClientMock);
    }

    public function testIsConnected()
    {
        $this->redisClientMock->expects($this->at(0))->method('isConnected')->willReturn(false);
        $this->setExpectedException('RuntimeException', 'Redis Client not connected!');
        new RedisLocker($this->redisClientMock);
    }

    public function testGetJobUniqId()
    {
        $job = '{"string": "example", "uniqid":"123"}';
        $this->assertEquals(
            md5(strtolower($job)),
            $this->redisLocker->getJobUniqId($job)
        );
        $this->redisLocker->setJobUniqIdFunction(function ($job) {
            return json_decode($job, true)['uniqid'];
        });
        $this->assertEquals(
            '123',
            $this->redisLocker->getJobUniqId($job)
        );
    }

    public function testGetLockerInfo()
    {
        $this->redisClientMock->expects($this->at(0))->method('getHost')->willReturn('localhost');
        $this->redisClientMock->expects($this->at(1))->method('getPort')->willReturn('6379');
        $this->redisClientMock->expects($this->at(2))->method('getDbNum')->willReturn('0');
        $this->assertEquals(
            'Redis ( localhost:6379 -> 0 )',
            $this->redisLocker->getLockerInfo()
        );
    }

    public function testLock()
    {
        $job = '{"string": "example", "uniqid":"123"}';
        $this->redisClientMock->expects($this->at(0))->method('set')->willReturn(true);
        $this->assertTrue($this->redisLocker->lock($job, 60));

        $this->redisClientMock->expects($this->at(0))->method('set')->willReturn(false);
        $this->redisClientMock->expects($this->at(1))->method('get')->willReturn(time()+20);
        $this->assertFalse($this->redisLocker->lock($job, 60));

        $this->redisClientMock->expects($this->at(0))->method('set')->willReturn(false);
        $this->redisClientMock->expects($this->at(1))->method('get')->willReturn(time()-20);
        $this->redisClientMock->expects($this->at(2))->method('getSet')->willReturn(time()+20);
        $this->assertFalse($this->redisLocker->lock($job, 60));

        $this->redisClientMock->expects($this->at(0))->method('set')->willReturn(false);
        $this->redisClientMock->expects($this->at(1))->method('get')->willReturn(time()-20);
        $this->redisClientMock->expects($this->at(2))->method('getSet')->willReturn(time()-20);
        $this->assertTrue($this->redisLocker->lock($job, 60));

        $this->setExpectedException('RuntimeException', 'Job for lock is invalid!');
        $this->redisLocker->lock(false, 60);
    }

}
