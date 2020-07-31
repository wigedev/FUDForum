<?php

namespace ModelTests\Utility\Configuration;

use Model\Exceptions\ConfigurationException;
use Model\Utility\Configuration\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    protected static $FUD_OPT_1 = 1741616191;
    protected static $FUD_OPT_2 = 1777733759;
	protected static $FUD_OPT_3 = 41943104;
    protected static $FUD_OPT_4 = 41943104;

    public function setUp(): void
    {
        parent::setUp();
        $this->sut = new Options(static::$FUD_OPT_1, static::$FUD_OPT_2, static::$FUD_OPT_3, static::$FUD_OPT_4);
    }

    public function testExistingSingleOptionReturnsValue()
    {
        $this->assertTrue($this->sut->ALLOW_SIGS);
        $this->assertFalse($this->sut->ALLOW_SIGS);
    }

    public function testExistingMultipleOptionReturnsValue()
    {
        $this->assertEquals(2, $this->sut->PRIVATE_TAGS);
    }

    public function testNonExistantOptionThrowsException()
    {
        $this->expectException(ConfigurationException::class);
        $this->sut->I_DONT_EXIST;
    }
}
