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
use Mezzio\Template\LaminasViewRenderer;
use PHPUnit_Framework_TestCase as TestCase;

class LaminasViewRendererTest extends TestCase
{
    /** @var  TemplatePathStack */
    private $resolver;
    /** @var  PhpRenderer */
    private $render;

    use TemplatePathAssertionsTrait;

    public function setUp()
    {
        $this->resolver = new TemplatePathStack;
        $this->render = new PhpRenderer;
        $this->render->setResolver($this->resolver);
    }

    public function testCanPassRendererToConstructor()
    {
        $renderer = new LaminasViewRenderer($this->render);
        $this->assertInstanceOf(LaminasViewRenderer::class, $renderer);
        $this->assertAttributeSame($this->render, 'renderer', $renderer);
    }

    public function testInstantiatingWithoutEngineLazyLoadsOne()
    {
        $renderer = new LaminasViewRenderer();
        $this->assertInstanceOf(LaminasViewRenderer::class, $renderer);
        $this->assertAttributeInstanceOf(PhpRenderer::class, 'renderer', $renderer);
    }

    public function testInstantiatingWithInvalidLayout()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        new LaminasViewRenderer(null, []);
    }

    public function testCanAddPathWithEmptyNamespace()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertEquals(1, count($paths));
        $this->assertTemplatePath(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertEmptyTemplatePathNamespace($paths[0]);
    }

    public function testCanAddPathWithNamespace()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset', 'test');
        $paths = $renderer->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertEquals(1, count($paths));
        $this->assertTemplatePath(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertTemplatePathString(__DIR__ . '/TestAsset/', $paths[0]);
        $this->assertTemplatePathNamespace('test', $paths[0]);
    }

    public function testDelegatesRenderingToUnderlyingImplementation()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'laminasview';
        $result = $renderer->render('laminasview', [ 'name' => $name ]);
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
        $renderer = new LaminasViewRenderer();
        $this->setExpectedException(InvalidArgumentException::class);
        $renderer->render('foo', $params);
    }

    public function testCanRenderWithNullParams()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result = $renderer->render('laminasview-null', null);
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
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $result = $renderer->render('laminasview', $params);
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
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'laminasview';
        $result = $renderer->render('laminasview', [ 'name' => $name ]);
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
        $renderer = new LaminasViewRenderer(null);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'laminasview';
        $result = $renderer->render('laminasview', [ 'name' => $name, 'layout' => 'laminasview-layout' ]);
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
        $renderer = new LaminasViewRenderer(null, 'laminasview-layout');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'laminasview';
        $result = $renderer->render('laminasview', [ 'name' => $name, 'layout' => 'laminasview-layout2' ]);
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

        $renderer = new LaminasViewRenderer(null, $layout);
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'laminasview';
        $result = $renderer->render('laminasview', [ 'name' => $name ]);
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

        $renderer = new LaminasViewRenderer(null, 'laminasview-layout');
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'laminasview';
        $result = $renderer->render('laminasview', [ 'name' => $name, 'layout' => $layout ]);
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
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset/test', 'test');

        $expected = file_get_contents(__DIR__ . '/TestAsset/test/test.phtml');
        $test     = $renderer->render('test::test');

        $this->assertSame($expected, $test);
    }

    public function testAddParameterToOneTemplate()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $renderer->addDefaultParam('laminasview', 'name', $name);
        $result = $renderer->render('laminasview');

        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testAddSharedParameters()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'LaminasView';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result = $renderer->render('laminasview');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);

        $result = $renderer->render('laminasview-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-2.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersPerTemplate()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'Laminas';
        $name2 = 'View';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $renderer->addDefaultParam('laminasview-2', 'name', $name2);
        $result = $renderer->render('laminasview');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name, $content);
        $this->assertEquals($content, $result);

        $result = $renderer->render('laminasview-2');
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview-2.phtml');
        $content = str_replace('<?php echo $name ?>', $name2, $content);
        $this->assertEquals($content, $result);
    }

    public function testOverrideSharedParametersAtRender()
    {
        $renderer = new LaminasViewRenderer();
        $renderer->addPath(__DIR__ . '/TestAsset');
        $name = 'Laminas';
        $name2 = 'View';
        $renderer->addDefaultParam($renderer::TEMPLATE_ALL, 'name', $name);
        $result = $renderer->render('laminasview', ['name' => $name2]);
        $content = file_get_contents(__DIR__ . '/TestAsset/laminasview.phtml');
        $content = str_replace('<?php echo $name ?>', $name2, $content);
        $this->assertEquals($content, $result);
    }
}
