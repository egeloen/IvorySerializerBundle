<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\Tests\FOS;

use FOS\RestBundle\Context\Context as FOSContext;
use Ivory\Serializer\Context\Context;
use Ivory\Serializer\Exclusion\ChainExclusionStrategy;
use Ivory\Serializer\Exclusion\ExclusionStrategyInterface;
use Ivory\Serializer\Exclusion\GroupsExclusionStrategy;
use Ivory\Serializer\Exclusion\MaxDepthExclusionStrategy;
use Ivory\Serializer\Exclusion\VersionExclusionStrategy;
use Ivory\Serializer\Format;
use Ivory\Serializer\SerializerInterface;
use Ivory\SerializerBundle\FOS\Serializer;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface
     */
    private $innerSerializer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->innerSerializer = $this->createSerializerMock();
        $this->serializer = new Serializer($this->innerSerializer);
    }

    public function testIgnoreNull()
    {
        $context = new FOSContext();
        $context->setSerializeNull(false);

        $callback = function (Context $context) {
            return $context->isNullIgnored();
        };

        $this->assertContext($context, $callback);
    }

    public function testGroups()
    {
        $context = new FOSContext();
        $context->setGroups(['foo', 'bar']);

        $callback = function (Context $context) {
            return $context->getExclusionStrategy() instanceof GroupsExclusionStrategy;
        };

        $this->assertContext($context, $callback);
    }

    public function testVersion()
    {
        $context = new FOSContext();
        $context->setVersion('1.0.0');

        $callback = function (Context $context) {
            return $context->getExclusionStrategy() instanceof VersionExclusionStrategy;
        };

        $this->assertContext($context, $callback);
    }

    public function testMaxDepth()
    {
        $context = new FOSContext();

        if (method_exists($context, 'enableMaxDepth')) {
            $context->enableMaxDepth();
        } else {
            $context->setMaxDepth(1);
        }

        $callback = function (Context $context) {
            return $context->getExclusionStrategy() instanceof MaxDepthExclusionStrategy;
        };

        $this->assertContext($context, $callback);
    }

    public function testCustomExclusionStrategy()
    {
        $context = new FOSContext();
        $context->setAttribute(
            'ivory_exclusion_strategies',
            [$exclusionStrategy = $this->createExclusionStrategyMock()]
        );

        $callback = function (Context $context) use ($exclusionStrategy) {
            return $context->getExclusionStrategy() === $exclusionStrategy;
        };

        $this->assertContext($context, $callback);
    }

    public function testCustomExclusionStrategies()
    {
        $context = new FOSContext();
        $context->setAttribute(
            'ivory_exclusion_strategies',
            [$this->createExclusionStrategyMock(), $this->createExclusionStrategyMock()]
        );

        $callback = function (Context $context) {
            return $context->getExclusionStrategy() instanceof ChainExclusionStrategy;
        };

        $this->assertContext($context, $callback);
    }

    public function testOptions()
    {
        $context = new FOSContext();
        $context->setAttribute('foo', 'bar');

        $callback = function (Context $context) {
            return $context->getOptions() === ['foo' => 'bar'];
        };

        $this->assertContext($context, $callback);
    }

    public function testInvalidExclusionStrategies()
    {
        $context = new FOSContext();
        $context->setAttribute('ivory_exclusion_strategies', 'invalid');

        $this->assertInvalidContext(
            $context,
            'The "ivory_exclusion_strategies" context attribute must be an array or implement "Traversable".'
        );
    }

    public function testInvalidExclusionStrategy()
    {
        $context = new FOSContext();
        $context->setAttribute('ivory_exclusion_strategies', ['invalid']);

        $this->assertInvalidContext(
            $context,
            'The "ivory_exclusion_strategies" context attribute must be an array of '.
            '"Ivory\Serializer\Exclusion\ExclusionStrategyInterface", got "string".'
        );
    }

    /**
     * @param FOSContext $context
     * @param callable   $callback
     */
    private function assertContext(FOSContext $context, callable $callback)
    {
        $this->innerSerializer
            ->expects($this->once())
            ->method('serialize')
            ->with(
                $this->identicalTo($data = 'data'),
                $this->identicalTo($format = Format::JSON),
                $this->callback($callback)
            )
            ->will($this->returnValue($serializeResult = 'serialize'));

        $this->innerSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $this->identicalTo($data),
                $this->identicalTo($type = 'type'),
                $this->identicalTo($format),
                $this->callback($callback)
            )
            ->will($this->returnValue($deserializeResult = 'deserialize'));

        $this->assertSame($serializeResult, $this->serializer->serialize($data, $format, $context));
        $this->assertSame($deserializeResult, $this->serializer->deserialize($data, $type, $format, $context));
    }

    /**
     * @param FOSContext $context
     * @param string     $message
     */
    private function assertInvalidContext(FOSContext $context, $message)
    {
        $data = 'data';
        $type = 'type';
        $format = Format::JSON;

        try {
            $this->serializer->serialize($data, $format, $context);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
            $this->assertSame($message, $e->getMessage());
        }

        try {
            $this->serializer->deserialize($data, $type, $format, $context);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);
            $this->assertSame($message, $e->getMessage());
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SerializerInterface
     */
    private function createSerializerMock()
    {
        return $this->createMock(SerializerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExclusionStrategyInterface
     */
    private function createExclusionStrategyMock()
    {
        return $this->createMock(ExclusionStrategyInterface::class);
    }
}
