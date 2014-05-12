composer-patches-plugin
=======================

Plugin for composer to apply patches onto dependencies.

Slides: http://de.slideshare.net/christianopitz/distributed-patching-with-composer

Providing patches
-----------------

You can provide the patches in any package through the extra object (you are free but don't have to bundle your patches in "patches" packages):

***
composer.json:
```json
{
    "name": "netresearch/typo3-patches",
    "version": "1.0.0",
    "type": "patches",
    "require": {
        "netresearch/composer-patches-plugin": "~1.0"
    },
    "extra": {
        "patches": {
            "typo3/cms":     {
                "6.2.0-beta1": {
                    "32f331fead9c7aa50d9248c54e3c0af75d793539": {
                        "title": "[FEATURE] Allow registration of different login forms",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/32f331fead9c7aa50d9248c54e3c0af75d793539"
                    },
                    "a48f8b0dae11ce7246eff43132d986bccf55b786 ": {
                        "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
                    }
                },
                "6.2.0-beta2": {
                    "a48f8b0dae11ce7246eff43132d986bccf55b786 ": {
                        "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
                    }
                },
                "6.2.0-beta3": {
                    "a48f8b0dae11ce7246eff43132d986bccf55b786 ": {
                        "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
                    }
                }
            }
        }
    }
}
```
***
You can put any part of the patches object into another JSON and load it via an URL (or a path):
***
composer.json:
```json
{
    "name": "netresearch/typo3-patches",
    "version": "1.0.0",
    "type": "patches",
    "require": {
        "netresearch/composer-patches-plugin": "~1.0"
    },
    "extra": {
        "patches": {
            "typo3/cms": "http://example.com/typo3-patches.json"
        }
    }
}
```
***
http://example.com/typo3-patches.json
```json
{
    "6.2.0-beta1": {
        "32f331fead9c7aa50d9248c54e3c0af75d793539": {
            "title": "[FEATURE] Allow registration of different login forms",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/32f331fead9c7aa50d9248c54e3c0af75d793539"
        },
        "a48f8b0dae11ce7246eff43132d986bccf55b786 ": {
            "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
        }
    },
    "6.2.0-beta2": {
        "a48f8b0dae11ce7246eff43132d986bccf55b786 ": {
            "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
        }
    },
    "6.2.0-beta3": {
        "a48f8b0dae11ce7246eff43132d986bccf55b786 ": {
            "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
        }
    }
}
```
## Requiring the patches:
just require the package with the patches. If you don't want a patch package outside the root package, consider providing it as package in the [repositories key](https://getcomposer.org/doc/04-schema.md#repositories)
```json
{
    "name": "netresearch/patched-typo3",
    "type": "project",
    "description": "A patched version of typo3",
    "minimum-stability": "dev",
    "require": {
        "netresearch/typo3-patches": "~1.0",
        "typo3/cms": "6.2.0-beta3"
    }
}
```
