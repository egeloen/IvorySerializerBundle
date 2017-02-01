<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\Tests\FOS\Type;

use FOS\RestBundle\Util\ExceptionValueMap;
use Ivory\Serializer\Context\Context;
use Ivory\Serializer\Context\ContextInterface;
use Ivory\Serializer\Format;
use Ivory\Serializer\Navigator\Navigator;
use Ivory\Serializer\Registry\TypeRegistry;
use Ivory\Serializer\Serializer;
use Ivory\Serializer\Type\Type;
use Ivory\SerializerBundle\FOS\Type\ExceptionType;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class ExceptionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->serializer = new Serializer(new Navigator(TypeRegistry::create([
            Type::EXCEPTION => new ExceptionType($this->createExceptionValueMapMock()),
        ])));
    }

    /**
     * @param string                $name
     * @param mixed                 $data
     * @param string                $format
     * @param ContextInterface|null $context
     *
     * @dataProvider serializeProvider
     */
    public function testSerialize($name, $data, $format, ContextInterface $context = null)
    {
        $this->assertSame(
            $this->getDataSet($name, $format),
            $this->serializer->serialize($data, $format, $context)
        );
    }

    /**
     * @param string                $name
     * @param mixed                 $data
     * @param string                $format
     * @param ContextInterface|null $context
     *
     * @dataProvider serializeProvider
     */
    public function testSerializeDebug($name, $data, $format, ContextInterface $context = null)
    {
        $this->serializer = new Serializer(new Navigator(TypeRegistry::create([
            Type::EXCEPTION => new ExceptionType($this->createExceptionValueMapMock(), true),
        ])));

        $this->assertRegExp(
            '/^'.$this->getDataSet($name.'_debug', $format).'$/s',
            $this->serializer->serialize($data, $format, $context)
        );
    }

    /**
     * @param string $format
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Deserializing an "Exception" is not supported.
     *
     * @dataProvider formatProvider
     */
    public function testDeserialize($format)
    {
        $this->serializer->deserialize($this->getDataSet('exception_parent', $format), \Exception::class, $format);
    }

    /**
     * @return mixed[]
     */
    public function serializeProvider()
    {
        $parentException = new \Exception('Parent exception', 321);
        $childException = new \Exception('Child exception', 123, $parentException);

        return $this->expandCases([
            ['exception_parent', $parentException],
            ['exception_child', $childException],
            ['exception_status_code', $parentException, (new Context())->setOption('template_data', ['status_code' => 400])],
        ]);
    }

    /**
     * @return mixed[]
     */
    public function formatProvider()
    {
        return [
            [Format::CSV],
            [Format::JSON],
            [Format::XML],
            [Format::YAML],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExceptionValueMap
     */
    private function createExceptionValueMapMock()
    {
        return $this->createMock(ExceptionValueMap::class);
    }

    /**
     * @param mixed[] $cases
     *
     * @return mixed[]
     */
    private function expandCases(array $cases)
    {
        $providers = [];

        foreach ([Format::CSV, Format::JSON, Format::XML, Format::YAML] as $format) {
            foreach ($cases as $case) {
                if (isset($case[2])) {
                    $case[3] = $case[2];
                }

                $case[2] = $format;
                $providers[] = $case;
            }
        }

        return $providers;
    }

    /**
     * @param string $name
     * @param string $format
     *
     * @return string
     */
    private function getDataSet($name, $format)
    {
        $extension = $format;

        if ($extension === Format::YAML) {
            $extension = 'yml';
        }

        return file_get_contents(__DIR__.'/../../Fixtures/Data/'.strtolower($format).'/'.$name.'.'.strtolower($extension));
    }
}
