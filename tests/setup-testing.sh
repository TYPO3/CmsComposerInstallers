#!/bin/bash

# Script is used to setup a TYPO3 for testing under /tmp/typo3-cms-installer
SCRIPT_PATH=$PWD/`dirname "${BASH_SOURCE[0]}"`
TYPO3_FOLDER=/tmp/typo3-cms-installer

[ -d "$TYPO3_FOLDER" ] && rm -Rf $TYPO3_FOLDER
composer create-project typo3/cms-base-distribution $TYPO3_FOLDER || exit 1
cd $TYPO3_FOLDER
composer config repositories.local path "$SCRIPT_PATH/../"
composer config repositories.composer vcs git@github.com:ochorocho/composer.git
composer config minimum-stability dev
# Require
composer req --prefer-source composer/composer:dev-improve-installed-versions-9648 || exit 1
composer req georgringer/news:^11 || exit 1
# Running local composer here to generate required files until MR was merged, see https://github.com/composer/composer/pull/9699
./vendor/bin/composer install --prefer-source --no-plugins
./vendor/bin/composer update --prefer-source typo3/cms-composer-installers && ./vendor/bin/composer dump-autoload
cd $PWD
