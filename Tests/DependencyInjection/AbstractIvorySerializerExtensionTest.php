<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\Tests\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use FOS\RestBundle\Serializer\Serializer as FOSSerializer;
use FOS\RestBundle\Util\ExceptionValueMap;
use Ivory\Serializer\Mapping\ClassMetadataInterface;
use Ivory\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Ivory\Serializer\Mapping\Loader\ChainClassMetadataLoader;
use Ivory\Serializer\Mapping\PropertyMetadataInterface;
use Ivory\Serializer\Serializer;
use Ivory\Serializer\Type\DateTimeType;
use Ivory\Serializer\Type\ExceptionType;
use Ivory\Serializer\Visitor\Csv\CsvDeserializationVisitor;
use Ivory\Serializer\Visitor\Csv\CsvSerializationVisitor;
use Ivory\Serializer\Visitor\Json\JsonDeserializationVisitor;
use Ivory\Serializer\Visitor\Json\JsonSerializationVisitor;
use Ivory\Serializer\Visitor\Xml\XmlDeserializationVisitor;
use Ivory\Serializer\Visitor\Xml\XmlSerializationVisitor;
use Ivory\Serializer\Visitor\Yaml\YamlDeserializationVisitor;
use Ivory\Serializer\Visitor\Yaml\YamlSerializationVisitor;
use Ivory\SerializerBundle\CacheWarmer\SerializerCacheWarmer;
use Ivory\SerializerBundle\DependencyInjection\IvorySerializerExtension;
use Ivory\SerializerBundle\FOS\Type\ExceptionType as FOSExceptionType;
use Ivory\SerializerBundle\IvorySerializerBundle;
use Ivory\SerializerBundle\Tests\Fixtures\Bundle\AcmeFixtureBundle;
use Ivory\SerializerBundle\Tests\Fixtures\Bundle\Model\Model;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
abstract class AbstractIvorySerializerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.root_dir', __DIR__.'/../Fixtures');
        $this->container->set('annotation_reader', new AnnotationReader());
        $this->container->set('cache.system', $this->createCacheItemPoolMock());
        $this->container->registerExtension($extension = new IvorySerializerExtension());
        $this->container->loadFromExtension($extension->getAlias());
        (new IvorySerializerBundle())->build($this->container);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $configuration
     */
    abstract protected function loadConfiguration(ContainerBuilder $container, $configuration);

    public function testSerializer()
    {
        $this->container->compile();

        $this->assertInstanceOf(Serializer::class, $this->container->get('ivory.serializer'));

        $this->assertInstanceOf(
            CacheClassMetadataFactory::class,
            $this->container->get('ivory.serializer.mapping.factory')
        );

        $this->assertInstanceOf(
            ChainClassMetadataLoader::class,
            $this->container->get('ivory.serializer.mapping.loader')
        );
    }

    public function testMappingAnnotationEnabled()
    {
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), [
            'foo' => ['alias' => 'bar'],
        ]);
    }

    public function testMappingAnnotationDisabled()
    {
        $this->loadConfiguration($this->container, 'mapping_annotation_disabled');
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), ['foo' => []]);
    }

    public function testMappingAutoEnabled()
    {
        $this->container->setParameter('kernel.bundles', ['AcmeFixtureBundle' => AcmeFixtureBundle::class]);
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), [
            'foo' => [
                'alias'    => 'bar',
                'readable' => false,
                'writable' => false,
                'since'    => '1.0.0',
                'until'    => '2.0.0',
                'groups'   => ['bar'],
                'type'     => 'int',
            ],
        ]);
    }

    public function testMappingAutoDisabled()
    {
        $this->loadConfiguration($this->container, 'mapping_auto_disabled');
        $this->container->setParameter('kernel.bundles', ['AcmeFixtureBundle' => AcmeFixtureBundle::class]);
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), [
            'foo' => ['alias' => 'bar'],
        ]);
    }

    public function testMappingAutoPaths()
    {
        $this->loadConfiguration($this->container, 'mapping_auto_paths');
        $this->container->setParameter('kernel.bundles', ['AcmeFixtureBundle' => AcmeFixtureBundle::class]);
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), [
            'foo' => [
                'alias'         => 'bar',
                'since'         => '1.1.0',
                'until'         => '2.1.0',
                'type'          => 'bool',
                'xml_attribute' => true,
                'xml_value'     => true,
            ],
        ], ['xml_root' => 'model']);
    }

    public function testMappingPaths()
    {
        $this->loadConfiguration($this->container, 'mapping_paths');
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), [
            'foo' => [
                'alias'         => 'bar',
                'xml_attribute' => true,
                'xml_value'     => true,
            ],
        ], ['xml_root' => 'model']);
    }

    public function testClassMetadataLoader()
    {
        $this->loadService('class_metadata_loader');
        $this->container->compile();

        $classMetadataFactory = $this->container->get('ivory.serializer.mapping.factory');

        $this->assertClassMetadata($classMetadataFactory->getClassMetadata(Model::class), [
            'foo' => [
                'alias'         => 'bar',
                'xml_attribute' => true,
            ],
        ]);
    }

    public function testMappingCache()
    {
        $this->container->compile();

        $classMetadataFactoryService = 'ivory.serializer.mapping.factory';
        $classMetadataFactoryDefinition = $this->container->getDefinition($classMetadataFactoryService);

        $this->assertSame(
            'ivory.serializer.mapping.factory.event',
            (string) $classMetadataFactoryDefinition->getArgument(0)
        );

        $this->assertSame('cache.system', (string) $classMetadataFactoryDefinition->getArgument(1));
        $this->assertSame('ivory_serializer', $classMetadataFactoryDefinition->getArgument(2));

        $this->assertInstanceOf(
            CacheClassMetadataFactory::class,
            $this->container->get($classMetadataFactoryService)
        );
    }

    public function testCustomMappingCache()
    {
        $this->container->set('cache.custom', $this->createCacheItemPoolMock());
        $this->loadConfiguration($this->container, 'mapping_cache');
        $this->container->compile();

        $classMetadataFactoryService = 'ivory.serializer.mapping.factory';
        $classMetadataFactoryDefinition = $this->container->getDefinition($classMetadataFactoryService);

        $this->assertSame(
            'ivory.serializer.mapping.factory.event',
            (string) $classMetadataFactoryDefinition->getArgument(0)
        );

        $this->assertSame('cache.custom', (string) $classMetadataFactoryDefinition->getArgument(1));
        $this->assertSame('acme', $classMetadataFactoryDefinition->getArgument(2));

        $this->assertInstanceOf(
            CacheClassMetadataFactory::class,
            $this->container->get($classMetadataFactoryService)
        );
    }

    public function testCacheWarmer()
    {
        $this->container->compile();

        $cacheWarmerService = 'ivory.serializer.cache_warmer';

        $this->assertSame(
            ['kernel.cache_warmer' => [[]]],
            $this->container->getDefinition($cacheWarmerService)->getTags()
        );

        $this->assertInstanceOf(
            SerializerCacheWarmer::class,
            $this->container->get($cacheWarmerService)
        );
    }

    public function testEventEnabled()
    {
        $this->container->compile();

        $this->assertTrue($this->container->has('ivory.serializer.event.dispatcher'));
        $this->assertTrue($this->container->has('ivory.serializer.mapping.factory.event'));
        $this->assertTrue($this->container->has('ivory.serializer.navigator.event'));
    }

    public function testEventDisabled()
    {
        $this->loadConfiguration($this->container, 'event_disabled');
        $this->container->compile();

        $this->assertFalse($this->container->has('ivory.serializer.event.dispatcher'));
        $this->assertFalse($this->container->has('ivory.serializer.mapping.factory.event'));
        $this->assertFalse($this->container->has('ivory.serializer.navigator.event'));
    }

    public function testDateTimeType()
    {
        $this->loadConfiguration($this->container, 'type_date_time');
        $this->container->compile();

        $dateTimeService = 'ivory.serializer.type.date_time';
        $dateTimeDefinition = $this->container->getDefinition($dateTimeService);

        $this->assertSame(\DateTime::ATOM, $dateTimeDefinition->getArgument(0));
        $this->assertSame('UTC', $dateTimeDefinition->getArgument(1));

        $this->assertInstanceOf(DateTimeType::class, $this->container->get($dateTimeService));
    }

    public function testCsvVisitor()
    {
        $this->loadConfiguration($this->container, 'visitor_csv');
        $this->container->compile();

        $csvSerializationVisitorService = 'ivory.serializer.visitor.csv.serialization';
        $csvDeserializationVisitorService = 'ivory.serializer.visitor.csv.deserialization';

        $csvSerializationVisitorDefinition = $this->container->getDefinition($csvSerializationVisitorService);
        $csvDeserializationVisitorDefinition = $this->container->getDefinition($csvDeserializationVisitorService);

        $this->assertSame('ivory.serializer.accessor', (string) $csvSerializationVisitorDefinition->getArgument(0));
        $this->assertSame($delimiter = ',', $csvSerializationVisitorDefinition->getArgument(1));
        $this->assertSame($enclosure = '"', $csvSerializationVisitorDefinition->getArgument(2));
        $this->assertSame($escapeChar = '\\', $csvSerializationVisitorDefinition->getArgument(3));
        $this->assertSame($keySeparator = '.', $csvSerializationVisitorDefinition->getArgument(4));

        $this->assertSame('ivory.serializer.instantiator', (string) $csvDeserializationVisitorDefinition->getArgument(0));
        $this->assertSame('ivory.serializer.mutator', (string) $csvDeserializationVisitorDefinition->getArgument(1));
        $this->assertSame($delimiter, $csvDeserializationVisitorDefinition->getArgument(2));
        $this->assertSame($enclosure, $csvDeserializationVisitorDefinition->getArgument(3));
        $this->assertSame($escapeChar, $csvDeserializationVisitorDefinition->getArgument(4));
        $this->assertSame($keySeparator, $csvDeserializationVisitorDefinition->getArgument(5));

        $this->assertInstanceOf(
            CsvSerializationVisitor::class,
            $this->container->get($csvSerializationVisitorService)
        );

        $this->assertInstanceOf(
            CsvDeserializationVisitor::class,
            $this->container->get($csvDeserializationVisitorService)
        );
    }

    public function testJsonVisitor()
    {
        $this->loadConfiguration($this->container, 'visitor_json');
        $this->container->compile();

        $jsonSerializationVisitorService = 'ivory.serializer.visitor.json.serialization';
        $jsonDeserializationVisitorService = 'ivory.serializer.visitor.json.deserialization';

        $jsonSerializationVisitorDefinition = $this->container->getDefinition($jsonSerializationVisitorService);
        $jsonDeserializationVisitorDefinition = $this->container->getDefinition($jsonDeserializationVisitorService);

        $this->assertSame('ivory.serializer.accessor', (string) $jsonSerializationVisitorDefinition->getArgument(0));
        $this->assertSame(0, $jsonSerializationVisitorDefinition->getArgument(1));

        $this->assertSame(
            'ivory.serializer.instantiator',
            (string) $jsonDeserializationVisitorDefinition->getArgument(0)
        );

        $this->assertSame('ivory.serializer.mutator', (string) $jsonDeserializationVisitorDefinition->getArgument(1));
        $this->assertSame(512, $jsonDeserializationVisitorDefinition->getArgument(2));
        $this->assertSame(0, $jsonDeserializationVisitorDefinition->getArgument(3));

        $this->assertInstanceOf(
            JsonSerializationVisitor::class,
            $this->container->get($jsonSerializationVisitorService)
        );

        $this->assertInstanceOf(
            JsonDeserializationVisitor::class,
            $this->container->get($jsonDeserializationVisitorService)
        );
    }

    public function testXmlVisitor()
    {
        $this->loadConfiguration($this->container, 'visitor_xml');
        $this->container->compile();

        $xmlSerializationVisitorService = 'ivory.serializer.visitor.xml.serialization';
        $xmlDeserializationVisitorService = 'ivory.serializer.visitor.xml.deserialization';

        $xmlSerializationVisitorDefinition = $this->container->getDefinition($xmlSerializationVisitorService);
        $xmlDeserializationVisitorDefinition = $this->container->getDefinition($xmlDeserializationVisitorService);

        $this->assertSame('ivory.serializer.accessor', (string) $xmlSerializationVisitorDefinition->getArgument(0));
        $this->assertSame('1.0', $xmlSerializationVisitorDefinition->getArgument(1));
        $this->assertSame('UTF-8', $xmlSerializationVisitorDefinition->getArgument(2));
        $this->assertTrue($xmlSerializationVisitorDefinition->getArgument(3));
        $this->assertSame('result', $xmlSerializationVisitorDefinition->getArgument(4));
        $this->assertSame($entry = 'entry', $xmlSerializationVisitorDefinition->getArgument(5));
        $this->assertSame($entryAttribute = 'key', $xmlSerializationVisitorDefinition->getArgument(6));

        $this->assertSame(
            'ivory.serializer.instantiator',
            (string) $xmlDeserializationVisitorDefinition->getArgument(0)
        );

        $this->assertSame('ivory.serializer.mutator', (string) $xmlDeserializationVisitorDefinition->getArgument(1));
        $this->assertSame($entry, $xmlDeserializationVisitorDefinition->getArgument(2));
        $this->assertSame($entryAttribute, $xmlDeserializationVisitorDefinition->getArgument(3));

        $this->assertInstanceOf(
            XmlSerializationVisitor::class,
            $this->container->get($xmlSerializationVisitorService)
        );

        $this->assertInstanceOf(
            XmlDeserializationVisitor::class,
            $this->container->get($xmlDeserializationVisitorService)
        );
    }

    public function testYamlVisitor()
    {
        $this->loadConfiguration($this->container, 'visitor_yaml');
        $this->container->compile();

        $yamlSerializationVisitorService = 'ivory.serializer.visitor.yaml.serialization';
        $yamlDeserializationVisitorService = 'ivory.serializer.visitor.yaml.deserialization';

        $yamlSerializationVisitorDefinition = $this->container->getDefinition($yamlSerializationVisitorService);
        $yamlDeserializationVisitorDefinition = $this->container->getDefinition($yamlDeserializationVisitorService);

        $this->assertSame('ivory.serializer.accessor', (string) $yamlSerializationVisitorDefinition->getArgument(0));
        $this->assertSame(2, $yamlSerializationVisitorDefinition->getArgument(1));
        $this->assertSame(4, $yamlSerializationVisitorDefinition->getArgument(2));
        $this->assertSame(0, $yamlSerializationVisitorDefinition->getArgument(3));

        $this->assertSame(
            'ivory.serializer.instantiator',
            (string) $yamlDeserializationVisitorDefinition->getArgument(0)
        );

        $this->assertSame('ivory.serializer.mutator', (string) $yamlDeserializationVisitorDefinition->getArgument(1));
        $this->assertSame(0, $yamlDeserializationVisitorDefinition->getArgument(2));

        $this->assertInstanceOf(
            YamlSerializationVisitor::class,
            $this->container->get($yamlSerializationVisitorService)
        );

        $this->assertInstanceOf(
            YamlDeserializationVisitor::class,
            $this->container->get($yamlDeserializationVisitorService)
        );
    }

    public function testFOSDisabled()
    {
        $this->container->compile();

        $this->assertFalse($this->container->has('ivory.serializer.fos'));
        $this->assertInstanceOf(ExceptionType::class, $this->container->get('ivory.serializer.type.exception'));
    }

    public function testFOSEnabled()
    {
        $this->container->setDefinition(
            'fos_rest.exception.messages_map',
            new Definition(ExceptionValueMap::class, [[]])
        );

        $this->container->compile();

        $this->assertInstanceOf(FOSSerializer::class, $this->container->get('ivory.serializer.fos'));
        $this->assertInstanceOf(FOSExceptionType::class, $this->container->get('ivory.serializer.type.exception'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessageRegExp /^The path "(.*)" does not exist\.$/
     */
    public function testMappingPathsInvalid()
    {
        $this->loadConfiguration($this->container, 'mapping_paths_invalid');
        $this->container->compile();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You must define at least one class metadata loader by enabling the reflection loader in your configuration or by registering a loader in the container with the tag "ivory.serializer.loader".
     */
    public function testMappingLoaderEmpty()
    {
        $this->loadConfiguration($this->container, 'mapping_loader_empty');
        $this->container->compile();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No "alias" attribute found for the tag "ivory.serializer.type" on the service "ivory.serializer.type.invalid".
     */
    public function testTypeCompilerMissingAlias()
    {
        $this->loadService('type_alias_missing');
        $this->container->compile();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No "direction" attribute found for the tag "ivory.serializer.visitor" on the service "ivory.serializer.visitor.invalid".
     */
    public function testVisitorCompilerMissingDirection()
    {
        $this->loadService('visitor_direction_missing');
        $this->container->compile();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The "direction" attribute (invalid) found for the tag "ivory.serializer.visitor" on the service "ivory.serializer.visitor.invalid" is not valid (Supported: serialization, deserialization).
     */
    public function testVisitorCompilerInvalidDirection()
    {
        $this->loadService('visitor_direction_invalid');
        $this->container->compile();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No "format" attribute found for the tag "ivory.serializer.visitor" on the service "ivory.serializer.visitor.invalid".
     */
    public function testVisitorCompilerMissingFormat()
    {
        $this->loadService('visitor_format_missing');
        $this->container->compile();
    }

    /**
     * @param string $service
     */
    private function loadService($service)
    {
        $loader = new XmlFileLoader($this->container, new FileLocator(__DIR__.'/../Fixtures/Service'));
        $loader->load($service.'.xml');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemPoolInterface
     */
    private function createCacheItemPoolMock()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool
            ->expects($this->any())
            ->method('getItem')
            ->will($this->returnValue($this->createCacheItemMock()));

        return $pool;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemInterface
     */
    private function createCacheItemMock()
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item
            ->expects($this->any())
            ->method('set')
            ->will($this->returnSelf());

        return $item;
    }

    /**
     * @param ClassMetadataInterface $classMetadata
     * @param mixed[][]              $properties
     * @param mixed[]                $options
     */
    private function assertClassMetadata(
        ClassMetadataInterface $classMetadata,
        array $properties,
        array $options = []
    ) {
        $this->assertSame(isset($options['xml_root']), $classMetadata->hasXmlRoot());
        $this->assertSame(isset($options['xml_root']) ? $options['xml_root'] : null, $classMetadata->getXmlRoot());

        foreach ($properties as $property => $data) {
            $this->assertTrue($classMetadata->hasProperty($property));
            $this->assertPropertyMetadata($classMetadata->getProperty($property), $data);
        }
    }

    /**
     * @param PropertyMetadataInterface $propertyMetadata
     * @param mixed[]                   $data
     */
    private function assertPropertyMetadata(PropertyMetadataInterface $propertyMetadata, array $data)
    {
        $this->assertSame(isset($data['alias']), $propertyMetadata->hasAlias());
        $this->assertSame(isset($data['alias']) ? $data['alias'] : null, $propertyMetadata->getAlias());

        $this->assertSame(isset($data['type']), $propertyMetadata->hasType());
        $this->assertSame(
            isset($data['type']) ? $data['type'] : null,
            $propertyMetadata->hasType() ? (string) $propertyMetadata->getType() : null
        );

        $this->assertSame(isset($data['readable']) ? $data['readable'] : true, $propertyMetadata->isReadable());
        $this->assertSame(isset($data['writable']) ? $data['writable'] : true, $propertyMetadata->isWritable());

        $this->assertSame(isset($data['accessor']), $propertyMetadata->hasAccessor());
        $this->assertSame(isset($data['accessor']) ? $data['accessor'] : null, $propertyMetadata->getAccessor());

        $this->assertSame(isset($data['mutator']), $propertyMetadata->hasMutator());
        $this->assertSame(isset($data['mutator']) ? $data['mutator'] : null, $propertyMetadata->getMutator());

        $this->assertSame(isset($data['since']), $propertyMetadata->hasSinceVersion());
        $this->assertSame(isset($data['since']) ? $data['since'] : null, $propertyMetadata->getSinceVersion());

        $this->assertSame(isset($data['until']), $propertyMetadata->hasUntilVersion());
        $this->assertSame(isset($data['until']) ? $data['until'] : null, $propertyMetadata->getUntilVersion());

        $this->assertSame(isset($data['max_depth']), $propertyMetadata->hasMaxDepth());
        $this->assertSame(isset($data['max_depth']) ? $data['max_depth'] : null, $propertyMetadata->getMaxDepth());

        $this->assertSame(isset($data['groups']), $propertyMetadata->hasGroups());
        $this->assertSame(isset($data['groups']) ? $data['groups'] : [], $propertyMetadata->getGroups());

        $this->assertSame(isset($data['xml_attribute']) && $data['xml_attribute'], $propertyMetadata->isXmlAttribute());
        $this->assertSame(isset($data['xml_inline']) && $data['xml_inline'], $propertyMetadata->isXmlInline());
        $this->assertSame(isset($data['xml_value']) && $data['xml_value'], $propertyMetadata->isXmlValue());
        $this->assertSame(isset($data['xml_entry']) ? $data['xml_entry'] : null, $propertyMetadata->getXmlEntry());

        $this->assertSame(
            isset($data['xml_entry_attribute']) ? $data['xml_entry_attribute'] : null,
            $propertyMetadata->getXmlEntryAttribute()
        );

        $this->assertSame(
            isset($data['xml_key_as_attribute']) ? $data['xml_key_as_attribute'] : null,
            $propertyMetadata->useXmlKeyAsAttribute()
        );

        $this->assertSame(
            isset($data['xml_key_as_node']) ? $data['xml_key_as_node'] : null,
            $propertyMetadata->useXmlKeyAsNode()
        );
    }
}
