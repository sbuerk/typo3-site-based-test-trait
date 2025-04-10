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

namespace SBUERK\TYPO3\Testing\Tests\Unit\Frontend;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SBUERK\TYPO3\Testing\Frontend\PhpError;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PhpErrorTest extends UnitTestCase
{
    #[Test]
    public function handlePageErrorReturnsExpectedResponse(): void
    {
        $message = 'Page not found';
        $reasons = ['Page record found, but hidden.'];
        $response = $this->phpErrorHandlePageError(404, $message, $reasons);
        $this->assertSame(404, $response->getStatusCode());
        $jsonData = \json_decode(json: (string)$response->getBody(), flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        $this->assertIsArray($jsonData);
        $this->assertNotSame([], $jsonData);
        $this->assertSame(['uri' => 'https://local.testing/', 'message' => $message, 'reasons' => $reasons], $jsonData);
    }

    /**
     * @param int $status
     * @param string $message
     * @param string[] $reasons
     * @return ResponseInterface
     */
    private function phpErrorHandlePageError(int $status, string $message, array $reasons = []): ResponseInterface
    {
        $request = new ServerRequest('https://local.testing/', 'GET');
        $subject = new PhpError($status);
        return $subject->handlePageError($request, $message, $reasons);
    }
}
