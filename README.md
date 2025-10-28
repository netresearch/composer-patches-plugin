# composer-patches-plugin

This plugin allows you to provide patches for any package from any package.

## Requirements

- PHP 8.0 or higher
- Composer 2.0 or higher

## Installation

```bash
composer require netresearch/composer-patches-plugin
```

## Compatibility

This plugin is fully compatible with:
- Composer 2.0+ (including 2.4.4+)
- PHP 8.0, 8.1, 8.2, 8.3

## Usage

If you don't want a patch package outside the root package, consider providing it as package in the [repositories key](https://getcomposer.org/doc/04-schema.md#repositories)

```json
{
    "name": "vendor/package",
    "type": "project",
    "repositories": [
        {
            "type": "package",
            "package": {
                "type": "metapackage",
                "name": "vendor/package-patches",
                "version": "1.0.0",
                "require": {
                    "netresearch/composer-patches-plugin": "~1.0"
                },
                "extra": {
                    "patches": {
                        "vendor/name": [
                            {
                                "url": "https://my-domain.com/path/to/my.patch"
                            }
                        ]
                    }
                }
            }
        }
    ],
    "require": {
        "vendor/package-patches": "~1.0"
    }
}
```

See this presentation for the original idea of this plugin: http://de.slideshare.net/christianopitz/distributed-patching-with-composer

## Patch properties

Key | Description | Required
--- | --- | ---
``url`` | The url or path to the patch | ✓
``title`` | Title to display when applying or reverting the patch |
``args`` | string, which will be added to the patch command |
``sha1`` | SHA1 checksum of the patch contents for security check - when given the patches actual checksum and this value are compared and if they don't match an exception will be thrown |

You may provide patches per package and optionally by version constraints:

## Provide patches by package only
```json
{
    "name": "netresearch/typo3-patches",
    "version": "1.0.0",
    "type": "metapackage",
    "require": {
        "netresearch/composer-patches-plugin": "~1.0"
    },
    "extra": {
        "patches": {
            "typo3/cms": [
                {
                    "title": "[FEATURE] Allow registration of different login forms",
                    "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/32f331fead9c7aa50d9248c54e3c0af75d793539"
                },
                {
                    "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
                    "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
                }
            ]
        }
    }
}
```

## Provide patches by package and versions or version constraints

***
composer.json:
```json
{
    "name": "netresearch/typo3-patches",
    "version": "1.0.0",
    "type": "metapackage",
    "require": {
        "netresearch/composer-patches-plugin": "~1.0"
    },
    "extra": {
        "patches": {
            "typo3/cms":     {
                "6.2.0-beta1": [
                    {
                        "title": "[FEATURE] Allow registration of different login forms",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/32f331fead9c7aa50d9248c54e3c0af75d793539"
                    },
                    {
                        "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
                    }
                ],
                "6.2.0-beta2": [
                    {
                        "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
                    }
                ],
                "6.2.*": [
                    {
                        "title": "[BUGFIX] Ignore dependencies on non typo3-cms-extension",
                        "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/9fe856ac96e6a53fef8277f36a4a80bace6f0ae9",
                        "sha1": "b56a1c47a67d1596c0bd8270e61c44f8911af425"
                    }
                ]
            }
        }
    }
}
```

**Note**: *When multiple version constraints match the version of the target package, all of the matching patches will be applied (canonicalized by theyr checksums, so no duplicates should occure).*

## Provide patches from URLs or paths

You can put any part of the patches object into another JSON and load it via an URL (or a path):

composer.json:
```json
{
    "name": "netresearch/typo3-patches",
    "version": "1.0.0",
    "type": "metapackage",
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

http://example.com/typo3-patches.json
```json
{
    "6.2.0-beta1": [
        {
            "title": "[FEATURE] Allow registration of different login forms",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/32f331fead9c7aa50d9248c54e3c0af75d793539"
        },
        {
            "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
        }
    ],
    "6.2.0-beta2": [
        {
            "title": "[PATCH] [BUGFIX] Flexform \"required\" on input fields applies to last field only",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/a48f8b0dae11ce7246eff43132d986bccf55b786"
        }
    ],
    "6.2.*": [
        {
            "title": "[BUGFIX] Ignore dependencies on non typo3-cms-extension",
            "url": "https://git.typo3.org/Packages/TYPO3.CMS.git/patch/9fe856ac96e6a53fef8277f36a4a80bace6f0ae9",
            "sha1": "b56a1c47a67d1596c0bd8270e61c44f8911af425"
        }
    ]
}
```

## Provide patches from local stored files
You can use absolute paths or paths relative to the application root.

composer.json:
```json
"repositories": [
        {
            "type": "package",
            "package": {
                "type": "metapackage",
                "name": "vendor/package-patches",
                "version": "1.0.0",
                "require": {
                    "netresearch/composer-patches-plugin": "~1.0"
                },
                "extra": {
                    "patches": {
                        "typo3/cms":     [
                            {
                                    "title": "[FEATURE] Allow registration of different login forms",
                                    "url": "patches/example_local_file.patch"
                            }
                        ]
                    }
                }
            }
        }
    ],
   "require": {
        ....
    },
```
I have also added a new folder at the same level as the composer.json called "patches" and add a patch file example_local_file.patch like:

```
# This patch file was generated by NetBeans IDE
# It uses platform neutral UTF-8 encoding and \n newlines.
--- a/src/com/zoho/crm/library/exception/Logger.php
+++ b/src/com/zoho/crm/library/exception/Logger.php
@@ -6,7 +6,7 @@
 	{
 		set_include_path(ZCRMConfigUtil::getConfigValue('applicationLogFilePath'));
 		$path=get_include_path();
-		if($path{strlen($path)-1}!='\/')
+		if($path[strlen($path)-1]!='\/')
 		{
 			$path=$path."/";
 		}


```

*Note* that the patch will be called with the option -p1 (remove the first slash) and in the directory of the library.

Then run the commands to update the dependency and to install patches:
```
composer update vendor/package-patches
composer install
```
    
## Requiring the patches:
just require the package with the patches.

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
