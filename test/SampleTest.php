<?php
 
use PHPUnit\Framework\TestCase;
use Gomicale\Sample;
 
class SampleTest extends TestCase
{
    public function testHello()
    {
        $sample = new Sample();
        $result = $sample->hello();
        $this->assertEquals("Hello PHPUnit!", $result);
    }

    public function testExampleHttpParser()
    {
        $sample = new Sample();
        $result = $sample->exampleHttpParser();
        $this->assertEquals("Example Domain", $result);
    }
}