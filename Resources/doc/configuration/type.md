# Type

When you deserialize your data or when you configure your metadata mapping, you can specify a type. This type is not 
mandatory except for deserializing but it is highly recommended to configure it in order to make the library faster.

## Built-in

The bundle integrates all the [Serializer types](https://github.com/egeloen/ivory-serializer/blob/master/doc/type.md)
and also support the following types (available via the bundle):

| Type                                   | Description                             |
| -------------------------------------- | --------------------------------------- |
| `Symfony\Component\Form\FormInterface` | Symfony form (serialization only)       |
| `Symfony\Component\Form\FormError`     | Symfony form error (serialization only) |

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

### Exception

If the kernel is in non debug mode, the serializer does not expose exception data but generate a generic structure 
(code => 500, message => Internal Server Error). If your want to always get this behavior, you can force it with:

``` php
ivory_serializer:
    types:
        exception:
            debug: false
```

Additionally, if the [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle) is loaded, the serializer will 
use your exception map or the template status code in order to find the appropriate message (code => status code, 
message => appropriate message according to your configuration).

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

By default, the type is registered for both directions (serialization and deserialization). You can also register a 
type just for a specific direction:

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
            <tag name="ivory.serializer.type" alias="custom" direction="serialization" />
        </service>
    </services>
</container>
```
