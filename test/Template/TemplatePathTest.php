<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Template;

use Mezzio\Template\TemplatePath;
use PHPUnit_Framework_TestCase as TestCase;

class TemplatePathTest extends TestCase
{
    use TemplatePathAssertionsTrait;

    public function testCanProvideNamespaceAtInstantiation()
    {
        $templatePath = new TemplatePath('/tmp', 'test');
        $this->assertTemplatePath('/tmp', $templatePath);
        $this->assertTemplatePathNamespace('test', $templatePath);
    }

    public function testCanInstantiateWithoutANamespace()
    {
        $templatePath = new TemplatePath('/tmp');
        $this->assertTemplatePath('/tmp', $templatePath);
        $this->assertEmptyTemplatePathNamespace($templatePath);
    }

    public function testCastingToStringReturnsPathOnly()
    {
        $templatePath = new TemplatePath('/tmp');
        $this->assertTemplatePathString('/tmp', $templatePath);

        $templatePath = new TemplatePath('/tmp', 'test');
        $this->assertTemplatePathString('/tmp', $templatePath);
    }
}
