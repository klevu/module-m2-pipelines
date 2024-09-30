<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Console\Command;

use Klevu\Pipelines\Pipeline\ConfigurationBuilder;
use Klevu\PlatformPipelines\Api\ConfigurationOverridesHandlerInterface;
use Klevu\PlatformPipelines\Api\PipelineConfigurationProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationDebugCommand extends Command
{
    public const COMMAND_NAME = 'klevu:pipelines:configuration-debug';
    public const ARGUMENT_ACTION = 'action';
    public const ARGUMENT_PIPELINE_IDENTIFIER = 'pipeline_identifier';
    public const ACTION_CONFIG = 'config';
    public const ACTION_LIST_FILES = 'list-files';
    public const ACTION_COMPILE = 'compile';

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var PipelineConfigurationProviderInterface
     */
    private readonly PipelineConfigurationProviderInterface $pipelineConfigurationProvider;
    /**
     * @var ConfigurationBuilder
     */
    private readonly ConfigurationBuilder $configurationBuilder;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param PipelineConfigurationProviderInterface $pipelineConfigurationProvider
     * @param ConfigurationBuilder $configurationBuilder
     * @param string|null $name
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PipelineConfigurationProviderInterface $pipelineConfigurationProvider,
        ConfigurationBuilder $configurationBuilder,
        ?string $name = null,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->pipelineConfigurationProvider = $pipelineConfigurationProvider;
        $this->configurationBuilder = $configurationBuilder;

        parent::__construct($name);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName(static::COMMAND_NAME);
        $this->addArgument(
            name: static::ARGUMENT_ACTION,
            mode: InputArgument::REQUIRED,
            description: __('Available actions:')->render() . PHP_EOL .
                __(
                    '%1 : Show related configuration settings',
                    static::ACTION_CONFIG,
                )->render() . PHP_EOL .
                __(
                    '%1 : List files configuration files for all registered pipelines',
                    static::ACTION_LIST_FILES,
                )->render() . PHP_EOL .
                __(
                    '%1 : Compile pipeline configuration for a specified pipeline identifier and output',
                    static::ACTION_COMPILE,
                )->render(),
        );
        $this->addArgument(
            name: static::ARGUMENT_PIPELINE_IDENTIFIER,
            mode: InputArgument::OPTIONAL,
            description: __(
                'Pipeline Identifier. Required for compiling configuration (%1)',
                static::ACTION_COMPILE,
            )->render(),
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        return match ($input->getArgument(static::ARGUMENT_ACTION)) {
            static::ACTION_CONFIG => $this->executeConfig(
                input: $input,
                output: $output,
            ),
            static::ACTION_LIST_FILES => $this->executeList(
                input: $input,
                output: $output,
            ),
            static::ACTION_COMPILE => $this->executeCompile(
                input: $input,
                output: $output,
            ),
            default => $this->executeDefault(
                input: $input,
                output: $output,
            ),
        };
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function executeConfig(
        InputInterface $input, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        OutputInterface $output,
    ): int {
        $configPathsAndDescriptions = [
            ConfigurationOverridesHandlerInterface::XML_PATH_CONFIGURATION_OVERRIDES_GENERATION_ENABLED => __(
                'Whether automatic generation of pipeline configuration overrides files is enabled; '
                . 'for example, to generate definitions for additional attributes used in indexing',
            ),
        ];

        $output->writeln(
            __('Displaying pipelines related configuration settings')->render(),
        );
        $output->writeln('');

        $configPathMaxLength = max(
            array_map('strlen', array_keys($configPathsAndDescriptions)),
        );
        foreach ($configPathsAndDescriptions as $configPath => $description) {
            $output->writeln(
                messages: sprintf(
                    '<info>%s</info> : <comment>%s</comment>',
                    str_pad(
                        string: $configPath,
                        length: $configPathMaxLength + 4,
                        pad_string: ' ',
                        pad_type: STR_PAD_LEFT,
                    ),
                    $this->scopeConfig->getValue(
                        $configPath,
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0,
                    ),
                ),
            );
            if ($description) {
                $output->writeln(
                    '    ' . $description->render(),
                );
                $output->writeln('');
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function executeList(
        InputInterface $input, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        OutputInterface $output,
    ): int {
        $output->writeln(
            __('Listing files configuration registered for all klevu/pipelines in the application')->render(),
        );
        $output->writeln(
            messages: sprintf(
                '<comment>%s</comment>',
                __(
                    'Note: if you have overridden or implemented pipeline services which do not utilise the '
                    . 'PipelineConfigurationProvider service to register configuration filepaths, these results '
                    . 'may not be accurate',
                )->render(),
            ),
        );

        foreach ($this->pipelineConfigurationProvider->getConfiguredIdentifiers() as $pipelineIdentifier) {
            $output->writeln(
                messages: sprintf(
                    '<info>%s</info>',
                    $pipelineIdentifier,
                ),
            );

            $output->write('* Filepath           : ');
            try {
                $output->writeln(
                    $this->pipelineConfigurationProvider->getPipelineConfigurationFilepathByIdentifier(
                        identifier: $pipelineIdentifier,
                    ),
                );
            } catch (LocalizedException $exception) {
                $output->writeln(
                    messages: sprintf(
                        '<error>%s</error>',
                        $exception->getMessage(),
                    ),
                );
            }

            $output->writeln(
                messages: sprintf(
                    '* Override Filepaths : %s',
                    implode(
                        separator: '; ',
                        array: $this->pipelineConfigurationProvider->getPipelineConfigurationOverridesFilepathsByIdentifier( // phpcs:ignore Generic.Files.LineLength.TooLong
                            identifier: $pipelineIdentifier,
                        ),
                    ),
                ),
            );
            $output->writeln('');
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function executeCompile(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $pipelineIdentifier = $input->getArgument(static::ARGUMENT_PIPELINE_IDENTIFIER);
        if (!$pipelineIdentifier) {
            $output->writeln(
                messages: sprintf(
                    '<error>%s</error>',
                    __('Please provide a pipeline identifier to compile')->render(),
                ),
            );

            return Cli::RETURN_FAILURE;
        }

        try {
            $pipelineConfigurationFilepath = $this->pipelineConfigurationProvider
                ->getPipelineConfigurationFilepathByIdentifier($pipelineIdentifier);
        } catch (NotFoundException) {
            $output->writeln(
                messages: sprintf(
                    '<error>%s</error>',
                    __(
                        'Pipeline for identifier %1 is not registered with configuration provider',
                        $pipelineIdentifier,
                    )->render(),
                ),
            );

            return Cli::RETURN_FAILURE;
        }

        $pipelineConfigurationOverridesFilepaths = $this->pipelineConfigurationProvider
            ->getPipelineConfigurationOverridesFilepathsByIdentifier($pipelineIdentifier);

        $configuration = $this->configurationBuilder->buildFromFiles(
            pipelineDefinitionFile: $pipelineConfigurationFilepath,
            pipelineOverridesFiles: $pipelineConfigurationOverridesFilepaths,
        );

        $output->writeln(
            // Need to suppress errors so that potential deprecated warnings related to conversion of int to string
            //  do not throw exception in Magento
            @Yaml::dump( // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                input: $configuration,
                inline: 100,
                indent: 2,
            ),
        );

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function executeDefault(
        InputInterface $input, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        OutputInterface $output,
    ): int {
        $output->writeln(
            $this->getHelp(),
        );

        return Cli::RETURN_SUCCESS;
    }
}
