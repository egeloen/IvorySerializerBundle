# Event

The bundle supports events and allows you to hook into the (de)-serialization process. If you want to learn more about 
the supported events, you can read this [documentation](https://github.com/egeloen/ivory-serializer/blob/master/doc/event.md).

## Register a listener

In order to register a listener on the event dispatcher, you need to use the `ivory.serializer.listener` tag as well 
as the `event` and `method` attributes:

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
            id="acme.serializer.event.listener"
            class="Acme\Serializer\Event\CustomListener"
        >
            <tag 
                name="ivory.serializer.listsner"
                event="serializer.pre_serialize"
                method="onPreSerialize"
            />
        </service>
    </services>
</container>
```

## Register a subscriber

To register a subscriber on the event dispatcher, you need to use the `ivory.serializer.subscriber` tag:

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
            id="acme.serializer.event.listener"
            class="Acme\Serializer\Event\CustomSubscriber"
        >
            <tag name="ivory.serializer.subscriber" />
        </service>
    </services>
</container>
```

## Disable Events

If you don't use events, we recommend you to disable it since it adds some overhead:

``` yaml
ivory_serializer:
    event:
        enabled: false
```
