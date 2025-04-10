<?php

declare(strict_types=1);

namespace SBUERK\TYPO3\Testing\Tests\Functional\SiteBasedTestTrait;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;

final class SiteBasedTestTraitWithCustomFunctionalTestCaseTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
    ];

    #[Test]
    public function siteConfigurationCanBeWritten(): void
    {
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://acme.com/',
            ),
            [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: 'https://acme.com/',
                ),
                $this->buildLanguageConfiguration(
                    identifier: 'FR',
                    base: 'https://acme.fr/',
                    fallbackIdentifiers: ['EN'],
                    fallbackType: 'strict',
                )
            ],
        );
    }
}