<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Emitter;

use Mezzio\Emitter\EmitterStack;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class EmitterStackTest extends TestCase
{
    public function setUp()
    {
        $this->emitter = new EmitterStack();
    }

    public function testIsAnSplStack()
    {
        $this->assertInstanceOf('SplStack', $this->emitter);
    }

    public function testIsAnEmitterImplementation()
    {
        $this->assertInstanceOf('Laminas\Diactoros\Response\EmitterInterface', $this->emitter);
    }

    public function nonEmitterValues()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['emitter'],
            'array'      => [[$this->prophesize('Laminas\Diactoros\Response\EmitterInterface')->reveal()]],
            'object'     => [(object)[]],
        ];
    }

    /**
     * @dataProvider nonEmitterValues
     */
    public function testCannotPushNonEmitterToStack($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->emitter->push($value);
    }

    /**
     * @dataProvider nonEmitterValues
     */
    public function testCannotUnshiftNonEmitterToStack($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->emitter->unshift($value);
    }

    /**
     * @dataProvider nonEmitterValues
     */
    public function testCannotSetNonEmitterToSpecificIndex($value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->emitter->offsetSet(0, $value);
    }

    public function testEmitLoopsThroughEmittersUntilOneReturnsNonFalseValue()
    {
        $first = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $first->emit()->shouldNotBeCalled();

        $second = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $second->emit(Argument::type('Psr\Http\Message\ResponseInterface'))
            ->willReturn(null);

        $third = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $third->emit(Argument::type('Psr\Http\Message\ResponseInterface'))
            ->willReturn(false);

        $this->emitter->push($first->reveal());
        $this->emitter->push($second->reveal());
        $this->emitter->push($third->reveal());

        $response = $this->prophesize('Psr\Http\Message\ResponseInterface');

        $this->assertNull($this->emitter->emit($response->reveal()));
    }
}
