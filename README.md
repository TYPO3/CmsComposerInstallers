TYPO3 CMS Composer installers
=============================

This package acts as composer plugin in order to download and install
TYPO3 core and extensions and put them into a directory structure
which is suitable for TYPO3 to work correctly.

The behavior of the installer can be influenced by configuration in the `extra` section of the root `composer.json`

```
  "extra": {
      "typo3/cms": {
          "web-dir": "public"
      }
    }
```

#### `web-dir`
You can specify a relative path from the base directory, where the public document root should be located.

*The default value* is `"public"`, which means a `"public"` directory at the same level as your root `composer.json`.

#### `app-dir`
This configuration option only applies to TYPO3 9.2 and above.
You can specify a relative path from the base directory, where the TYPO3 application directory should be located.
This directory will contain the TYPO3 folders `var` and `config`.

*The default value* is the base directory, which means at the same level as your root `composer.json`.

## Feedback/ Bug reports/ Contribution

Bug reports, feature requests and pull requests are welcome in the Github repository: https://github.com/TYPO3/CmsComposerInstallers/
