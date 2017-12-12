TYPO3 CMS Composer installers
=============================

This package acts as composer plugin in order to download and install
TYPO3 core and extensions and put them into a directory structure
which is suitable for TYPO3 to work correctly.

The behavior of the installer can be influenced by configuration in the `extra` section of the root `composer.json`

```
  "extra": {
      "typo3/cms": {
          "web-dir": "web"
      }
    }
```

#### `web-dir`
You can specify a relative path from the base directory, where the public document root should be located.
Links to the `typo3` folder and the `index.php` will be established in this folder by the installer.

*The default value* is `"public"`, which means a `"public"` directory at the same level as your root `composer.json`.

## Feedback/ Bugreports/ Contribution

Bug reports, feature requests and pull requests are welcome in the Github repository: https://github.com/TYPO3/CmsComposerInstallers/
