# README

[![Travis Build Status](https://travis-ci.org/egeloen/IvorySerializerBundle.svg?branch=master)](http://travis-ci.org/egeloen/IvorySerializerBundle)
[![AppVeyor Build status](https://ci.appveyor.com/api/projects/status/8ydvhbgwsy0k39ux/branch/master?svg=true)](https://ci.appveyor.com/project/egeloen/ivoryserializerbundle/branch/master)
[![Code Coverage](https://scrutinizer-ci.com/g/egeloen/IvorySerializerBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/egeloen/IvorySerializerBundle/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/egeloen/IvorySerializerBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/egeloen/IvorySerializerBundle/?branch=master)
[![Dependency Status](http://www.versioneye.com/php/egeloen:serializer-bundle/badge.svg)](http://www.versioneye.com/php/egeloen:serializer-bundle)

[![Latest Stable Version](https://poser.pugx.org/egeloen/serializer-bundle/v/stable.svg)](https://packagist.org/packages/egeloen/serializer-bundle)
[![Latest Unstable Version](https://poser.pugx.org/egeloen/serializer-bundle/v/unstable.svg)](https://packagist.org/packages/egeloen/serializer-bundle)
[![Total Downloads](https://poser.pugx.org/egeloen/serializer-bundle/downloads.svg)](https://packagist.org/packages/egeloen/serializer-bundle)
[![License](https://poser.pugx.org/egeloen/serializer-bundle/license.svg)](https://packagist.org/packages/egeloen/serializer-bundle)

The bundle provides an integration of the [Ivory Serializer](https://github.com/egeloen/ivory-serializer) library for
your Symfony2 project.

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

## Documentation

 - [Installation](/Resources/doc/installation.md)
 - [Usage](/Resources/doc/usage.md)
 - [Configuration](/Resources/doc/configuration/index.md)
    - [Mapping](/Resources/doc/configuration/mapping.md)
    - [Type](/Resources/doc/configuration/type.md)
    - [Event](/Resources/doc/configuration/event.md)
    - [Visitor](/Resources/doc/configuration/visitor.md)
    - [Cache](/Resources/doc/configuration/cache.md)
    - [FOSRestBundle Integration](/Resources/doc/configuration/fos_rest.md)

## Testing

The bundle is fully unit tested by [PHPUnit](http://www.phpunit.de/) with a code coverage close to **100%**. To
execute the test suite, check the travis [configuration](/.travis.yml).

## Contribute

We love contributors! Ivory is an open source project. If you'd like to contribute, feel free to propose a PR! You
can follow the [CONTRIBUTING](/CONTRIBUTING.md) file which will explain you how to set up the project.

## License

The Ivory Google Map Bundle is under the MIT license. For the full copyright and license information, please read the
[LICENSE](/LICENSE) file that was distributed with this source code.
