# Usage

The bundle just integrates the [Ivory Serializer](https://github.com/egeloen/ivory-serializer) library into Symfony.
In order to use the library, you can fetch the serializer from the container and use it for serializing or 
deserializing your data:

``` php
use Ivory\Serializer\Format;

$stdClass = new \stdClass();
$stdClass->foo = true;
$stdClass->bar = ['foo', [123, 432.1]];

$serializer = $container->get('ivory.serializer');

echo $serializer->serialize($stdClass, Format::JSON);
// {"foo": true,"bar": ["foo", [123, 432.1]]}

$deserialize = $serializer->deserialize($json, \stdClass::class, Format::JSON);
// $deserialize == $stdClass
```
