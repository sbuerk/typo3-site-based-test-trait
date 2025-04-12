<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace SBUERK\TYPO3\Testing\Tests\Functional\SiteBasedTestTrait;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteBasedTestTraitWithTestingFrameworkFunctionalTestCaseTest extends FunctionalTestCase
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
                ),
            ],
        );
    }
}
