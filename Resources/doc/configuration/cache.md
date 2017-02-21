# Cache

The cache system is enabled and used when the `kernel.debug` flag is disabled in order to increase performance of the 
library/bundle. This means you don't really need to care about caching since by default in production, the library will 
rely on the `cache.system` for Symfony >= 3.1 or generate a filesystem cache in the `kernel.cache_dir` of your 
application. So, by default, everything is enabled :) 

But... if you want to use your own cache strategy such as APCu, Redis, ... you can define your own PSR-6 cache pool as 
a service and configure it with:

``` yaml
ivory_serializer:
    mapping:
        cache:
            pool: acme.cache.pool
```
