<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Validator;

use Klevu\PlatformPipelines\Validator\GeneratedConfigurationOverridesFilepathValidator;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\PlatformPipelines\Validator\GeneratedConfigurationOverridesFilepathValidator::class
 * @method ValidatorInterface instantiateTestObject(?array $arguments = null)
 * @method ValidatorInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class GeneratedConfigurationOverridesFilepathValidatorTest extends TestCase
{
    use TestImplementsInterfaceTrait;
    use ObjectInstantiationTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var DirectoryList|null
     */
    private ?DirectoryList $directoryList = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->implementationFqcn = GeneratedConfigurationOverridesFilepathValidator::class;
        $this->interfaceFqcn = ValidatorInterface::class;

        $this->directoryList = $this->objectManager->get(DirectoryList::class);
    }

    public function testIsValid_ReturnsTrueOnNull(): void
    {
        $validator = $this->instantiateTestObject();

        $isValidResult = $validator->isValid(null);
        $this->assertTrue(
            condition: $isValidResult,
        );
        $this->assertEmpty(
            actual: $validator->getMessages(),
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testIsValid_ReturnsFalse_IfNotString(): array
    {
        $fileHandle = fopen(__FILE__, 'r');
        fclose($fileHandle);

        return [
            [42],
            [3.14],
            [false],
            [
                ['foo'],
            ],
            [
                (object)['foo'],
            ],
            [$fileHandle],
        ];
    }

    /**
     * @dataProvider dataProvider_testIsValid_ReturnsFalse_IfNotString
     *
     * @param mixed $value
     *
     * @return void
     */
    public function testIsValid_ReturnsFalse_IfNotString(
        mixed $value,
    ): void {
        $validator = $this->instantiateTestObject();

        $isValidResult = $validator->isValid($value);
        $this->assertFalse(
            condition: $isValidResult,
        );
        $this->assertSame(
            expected: [
                'Filepath must be of type string, received ' . get_debug_type($value),
            ],
            actual: $validator->getMessages(),
        );
    }

    public function testIsValid_ReturnsFalse_FileOutsideVarDirectory(): void
    {
        $validator = $this->instantiateTestObject();

        $isValidResult = $validator->isValid(__FILE__);
        $this->assertFalse(
            condition: $isValidResult,
        );
        $this->assertSame(
            expected: [
                'Generated configuration filepath must live in var directory; received ' . __FILE__,
            ],
            actual: $validator->getMessages(),
        );
    }

    public function testIsValid_ReturnsTrue_OnValidValue(): void
    {
        $validator = $this->instantiateTestObject();

        $isValidResult = $validator->isValid(
            value: $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
                . DIRECTORY_SEPARATOR
                . 'foo.yml',
        );
        $this->assertTrue(
            condition: $isValidResult, // Note, we're checking the pattern, _not_ the existence of this file
        );
        $this->assertSame(
            expected: [],
            actual: $validator->getMessages(),
        );
    }
}
