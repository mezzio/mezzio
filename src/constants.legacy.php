<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive;

use const Mezzio\DEFAULT_DELEGATE;
use const Mezzio\DISPATCH_MIDDLEWARE;
use const Mezzio\IMPLICIT_HEAD_MIDDLEWARE;
use const Mezzio\IMPLICIT_OPTIONS_MIDDLEWARE;
use const Mezzio\NOT_FOUND_MIDDLEWARE;
use const Mezzio\ROUTE_MIDDLEWARE;
use const Mezzio\SERVER_REQUEST_FACTORY;

/**
 * @deprecated Please use Mezzio\DEFAULT_DELEGATE instead
 */
const DEFAULT_DELEGATE = DEFAULT_DELEGATE;

/**
 * @deprecated Please use Mezzio\DISPATCH_MIDDLEWARE instead
 */
const DISPATCH_MIDDLEWARE = DISPATCH_MIDDLEWARE;

/**
 * @deprecated Please use Mezzio\IMPLICIT_HEAD_MIDDLEWARE instead
 */
const IMPLICIT_HEAD_MIDDLEWARE = IMPLICIT_HEAD_MIDDLEWARE;

/**
 * @deprecated Please use Mezzio\IMPLICIT_OPTIONS_MIDDLEWARE instead
 */
const IMPLICIT_OPTIONS_MIDDLEWARE = IMPLICIT_OPTIONS_MIDDLEWARE;

/**
 * @deprecated Please use Mezzio\NOT_FOUND_MIDDLEWARE instead
 */
const NOT_FOUND_MIDDLEWARE = NOT_FOUND_MIDDLEWARE;

/**
 * @deprecated Please use Mezzio\ROUTE_MIDDLEWARE instead
 */
const ROUTE_MIDDLEWARE = ROUTE_MIDDLEWARE;

/**
 * @deprecated Please use Mezzio\SERVER_REQUEST_FACTORY instead
 */
const SERVER_REQUEST_FACTORY = SERVER_REQUEST_FACTORY;
