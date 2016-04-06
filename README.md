TYPO3 CMS Composer installers
=============================

This package acts as composer plugin in order to download and install
TYPO3 core and extensions and put them into a directory structure
which is suitable for TYPO3 to work correctly.

The behavior of the installer can be influenced by configuration in the extra section of the root composer.json

```
  "extra": {
      "typo3/cms": {
          "web-dir": "web",
          "cms-package-dir": "{$vendor-dir}/typo3/cms"
      }
    }
```

#### `web-dir`
You can specify a relative path from the base directory, where the public document root should be located.
Links to the typo3 folder and the index.php will be established in this folder by the installer.

*The default value* is "", which means next to your next to your root `composer.json`. This default value is kept for compatiblity reasons, but is recommended to have the document root in a separate folder so that the vendor folder and the composer.lock file are not accessible.

#### `cms-package-dir`
You can specify a relative path from the base directory, where the typo3/cms package should be installed into.

*The default value* is "typo3_cms". This default value is kept for compatiblity reasons, but is recommended to let typo3/cms being installed in the vendor directory, which is possible with the example configuration outlined above.

#### `config-dir`
You can specify a relative path from the base directory, where the configuration folder is located.
Extensions will be installed in a sub directory `ext` within this directory.
The name of the extgension directory is implied by the first replaces entry, or (if a replaces section is not present in the extension composer.json), the second part of th vendor name, where all dashes (-) are converted to underscores (_).
Example: For the vendor name `helhum/typo3-console` an extension directory with the name `typo3_console` is implied.

*The default value* is "{$web-dir}/typo3conf". TYPO3 requires extensions to be present in `typo3conf/ext` If this value is changed, a proper publication (link or copy) of the extensions into this directory must be taken care of manually.

## Feedback/ Bugreports/ Contribution

Bugreports, feature requests and pull requests are welcome in the Github repository: https://github.com/TYPO3/CmsComposerInstallers/
