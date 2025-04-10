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

namespace SBUERK\TYPO3\Testing\TestCase;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase as TestingFrameworkFunctionalTestCase;

class FunctionalTestCase extends TestingFrameworkFunctionalTestCase
{
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
    ): void {
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $page = $connection->select(['*'], 'pages', ['uid' => $pageId])->fetchAssociative();
        if (empty($page)) {
            $this->fail('Cannot set up frontend root page "' . $pageId . '"');
        }

        // migrate legacy definition to support `constants` and `setup`
        if ($typoScriptFiles !== []
            && (!isset($typoScriptFiles['constants']) || !is_array($typoScriptFiles['constants']) || $typoScriptFiles['constants'] === [])
            && (!isset($typoScriptFiles['setup']) || !is_array($typoScriptFiles['setup']) || $typoScriptFiles['setup'] === [])
        ) {
            $typoScriptFiles = ['setup' => $typoScriptFiles];
        }
        /** @var array{constants?: string[], setup?: string[]} $typoScriptFiles */
        $connection->update(
            'pages',
            ['is_siteroot' => 1],
            ['uid' => $pageId]
        );
        $templateFields = array_merge(
            [
                'title' => '',
                'constants' => '',
                'config' => '',
            ],
            $templateValues,
            [
                'pid' => $pageId,
                'clear' => 3,
                'root' => 1,
            ]
        );
        foreach ($typoScriptFiles['constants'] ?? [] as $typoScriptFile) {
            if (!str_starts_with($typoScriptFile, 'EXT:')) {
                // @deprecated will be removed in version 8, use "EXT:" syntax instead
                $templateFields['constants'] .= '<INCLUDE_TYPOSCRIPT: source="FILE:' . $typoScriptFile . '">' . LF;
            } else {
                $templateFields['constants'] .= '@import \'' . $typoScriptFile . '\'' . LF;
            }
        }
        foreach ($typoScriptFiles['setup'] ?? [] as $typoScriptFile) {
            if (!str_starts_with($typoScriptFile, 'EXT:')) {
                // @deprecated will be removed in version 8, use "EXT:" syntax instead
                $templateFields['config'] .= '<INCLUDE_TYPOSCRIPT: source="FILE:' . $typoScriptFile . '">' . LF;
            } else {
                $templateFields['config'] .= '@import \'' . $typoScriptFile . '\'' . LF;
            }
        }
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $connection->delete('sys_template', ['pid' => $pageId]);
        if ($createSysTemplateRecord) {
            $connection->insert(
                'sys_template',
                $templateFields
            );
        }
    }
}
