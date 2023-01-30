# WordPress stubs

This repo contains auto-generated WordPress stubs to be used with static analysis tools.

## FAQ

- Why this instead of "< insert exiting project here >"?

This project's purpose is:

- having multiple versions of WP in the same branch/version
- _not_ having stubs for globals, but having stubs for constants
- having a super-simple "override" method. By declaring stubs in
  the [`fixtures.php` file](https://github.com/inpsyde/wp-stubs/blob/main/fixtures.php), it is
  possible to override the WordPress declaration for functions, classes, and interfaces.
  That way, it is easy to fix incorrect DocBlocks in WordPress and use "advanced" doc block
  supported by static analyzers (think of array shape, type aliases, conditional return types …)

# How this works

Every day, a GitHub Actions workflow calls the wp.org API to find new WordPress versions.

If it finds new versions, it generates and saves stubs for them (using
https://github.com/php-stubs/generator) while also updating the "latest" stub.

# How to use

The primary usage is for static analysis tools, for example, [Psalm](https://psalm.dev/):

## Method 1: Get all WordPress versions

### Steps:

1. Require this project (`inpsyde/wp-stubs`) in your Composer's `"require-dev"` property
2. Create a `psalm.xml` config file
   (see [docs](https://psalm.dev/docs/running_psalm/configuration/))
3. In the Psalm configuration, add these lines:
    ```xml
    <stubs>
        <file name="vendor/inpsyde/wp-stubs/stubs/latest.php"/>
    </stubs>
    ```

You can replace `latest.php` with a specific WP version. See the available versions in
the [`/stubs` directory](https://github.com/inpsyde/wp-stubs/tree/main/stubs).

**Note**: In that folder, a two-digit version number like `5.9` does not necessarily mean WordPress
version `5.9`, but the _latest_ in the `5.9.*` series. So if you use Composer to require WordPress
and have a requirement like `5.9.*`, using the `stubs/5.9.php` stubs file will match the currently
installed version.

The "stubs" folder contains stubs of many versions so that you can choose, but it would also be
possible to have a CI script that loads different versions to test against different stubs.

However, the package size might get huge, and if not excluded from IDE's analysis, it might affect
the IDE performance.

## Method 2: Get a specific version

### Pre-requisite:

In your `composer.json`, declare
a [repository](https://getcomposer.org/doc/05-repositories.md#repository) like this:

```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://raw.githubusercontent.com/inpsyde/wp-stubs/main",
      "only": [
        "inpsyde/wp-stubs-versions"
      ]
    }
  ]
}
```

> **Note for Inpsyde developers**: The Inpsyde organization
> in [Private Packagist](https://packagist.com/) mirrors the above repository, so for Inpsyde's
> private repositories, this is likely not needed considering Private Packagist will probably
> be _already_ added to the repo's `composer.json`.

### Steps:

1. Require the "versioned" project in your Composer's `"require-dev"` property e.g.
   `{ "require": { "inpsyde/wp-stubs-versions": "dev-latest" } }`.
   _(Note how the package name has  `-versions` appended)_
2. Create a `psalm.xml` config file (
   see [docs](https://psalm.dev/docs/running_psalm/configuration/))
3. In the Psalm configuration, add these lines:
    ```xml
    <stubs>
        <file name="vendor/inpsyde/wp-stubs-versions/latest.php"/>
    </stubs>
    ```

Using this approach, the latest version will be the *only* version Composer downloads.

## Minimum requirements

The code that generates the stubs requires PHP 8.0+. However, when consuming the package, there are
no minimum requirements besides [being able to run
WordPress (https://wordpress.org/about/requirements/).

## License

Copyright (c) 2022, Inpsyde GmbH

This software is released under the ["MIT"](LICENSE) license.
