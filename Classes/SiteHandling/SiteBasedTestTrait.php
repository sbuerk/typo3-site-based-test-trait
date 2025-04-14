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

namespace SBUERK\TYPO3\Testing\SiteHandling;

use SBUERK\TYPO3\Testing\Frontend\PhpError;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\InstructionInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * Be sure to set the LANGUAGE_PRESETS const in your class.
 *
 * Cloned and extended from {@see \TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait}
 */
trait SiteBasedTestTrait
{
    /**
     * @param string[] $items
     */
    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        if ($items === []) {
            return;
        }

        static::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }

    /**
     * @param non-empty-string $identifier
     * @param array<string, mixed> $site
     * @param list<array<string, mixed>> $languages
     * @param array<string, mixed> $errorHandling
     * @param array<string, mixed> $additional
     */
    protected function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = [],
        array $additional = [],
    ): void {
        $configuration = $site;
        if ($languages !== []) {
            $configuration['languages'] = $languages;
        }
        if ($errorHandling !== []) {
            $configuration['errorHandling'] = $errorHandling;
        }
        if ($additional !== []) {
            ArrayUtility::mergeRecursiveWithOverrule($configuration, $site);
        }
        $siteWriter = $this->get(SiteWriter::class);
        try {
            // ensure no previous site configuration influences the test
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/' . $identifier, true);
            $siteWriter->write($identifier, $configuration);
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @param non-empty-string $identifier
     * @param array<string, mixed> $overrides
     */
    protected function mergeSiteConfiguration(
        string $identifier,
        array $overrides,
    ): void {
        $siteConfiguration = $this->get(SiteConfiguration::class);
        $siteWriter = $this->get(SiteWriter::class);
        $configuration = $siteConfiguration->load($identifier);
        ArrayUtility::mergeRecursiveWithOverrule($configuration, $overrides);
        try {
            $siteWriter->write($identifier, $configuration);
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @param non-empty-string $base
     * @param non-empty-string $websiteTitle
     * @param array<non-empty-string, mixed> $additionalRootConfiguration
     * @return array<non-empty-string, mixed>
     */
    protected function buildSiteConfiguration(
        int $rootPageId,
        string $base = '/',
        string $websiteTitle = 'Home',
        array $additionalRootConfiguration = [],
    ): array {
        return array_merge(
            [
                'rootPageId' => $rootPageId,
                'base' => $base,
                'websiteTitle' => $websiteTitle,
            ],
            $additionalRootConfiguration,
        );
    }

    /**
     * @param non-empty-string $identifier
     * @param non-empty-string $base
     * @return array<string, mixed>
     */
    protected function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base,
    ): array {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);
        return $configuration;
    }

    /**
     * @param non-empty-string $identifier
     * @param non-empty-string $base
     * @param non-empty-string[] $fallbackIdentifiers
     * @param non-empty-string|null $fallbackType
     * @return array<string, mixed>
     */
    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        ?string $fallbackType = null,
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'websiteTitle' => $preset['websiteTitle'] ?? '',
            'base' => $base,
            'locale' => $preset['locale'],
            'flag' => $preset['iso'] ?? '',
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];
        //--------------------------------------------------------------------------------------------------------------
        // SBUERK - CUSTOM LANGUAGE DATA BORROWED - Used for example by `deepltranslate-core` and addons.
        //--------------------------------------------------------------------------------------------------------------
        if (isset($preset['custom'])
            && is_array($preset['custom'])
            && $preset['custom'] !== []
        ) {
            $configuration = array_replace(
                $configuration,
                $preset['custom']
            );
        }
        //--------------------------------------------------------------------------------------------------------------
        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    /**
     * @param non-empty-string $handler
     * @param int[] $codes
     * @return array<non-empty-string, mixed>
     */
    protected function buildErrorHandlingConfiguration(
        string $handler,
        array $codes,
    ): array {
        if ($handler === 'Page') {
            // This implies you cannot test both 404 and 403 in the same test.
            // Fixing that requires much deeper changes to the testing harness,
            // as the structure here is only a portion of the config array structure.
            if (in_array(404, $codes, true)) {
                $baseConfiguration = [
                    'errorContentSource' => 't3://page?uid=404',
                ];
            } elseif (in_array(403, $codes, true)) {
                $baseConfiguration = [
                    'errorContentSource' => 't3://page?uid=403',
                ];
            }
        } elseif ($handler === 'Fluid') {
            $baseConfiguration = [
                'errorFluidTemplate' => 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/FluidError.html',
                'errorFluidTemplatesRootPath' => '',
                'errorFluidLayoutsRootPath' => '',
                'errorFluidPartialsRootPath' => '',
            ];
        } elseif ($handler === 'PHP') {
            $baseConfiguration = [
                'errorPhpClassFQCN' => PhpError::class,
            ];
        } else {
            throw new \LogicException(
                sprintf('Invalid handler "%s"', $handler),
                1533894782
            );
        }

        $baseConfiguration['errorHandler'] = $handler;

        return array_map(
            static function (int $code) use ($baseConfiguration) {
                $baseConfiguration['errorCode'] = $code;
                return $baseConfiguration;
            },
            $codes
        );
    }

    /**
     * @param non-empty-string $identifier
     * @return array<non-empty-string, mixed>
     */
    protected function resolveLanguagePreset(string $identifier): array
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }

    /**
     * @param InternalRequest $request
     * @param InstructionInterface ...$instructions
     * @return InternalRequest
     *
     * @todo Instruction handling should be part of Testing Framework (multiple instructions per identifier, merge in interface)
     */
    protected function applyInstructions(InternalRequest $request, InstructionInterface ...$instructions): InternalRequest
    {
        $modifiedInstructions = [];

        foreach ($instructions as $instruction) {
            $identifier = $instruction->getIdentifier();
            $useModifier = $modifiedInstructions[$identifier] ?? $request->getInstruction($identifier);
            if ($useModifier !== null) {
                $modifiedInstructions[$identifier] = $this->mergeInstruction($useModifier, $instruction);
            } else {
                $modifiedInstructions[$identifier] = $instruction;
            }
        }

        return $request->withInstructions($modifiedInstructions);
    }

    protected function mergeInstruction(InstructionInterface $current, InstructionInterface $other): InstructionInterface
    {
        if ($current::class !== $other::class) {
            throw new \LogicException('Cannot merge different instruction types', 1565863174);
        }

        if ($current instanceof TypoScriptInstruction) {
            /** @var TypoScriptInstruction $other */
            $typoScript = array_replace_recursive(
                $current->getTypoScript() ?? [],
                $other->getTypoScript() ?? []
            );
            $constants = array_replace_recursive(
                $current->getConstants() ?? [],
                $other->getConstants() ?? []
            );
            if ($typoScript !== []) {
                $current = $current->withTypoScript($typoScript);
            }
            if ($constants !== []) {
                $current = $current->withConstants($constants);
            }
            return $current;
        }

        if ($current instanceof ArrayValueInstruction) {
            /** @var ArrayValueInstruction $other */
            $array = array_merge_recursive($current->getArray(), $other->getArray());
            return $current->withArray($array);
        }

        return $current;
    }
}
