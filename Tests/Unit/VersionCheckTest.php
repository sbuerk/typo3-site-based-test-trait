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

namespace SBUERK\TYPO3\Testing\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VersionCheckTest extends UnitTestCase
{
    private const SUPPORTED_TYPO3_MAJOR_VERSION = 12;

    #[Test]
    public function ensureSupportedTypo3Version(): void
    {
        $this->assertSame(self::SUPPORTED_TYPO3_MAJOR_VERSION, (new Typo3Version())->getMajorVersion());
    }
}
