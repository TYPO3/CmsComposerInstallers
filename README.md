[![CI Status](https://github.com/TYPO3/CmsComposerInstallers/workflows/CI/badge.svg?branch=master)](https://github.com/TYPO3/CmsComposerInstallers/actions?query=workflow%3ACI)

TYPO3 CMS Composer installers
=============================

This package acts as composer plugin in order to download and install
TYPO3 core and extensions and put them into a directory structure
which is suitable for TYPO3 to work correctly.

The behavior of the installer can be influenced by configuration in the `extra` section of the root `composer.json`

## Options for extensions

### `extension-key`

**required**

```
  "extra": {
      "typo3/cms": {
          "extension-key": "bootstrap_package"
      }
    }
```

Specify the extension key.

## Options for project composer.json

#### `web-dir`

```
  "extra": {
      "typo3/cms": {
          "web-dir": "public"
      }
    }
```

You can specify a relative path from the base directory, where the public document root should be located.

*The default value* is `"public"`, which means a `"public"` directory at the same level as your root `composer.json`.

#### `app-dir`
You can specify a relative path from the base directory, where the TYPO3 application directory should be located.
This directory will contain the TYPO3 folders `var` and `config`.
It is **not** recommended to change this directory. If you have to, e.g. to simplify a CI setup for TYPO3 extensions,
the `web-dir` path **must** be a subdirectory of `app-dir`. Otherwise the `app-dir` directive will be ignored.

*The default value* is the base directory, which means at the same level as your root `composer.json`.

## Feedback/ Bug reports/ Contribution

Bug reports, feature requests and pull requests are welcome in the Github repository: https://github.com/TYPO3/CmsComposerInstallers/
