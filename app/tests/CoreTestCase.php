<?php

declare(strict_types=1);

/*
 * UserFrosting Core Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-core
 * @copyright Copyright (c) 2021 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-core/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\Core\Tests;

use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Testing\TestCase;

/**
 * Test case with Core as main sprinkle
 */
class CoreTestCase extends TestCase
{
    protected string $mainSprinkle = Core::class;
}
