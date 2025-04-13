# Provides modified TYPO3 `SiteBasedTestTrait`

## Compatibility

| branch | version   | TYPO3           | testing-framework |
|--------|-----------|-----------------|-------------------|
| main   | 3.x.x-dev | main,14.0.0-dev | main, 9           |
| 2      | 2.x.x-dev | 13.4,13.4.x-dev | 9                 |
| 1      | 1.x.x-dev | 12.4,12.4.x-dev | 8                 |

## Description

This package aims to provide a slightly extended (modified) `SiteBasedTestTrait` to allow easier usage for functional
test TYPO3 Extensions or projects, hiding away internal requirements and cross TYPO3 Core Version changes introduced
over time.

TYPO3 Core provides the trait only in the test namespace, stripped from distribution composer packages and are not 
available out of the box. It is possible to tell composer to install the package from source and requiring to add
the TYPO3 system extension test namespace to the own root composer.json, which can easily be missed. This package
makes the life easier.

Additionally, a custom [FunctionalTestCase](#extended-functionaltestcase) extending the `typo3/testing-framework`
counter-part is provided with a modified [setUpFrontendRootPage() method](#functionaltestcasesetupfrontendrootpage)
in preparation for TYPO3 v13 to make it possible to init site roots without creating a `sys_template` to make it
possible to write site sets based templates. This is already provided for the TYPO3 v12 variante to have the same
API in place to ensure working state without static code analysis errors when multiple core version tests are used,
like it is the case for extensions.

> [!IMPORTANT]
> This should still be taken as experimental. It is tried hard to provide the same
> outer surface across versions as long as possible, but this cannot be guaranteed.

## Installation

> [!NOTE]
> This package should only be installed as development dependency and
> not deployed to production instances. It does not serve any purpose
> in production code.

```shell
composer require --dev 'sbuerk/typo3-site-based-test-trait'
```

Extension authors may support multiple TYPO3 versions with one extension version and
needs to have working `SiteBasedTestTrait` for the corresponding TYPO3 version, which
changes under the hood. To make maintenance and static code analysises easier, this
package supports only one core version per version and extension authors needs to add
conditional constraints for this package and let composer install the suitable version
along with other dependencies, for example to suppor TYPO3 v12 and v13:

```shell
composer require --dev 'sbuerk/typo3-site-based-test-trait':'^1 || ^2'
```

## Differences to the TYPO3 Core implementation of `SiteBasedTestTrait`

### Fail test instead of mark it as skipped

In case that something went wrong, language could not found in the preset and similar the TYPO3 implementation
marks the test as skipped and literally hiding away issues which are hard to identify and find.

This package changes them and let tests fail when these things happen to point directly to an issue in the tests.

### Better code annotation

The annotations of the core are simple, related to the lower PHPStan level used. That produces a lot of noise
in projects or extensions using PHPStan on a higher level and requires to add them to the baseline for each
`FunctionalTestCase`.

To mitigate this, the annotations are enhanced for the cloned trait to survive higher PHPStan levels directly
checked as part of the package testing.

### `writeSiteConfiguration()`

The `SiteBasedTestTrait::writeSiteConfiguration()` method got a additional argument `array $additional`, which
can be used to provide additional `SiteConfig` content, for example routing information.

**Example Usage**

```php
$this->writeSiteConfiguration(
    identifier: 'acme',
    site: [], // $this->buildSiteConfiguration(...)
    languages: [], // [$this->buildDefaultLanguageConfiguration(...), $this->buildLanguageConfiguration(...), ...]
    errorHandling: [], // $this->buildErrorHandlingConfiguration(...)
    // additional content
    additional: [
      'settings' => [
        'some_settings' => 123,
      ],
    ],
);
```

### `buildSiteConfiguration()`

This method got a additional argument `$additionalRootConfiguration`, which also allows to add custom things on root
level to the `SiteConfiguation` similar to [writeSiteConfiguration() argument additional](#writesiteconfiguration).

```php
$this->buildSiteConfiguration(
    rootPageId: 1,
    base: 'https://acme.com/',
    additionalRootConfiguration: [
      'settings' => [
        'some_settings' => 123,
      ],    
    ],
);
```

### `LANGUAGE_PRESETS` class property

TYPO3 has a strong limitation which is read from the `LANGUAGE_PRESETS` class property, which is extended to allow
custom values for language definitions in the `SiteConfiguration`, which `web-vision/deepltranslate-core` uses as
an example.

```php
protected const LANGUAGE_PRESETS = [
    'EN' => [
        'id' => 0,
        'title' => 'English',
        'locale' => 'en_US.UTF8',
        // custom values added to the language block
        'custom' => [
            'deepltranslate_language' => 'EN',
        ],
    ],
    'FR' => [
        'id' => 1,
        'title' => 'French',
        'locale' => 'fr_FR.UTF8',
        // custom values added to the language block
        'custom' => [
            'deepltranslate_language' => 'FR',
        ],        
    ],
];
```

## Extended  `FunctionalTestCase`

A extended `FunctionalTestCase` is provided with a modified [setUpFrontendRootPage() method](#functionaltestcasesetupfrontendrootpage).

### `FunctionalTestCase::setUpFrontendRootPage()`

Signature of modified method:

```php
/**
 * Sets up a root-page containing TypoScript settings for frontend testing.
 *
 * Parameter `$typoScriptFiles` can either be
 * + `[
 *      'EXT:extension/path/first.typoscript',
 *      'EXT:extension/path/second.typoscript'
 *    ]`
 *   which just loads files to the setup setion of the TypoScript template
 *   record (legacy behavior of this method)
 * + `[
 *      'constants' => ['EXT:extension/path/constants.typoscript'],
 *      'setup' => ['EXT:extension/path/setup.typoscript']
 *    ]`
 *   which allows to define contents for the `constants` and `setup` part
 *   of the TypoScript template record at the same time
 *
 * @param int $pageId
 * @param array{constants?: string[], setup?: string[]}|string[] $typoScriptFiles
 * @param array<string, mixed> $templateValues
 * @param bool $createSysTemplateRecord TRUE if sys_template record should be created, FALSE does not create one
 *                                      but removes an existing one.
 */
protected function setUpFrontendRootPage(
    int $pageId,
    array $typoScriptFiles = [],
    array $templateValues = [],
    bool $createSysTemplateRecord = true,
): void;
```

The main differnce is the 4th parameter. If this is set to false, no `sys_template` record is created for the given
`$pageId` - and silently ignoring `$typoScriptFiles` and `$templateValues`.

To simply ensure page is set as rootpage without creating a `sys_template` row, following is enough:

```php
$this->setUpFrontendRootPage(
    pageId: 1000,
    createSysTemplateRecord: false,
);
```

of without named arguments:

```php
$this->setUpFrontendRootPage(
    1000,
    [], // will be ignored/not used due to false as 4th argument
    [], // will be ignored/not used due to false as 4th argument
    false,
);
```