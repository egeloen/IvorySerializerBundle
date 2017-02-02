# FOSRestBundle Integration

If you want to use the Ivory Serializer with for the [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle), 
you just need to set up the FOSRestBundle with the following configuration:

``` yaml
fos_rest:
    service:
        serializer: ivory.serializer.fos
```
