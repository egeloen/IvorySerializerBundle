# Mapping

The mapping configuration allows you to configure how and where metadatas are loaded by the Serializer.

## Auto Mapping

By default, the bundle automatically registers the following directory and files for each bundles (if they exist):

```
Resources/config/ivory-serializer
Resources/config/ivory-serializer.json
Resources/config/ivory-serializer.xml
Resources/config/ivory-serializer.yml
```

That means you just need to put your metadatas in the `Resources/config/ivory-serializer` directory or in the 
`Resources/config/ivory-serializer.(json|xml|yml)` file of your bundle.

Hopefully, these paths are configurable. If you would prefer to use the `Resources/config/serializer` directory as 
well as the `Resources/config/serializer.xml`, you can use:

``` yaml
ivory_serializer:
    mapping:
        auto:
            paths:
                - Resources/config/serializer
                - Resources/config/serializer.xml
```

If you don't want to use the auto mapping feature, you can disable it:

``` yaml
ivory_serializer:
    mapping:
        auto:
            enabled: false
```

## Manual Mapping

The manual mapping allows you to expose global paths to the serializer:

``` yaml
ivory_serializer:
    mapping:
        paths:
            - %kernel.root_dir%/Resources/serializer
            - %kernel.root_dir%/Resources/serializer.json
            - %kernel.root_dir%/Resources/serializer.xml
            - %kernel.root_dir%/Resources/serializer.yml
```

By default, there are no global paths configured.

## Annotation

The bundle enables annotation support if the `AnnotationReader` class exists. If you prefer to disable it in all cases, 
you can use:

``` yaml
ivory_serializer:
    mapping:
        annotations: false
```

## Reflection

The bundle uses reflection by default in addition to other loaders to extract your metadatas. If you prefer disable it, 
you can use:

``` yaml
ivory_serializer:
    mapping:
        reflection: false
```

## Custom

If you want to programmatically register a mapping, you just need to register the loader you want and use the 
`ivory.serializer.loader` tag. In the following example, we configure the `DirectoryClassMetadataLoader` in order to 
load the `%kernel.root_dir%/Resources/serializer` directory.

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
            id="acme.serializer.loader"
            class="Ivory\Serializer\Mapping\Loader\DirectoryClassMetadataLoader"
        >
            <argument>%kernel.root_dir%/Resources/serializer</argument>
            <argument type="service" id="ivory.serializer.type.parser" />
            <tag name="ivory.serializer.loader" />
        </service>
    </services>
</container>
```
