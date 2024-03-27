<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\ObjectManager;

use Klevu\PhpSDKPipelines\ObjectManager\TransformerManager as BaseTransformerManager;

class TransformerManager extends BaseTransformerManager
{
    // Implemented as concrete class to allow Magento's DI to recognise constructor args
}
