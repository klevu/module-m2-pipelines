<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service\Action;

use Klevu\PlatformPipelines\Service\Action\ParseFilepath;
use Klevu\PlatformPipelines\Service\Action\ParseFilepathActionInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\PlatformPipelines\Service\Action\ParseFilepath::class
 * @method ParseFilepath instantiateTestObject(?array $arguments = null)
 */
class ParseFilepathTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

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

        $this->implementationFqcn = ParseFilepath::class;
        $this->interfaceFqcn = ParseFilepathActionInterface::class;

        $this->objectManager = ObjectManager::getInstance();
    }

    public function testInterfaceGeneration(): void
    {
        $this->assertInstanceOf(
            ParseFilepath::class,
            $this->objectManager->get(ParseFilepathActionInterface::class),
        );
    }

    public function testExecute_ModuleFilepath_NotExists(): void
    {
        $parseFilepathAction = $this->instantiateTestObject();

        $filepath = 'Klevu_PlatformPipelines::etc/foo.xml';

        $this->expectException(NotFoundException::class);
        $parseFilepathAction->execute($filepath);
    }

    public function testExecute_ModuleFilepath_Exists(): void
    {
        $parseFilepathAction = $this->instantiateTestObject();

        $filepath = 'Klevu_PlatformPipelines::etc/module.xml';
        $expectedResult = realpath(__DIR__ . '/../../../../etc/module.xml');

        $this->assertSame(
            $expectedResult,
            $parseFilepathAction->execute($filepath),
        );
    }

    public function testExecute_RelativeFilepath_NotExists(): void
    {
        $parseFilepathAction = $this->instantiateTestObject();

        $filepath = 'app/etc/foo.xml';

        $this->expectException(NotFoundException::class);
        $parseFilepathAction->execute($filepath);
    }

    public function testExecute_RelativeFilepath_Exists(): void
    {
        $parseFilepathAction = $this->instantiateTestObject();

        $filepath = 'app/etc/env.php';

        /** @var DirectoryList $directoryList */
        $directoryList = $this->objectManager->get(DirectoryList::class);
        $expectedResult = $directoryList->getRoot() . '/app/etc/env.php';

        $this->assertSame(
            $expectedResult,
            $parseFilepathAction->execute($filepath),
        );
    }

    public function testExecute_AbsoluteFilepath_NotExists(): void
    {
        $parseFilepathAction = $this->instantiateTestObject();

        $filepath = '/foo/bar';

        $this->expectException(NotFoundException::class);
        $parseFilepathAction->execute($filepath);
    }

    public function testExecute_AbsoluteFilepath_Exists(): void
    {
        $parseFilepathAction = $this->instantiateTestObject();

        $filepath = __DIR__ . '/../../../../etc/module.xml';
        $expectedResult = realpath($filepath);

        $this->assertSame(
            $expectedResult,
            $parseFilepathAction->execute($expectedResult),
        );
    }
}
