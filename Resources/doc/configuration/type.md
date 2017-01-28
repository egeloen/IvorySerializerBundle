# Type

When you deserialize your data or when you configure your metadata mapping, you can specify a type. This type is not 
mandatory except for deserializing but it is highly recommended to configure it in order to make the library faster.

## Built-in

The bundle integrates all the [Serializer types](https://github.com/egeloen/ivory-serializer/blob/master/doc/type.md).

## Configuration

Some types can be globally configured in your configuration file.

### DateTime

By default, the date time type uses the `DateTime::RFC3339` as format and use the `date_default_timezone_get` in order 
to determine the timezone. If you want to override these configurations, you can use:

``` yaml
ivory_serializer:
    types:
        date_time:
            format: "Y-m-d H:i:s"
            timezone: UTC
```

## Custom

If you define your own type, you need to register it by using the `ivory.serializer.type` tag and the `alias` 
attribute representing the name of the type:

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
            id="acme.serializer.type.custom"
            class="Acme\Serializer\Type\CustomType"
        >
            <tag name="ivory.serializer.type" alias="custom" />
        </service>
    </services>
</container>
```
