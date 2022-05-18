# WordPress stubs

This repo contains auto-genetated WordPress stubs, main reason to exist is usage with static analysis tools.


## FAQ

- Why this instead of "< insert exiting project here >"
  
This project is the only:
  - having multiple version of WP in the same branch/version
  - _not_ having stub for globals, but having stubs for constants
  - having a super-simple "override" method. By declaring a sub in the [`fixtures.php` file](https://github.com/inpsyde/wp-stubs/blob/main/fixtures.php) 
    it is possible to override the WordPress delcatation for functions, classes, and interfaces.
    That way is easy to fix wrong doc-bloc in WordPress, as well as use "advanced" doc block supported by static analyzers
    
    
# How this work

Every day, the wp.org API is called in a GitHub action to find new WordPress version.

If a new version is found, stubs are generated for it (using https://github.com/php-stubs/generator) and saved, also updating the "latest" stub.


# How to use

The main usage is for static analysis tools.

For example, for [Psalm](https://psalm.dev/):

1. Require this project (`inpsyde/wp-subs`) in your Composer's `"dev-require"`
2. Create a `psalm.xml` config file (See [docs](https://psalm.dev/docs/running_psalm/configuration/))
3. In the configuration add these lines:

```xml
    <stubs>
        <file name="vendor/inpsyde/wp-stubs/stubs/latest.php"/>
    </stubs>
```

You can replace `latest.php` with a specific WP version. See in the [`/stubs` directory](https://github.com/inpsyde/wp-stubs/tree/main/stubs) the available version.

A note: In that folder, a two-numbers version like `5.9` does not necessarily mean the exact `5.9` version from WordPress, but it means the _latest_ in the `5.9.*` series.

## License

Copyright (c) 2022, Inpsyde GmbH

This software is released under the ["GNU General Public License v2.0 or later"](LICENSE) license.
  
