# WordPress stubs

This repo contains auto-generated WordPress stubs to be used with static analysis tools.


## FAQ

- Why this instead of "<insert exiting project here>"?
  
This project's purpose is:
  - having multiple versions of WP in the same branch/version
  - _not_ having stubs for globals, but having stubs for constants
  - having a super-simple "override" method. By declaring stubs in the [`fixtures.php` file](https://github.com/inpsyde/wp-stubs/blob/main/fixtures.php) 
    it is possible to override the WordPress declaration for functions, classes, and interfaces.
    That way it is easy to fix incorrect doc-bloc in WordPress, as well as use "advanced" doc block
    supported by static analyzers (think of array shape, type aliases, conditional return types...)
    
    
# How this work

Every day, the wp.org API is called in a GitHub Action to find new WordPress versions.

If new versions are found, stubs are generated for it (using https://github.com/php-stubs/generator) and saved, also updating the "latest" stub.


# How to use

The main usage is for static analysis tools.

For example, for [Psalm](https://psalm.dev/):

1. Require this project (`inpsyde/wp-stubs`) in your Composer's `"require-dev"` property
2. Create a `psalm.xml` config file (See [docs](https://psalm.dev/docs/running_psalm/configuration/))
3. In the configuration add these lines:

```xml
    <stubs>
        <file name="vendor/inpsyde/wp-stubs/stubs/latest.php"/>
    </stubs>
```

You can replace `latest.php` with a specific WP version. See in the [`/stubs` directory](https://github.com/inpsyde/wp-stubs/tree/main/stubs) the available version.

**A note**: In that folder, a two-numbers version like `5.9` does not necessarily mean the exact `5.9` version 
from WordPress, but it means the _latest_ in the `5.9.*` series, so if you use Composer to require WordPress
and have a requirement like `5.9.*`, using the `stubs/5.9.php` stubs file, that will match the current version
installed.


## Minimum requirements

The code that generates the stubs requires PHP 7.4+, however when consuming the package there are no
minimum requirements, besides [being able to run WordPress](https://wordpress.org/about/requirements/).


## License

Copyright (c) 2022, Inpsyde GmbH

This software is released under the ["MIT"](LICENSE) license.
