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


## Method 1: Get all WordPress Versions

### Steps:

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

The "stubs" folder contain stubs or many versions, so you can choose, but it would also be possible
to have a CI scripts that loads different versions to test against different stubs.

However, the package size might get very big and if not excluded from IDE's analysis it might affect
IDE performance.


## Method 2: Get a Specific Version

### Pre-requisite:
 
In your `composer.json`, declare a [repository](https://getcomposer.org/doc/05-repositories.md#repository)
like this:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://raw.githubusercontent.com/inpsyde/wp-stubs/main",
            "only": ["inpsyde/wp-stubs-versions"]
        }
    ]
}
```

> **Note for Inpsyde developers**: The above repository is mirrored in Inpsyde's 
[Private Packagist](https://packagist.com/), so for Inpsyde's private repositories the above is likely
not needed considering Private Packagist will probably be _already_ added to the repo's `composer.json`.

### Steps:

1. Require the "versioned" project in your Composer's `"require-dev"` property e.g.
   `{ "require": { "inpsyde/wp-stubs-versions": "dev-latest" } }`.
   _(Note how the package name has "-versions" appended)_
2. Create a `psalm.xml` config file (See [docs](https://psalm.dev/docs/running_psalm/configuration/))
3. In the configuration add these lines:

```xml
    <stubs>
        <file name="vendor/inpsyde/wp-stubs-versions/stubs/latest.php" />
    </stubs>
```

Using this approach, the latest version will be the *only* version Composer will download.


## Minimum requirements

The code that generates the stubs requires PHP 8.0+, however, when consuming the package there are no
minimum requirements, besides [being able to run WordPress](https://wordpress.org/about/requirements/).


## License

Copyright (c) 2022, Inpsyde GmbH

This software is released under the ["MIT"](LICENSE) license.
