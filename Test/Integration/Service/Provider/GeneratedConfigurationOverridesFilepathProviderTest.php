<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service\Provider;

use Klevu\PlatformPipelines\Exception\Validator\InvalidFilepathException;
use Klevu\PlatformPipelines\Service\Provider\GeneratedConfigurationOverridesFilepathProvider;
use Klevu\PlatformPipelines\Service\Provider\GeneratedConfigurationOverridesFilepathProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * @covers \Klevu\PlatformPipelines\Service\Provider\GeneratedConfigurationOverridesFilepathProvider::class
 * @method GeneratedConfigurationOverridesFilepathProviderInterface instantiateTestObject(?array $arguments = null)
 * @method GeneratedConfigurationOverridesFilepathProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
// phpcs:enable Generic.Files.LineLength.TooLong
class GeneratedConfigurationOverridesFilepathProviderTest extends TestCase
{
    use TestImplementsInterfaceTrait;
    use ObjectInstantiationTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->implementationFqcn = GeneratedConfigurationOverridesFilepathProvider::class;
        $this->interfaceFqcn = GeneratedConfigurationOverridesFilepathProviderInterface::class;
    }

    /**
     * @testWith [null]
     *           [""]
     *           [" "]
     */
    public function testGet_ReturnsNull_WhenNoFilepathDefined(
        mixed $filepathInVar,
    ): void {
        $provider = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(),
            'appState' => $this->getMockAppState(
                AppState::MODE_PRODUCTION,
            ),
            'filepathValidator' => $this->getMockValidator(
                isValid: true,
                messages: [
                    'This should not return',
                ],
            ),
            'filepathInVar' => $filepathInVar,
        ]);

        $this->assertNull(
            actual: $provider->get(),
        );
    }

    public function testGet_ReturnsNull_WhenInvalidFilepathDefined_InProductionMode(): void
    {
        $provider = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(),
            'appState' => $this->getMockAppState(
                AppState::MODE_PRODUCTION,
            ),
            'filepathValidator' => $this->getMockValidator(
                isValid: false,
                messages: [
                    'Invalid filepath',
                ],
            ),
            'filepathInVar' => '/foo/bar',
        ]);

        $this->assertNull(
            actual: $provider->get(),
        );
    }

    public function testGet_ThrowsException_WhenInvalidFilepathDefined_InDeveloperMode(): void
    {
        $provider = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(),
            'appState' => $this->getMockAppState(
                AppState::MODE_DEVELOPER,
            ),
            'filepathValidator' => $this->getMockValidator(
                isValid: false,
                messages: [
                    'Invalid filepath',
                ],
            ),
            'filepathInVar' => '/foo/bar',
        ]);

        $this->expectException(InvalidFilepathException::class);
        $this->expectExceptionMessage('Invalid configuration overrides filepath defined (/foo/bar): Invalid filepath');

        $provider->get();
    }

    /**
     * @return string[][]
     */
    public static function dataProvider_testGet_ReturnsExpectedWhenValidFilepathDefined(): array
    {
        $directoryList = ObjectManager::getInstance()->get(DirectoryList::class);
        $varDir = $directoryList->getPath(AppDirectoryList::VAR_DIR);

        return [
            [
                'foo/bar.yml',
                $varDir . DIRECTORY_SEPARATOR . 'foo/bar.yml',
            ],
            [
                'foo/../bar.yml',
                $varDir . DIRECTORY_SEPARATOR . 'bar.yml',
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_testGet_ReturnsExpectedWhenValidFilepathDefined
     */
    public function testGet_ReturnsExpected_WhenValidFilepathDefined(
        string $filepathInVar,
        string $expectedResult,
    ): void {
        $provider = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(),
            'appState' => $this->getMockAppState(
                AppState::MODE_PRODUCTION,
            ),
            'filepathValidator' => $this->getMockValidator(
                isValid: true,
                messages: [],
            ),
            'filepathInVar' => $filepathInVar,
        ]);

        $this->assertSame(
            expected: $expectedResult,
            actual: $provider->get(),
        );
    }

    /**
     * @param string[] $expectedLogLevels
     *
     * @return MockObject&LoggerInterface
     */
    private function getMockLogger(array $expectedLogLevels = []): MockObject
    {
        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notExpectedLogLevels = array_diff(
            $expectedLogLevels,
            [
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
                'notice',
                'info',
                'debug',
            ],
        );
        foreach ($notExpectedLogLevels as $notExpectedLogLevel) {
            $mockLogger->expects($this->never())
                ->method($notExpectedLogLevel);
        }

        return $mockLogger;
    }

    private function getMockAppState(
        string $mode,
    ): MockObject {
        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAppState->method('getMode')
            ->willReturn($mode);

        return $mockAppState;
    }

    /**
     * @param bool $isValid
     * @param string[] $messages
     *
     * @return MockObject&ValidatorInterface
     */
    private function getMockValidator(
        bool $isValid,
        array $messages = [],
    ): MockObject {
        $mockValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockValidator->method('isValid')->willReturn($isValid);
        $mockValidator->method('getMessages')->willReturn($messages);

        return $mockValidator;
    }
}
