<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\ObjectManager;

use Klevu\PhpSDKPipelines\ObjectManager\PipelineFqcnProvider as BasePipelineFqcnProvider;

class PipelineFqcnProvider extends BasePipelineFqcnProvider
{
    // Implemented as concrete class to allow Magento's DI to recognise constructor args
}
