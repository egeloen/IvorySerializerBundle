# Visitors

When you (de)-serialize your data, the serializer will choose a visitor according to your format (csv, json, ...) and
your direction (serialization or deserialization). Each format/direction have a dedicated visitor in order to
handle this specific use case.

## Built-in

The bundle integrates all the [Serializer visitors](https://github.com/egeloen/ivory-serializer/blob/master/doc/visitor.md).

## Configuration

Each visitor can be globally configured in your configuration file.

### CSV

The CSV visitor can be configured in order to customize how the data should be (de)-serialized when using the CSV
format: 

``` yaml
ivory_serializer:
    visitors:
        csv:
            delimiter: ","
            enclosure: '"'
            escape_char: "\\"
            key_separator: "."
```

### JSON

The JSON visitor can be configured in order to customize how the data should be (de)-serialized when using the JSON
format:

``` yaml
ivory_serializer:
    visitors:
        json:
            max_depth: 512
            options: 0
```

### XML

The XML visitor can be configured in order to customize how the data should be (de)-serialized when using the XML
format:

``` yaml
ivory_serializer:
    visitors:
        xml:
            version: "1.0"
            encoding: UTF-8
            format_output: "%kernel.debug%"
            root: result
            entry: entry
            entry_attribute: key
```

### YAML

The YAML visitor can be configured in order to customize how the data should be (de)-serialized when using the YAML
format:

``` yaml
ivory_serializer:
    visitors:
        yaml:
            inline: 2
            indent: 4
            options: 0
```

## Custom

``` xml
<?xml version="1.0" encoding="UTF-8" ?>

<container
    xmlns="http://symfony.com/schema/dic/services"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://symfony.com/schema/dic/services
                        http://symfony.com/schema/dic/services/services-1.0.xsd"
>
    <services>
        <service
            id="acme.serializer.visitor.custom.serialization"
            class="Acme\Serializer\Visitor\Custom\CustomSerializationVisitor"
        >
            <tag name="ivory.serializer.visitor" direction="serialization" format="custom" />
        </service>
        
        <service
            id="acme.serializer.visitor.custom.deserialization"
            class="Acme\Serializer\Visitor\Custom\CustomDeserializationVisitor"
        >
            <tag name="ivory.serializer.visitor" direction="deserialization" format="custom" />
        </service>
    </services>
</container>
```
