<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Template;

use ArrayObject;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use Mezzio\Exception;
use Mezzio\Exception\InvalidArgumentException;
use Mezzio\Template\LaminasView;
use PHPUnit_Framework_TestCase as TestCase;

class LaminasViewTest extends TestCase
{
    use TemplatePathAssertionsTrait;

    public function setUp()
    {
        $this->resolver = new TemplatePathStack;
        $this->render = new PhpRenderer;
        $this->render->setResolver($this->resolver);
    }

    public function testCanPassRendererToConstructor()
    {
        $template = new LaminasView($this->render);
        $this->assertInstanceOf(LaminasView::class, $template);
        $this->assertAttributeSame($this->render, 'renderer', $template);
    }

    public function testInstantiatingWithoutEngineLazyLoadsOne()
    {
        $template = new LaminasView();
        $this->assertInstanceOf(LaminasView::class, $template);
        $this->assertAttributeInstanceOf(PhpRenderer::class, 'renderer', $template);
    }

    public function testInstantiatingWithInvalidLayout()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        new LaminasView(null, []);
    }

    public function testCanAddPathWithEmptyNamespace()
    {
        $template = new LaminasView();
        $template->addPath(__DIR__ . '/TestAsset');
        $paths = $template->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertEquals(1, count($paths));
        $this->assertTemplatePath(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);
    }

    public function testCanAddPathWithNamespace()
    {
        $template = new LaminasView();
        $template->addPath(__DIR__ . '/TestAsset', 'test');
        $paths = $template->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertEquals(1, count($paths));
        $this->assertTemplatePath(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation()
    {
        $template = new LaminasView();
        $template->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $result = $template->render('laminasview', [ 'name' => $name ]);
        $this->assertContains($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function invalidParameterValues()
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['value'],
        ];
    }

    /**
     * @dataProvider invalidParameterValues
     */
    public function testRenderRaisesExceptionForInvalidParameterTypes($params)
    {
        $template = new LaminasView();
        $this->setExpectedException(InvalidArgumentException::class);
        $template->render('foo', $params);
    }

    public function testCanRenderWithNullParams()
    {
        $template = new LaminasView();
        $template->addPath(__DIR__ . '/TestAsset');
        $result = $template->render('laminasview-null', null);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-null.phtml');
        $this->assertEquals($content, $result);
    }

    public function objectParameterValues()
    {
        $names = [
            'stdClass'    => uniqid(),
            'ArrayObject' => uniqid(),
        ];

        return [
            'stdClass'    => [(object) ['name' => $names['stdClass']], $names['stdClass']],
            'ArrayObject' => [new ArrayObject(['name' => $names['ArrayObject']]), $names['ArrayObject']],
        ];
    }

    /**
     * @dataProvider objectParameterValues
     */
    public function testCanRenderWithParameterObjects($params, $search)
    {
        $template = new LaminasView();
        $template->addPath(__DIR__ . '/TestAsset');
        $result = $template->render('laminasview', $params);
        $this->assertContains($search, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $search, $content);
        $this->assertEquals($content, $result);
    }

    /**
     * @group layout
     */
    public function testWillRenderContentInLayoutPassedToConstructor()
    {
        $template = new LaminasView(null, 'laminasview-layout');
        $template->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $result = $template->render('laminasview', [ 'name' => $name ]);
        $this->assertContains($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertContains($content, $result);
        $this->assertContains('<title>Layout Page</title>', $result, sprintf("Received %s", $result));
    }

    /**
     * @group layout
     */
    public function testWillRenderContentInLayoutPassedDuringRendering()
    {
        $template = new LaminasView(null);
        $template->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $result = $template->render('laminasview', [ 'name' => $name, 'layout' => 'laminasview-layout' ]);
        $this->assertContains($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertContains($content, $result);

        $this->assertContains('<title>Layout Page</title>', $result);
    }

    /**
     * @group layout
     */
    public function testLayoutPassedWhenRenderingOverridesLayoutPassedToConstructor()
    {
        $template = new LaminasView(null, 'laminasview-layout');
        $template->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $result = $template->render('laminasview', [ 'name' => $name, 'layout' => 'laminasview-layout2' ]);
        $this->assertContains($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertContains($content, $result);

        $this->assertContains('<title>ALTERNATE LAYOUT PAGE</title>', $result);
    }

    /**
     * @group layout
     */
    public function testCanPassViewModelForLayoutToConstructor()
    {
        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout');

        $template = new LaminasView(null, $layout);
        $template->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $result = $template->render('laminasview', [ 'name' => $name ]);
        $this->assertContains($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertContains($content, $result);
        $this->assertContains('<title>Layout Page</title>', $result, sprintf("Received %s", $result));
    }

    /**
     * @group layout
     */
    public function testCanPassViewModelForLayoutParameterWhenRendering()
    {
        $layout = new ViewModel();
        $layout->setTemplate('laminasview-layout2');

        $template = new LaminasView(null, 'laminasview-layout');
        $template->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $result = $template->render('laminasview', [ 'name' => $name, 'layout' => $layout ]);
        $this->assertContains($name, $result);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertContains($content, $result);
        $this->assertContains('<title>ALTERNATE LAYOUT PAGE</title>', $result);
    }

    /**
     * @group namespacing
     */
    public function testProperlyResolvesNamespacedTemplate()
    {
        $template = new LaminasView();
        $template->addPath(__DIR__ . '/TestAsset/test', 'test');

        $expected = file_get_contents(__DIR__ . '/TestAsset/test/test.phtml');
        $test     = $template->render('test::test');

        $this->assertSame($expected, $test);
    }
}
