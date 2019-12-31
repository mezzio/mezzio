<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio;

use MezzioTest\AppFactoryTest;

function class_exists($classname)
{
    if (AppFactoryTest::$existingClasses === null) {
        return \class_exists($classname);
    }
    return in_array($classname, AppFactoryTest::$existingClasses, true);
}
