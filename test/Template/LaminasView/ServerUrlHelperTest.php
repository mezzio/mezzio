<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Template\LaminasView;

use Laminas\Diactoros\Uri;
use Mezzio\Template\LaminasView\ServerUrlHelper;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\UriInterface;

class ServerUrlHelperTest extends TestCase
{
    public function plainPaths()
    {
        // @codingStandardsIgnoreStart
        return [
            'null'          => [null,       '/'],
            'empty'         => ['',         '/'],
            'root'          => ['/',        '/'],
            'relative-path' => ['foo/bar',  '/foo/bar'],
            'abs-path'      => ['/foo/bar', '/foo/bar'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider plainPaths
     */
    public function testInvocationReturnsPathOnlyIfNoUriInjected($path, $expected)
    {
        $helper = new ServerUrlHelper();
        $this->assertEquals($expected, $helper($path));
    }

    public function plainPathsForUseWithUri()
    {
        $uri = new Uri('https://example.com/resource');
        // @codingStandardsIgnoreStart
        return [
            'null'          => [$uri, null,       'https://example.com/resource'],
            'empty'         => [$uri, '',         'https://example.com/resource'],
            'root'          => [$uri, '/',        'https://example.com/'],
            'relative-path' => [$uri, 'foo/bar',  'https://example.com/resource/foo/bar'],
            'abs-path'      => [$uri, '/foo/bar', 'https://example.com/foo/bar'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider plainPathsForUseWithUri
     */
    public function testInvocationReturnsUriComposingPathWhenUriInjected(UriInterface $uri, $path, $expected)
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals((string) $expected, $helper($path));
    }

    public function uriWithQueryString()
    {
        $uri = new Uri('https://example.com/resource?bar=baz');
        // @codingStandardsIgnoreStart
        return [
            'null'          => [$uri, null,       'https://example.com/resource'],
            'empty'         => [$uri, '',         'https://example.com/resource'],
            'root'          => [$uri, '/',        'https://example.com/'],
            'relative-path' => [$uri, 'foo/bar',  'https://example.com/resource/foo/bar'],
            'abs-path'      => [$uri, '/foo/bar', 'https://example.com/foo/bar'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider uriWithQueryString
     */
    public function testStripsQueryStringFromInjectedUri(UriInterface $uri, $path, $expected)
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    public function uriWithFragment()
    {
        $uri = new Uri('https://example.com/resource#bar');
        // @codingStandardsIgnoreStart
        return [
            'null'          => [$uri, null,       'https://example.com/resource'],
            'empty'         => [$uri, '',         'https://example.com/resource'],
            'root'          => [$uri, '/',        'https://example.com/'],
            'relative-path' => [$uri, 'foo/bar',  'https://example.com/resource/foo/bar'],
            'abs-path'      => [$uri, '/foo/bar', 'https://example.com/foo/bar'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider uriWithFragment
     */
    public function testStripsFragmentFromInjectedUri(UriInterface $uri, $path, $expected)
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    public function pathsWithQueryString()
    {
        $uri = new Uri('https://example.com/resource');
        // @codingStandardsIgnoreStart
        return [
            'empty-path'    => [$uri, '?foo=bar',         'https://example.com/resource?foo=bar'],
            'root-path'     => [$uri, '/?foo=bar',        'https://example.com/?foo=bar'],
            'relative-path' => [$uri, 'foo/bar?foo=bar',  'https://example.com/resource/foo/bar?foo=bar'],
            'abs-path'      => [$uri, '/foo/bar?foo=bar', 'https://example.com/foo/bar?foo=bar'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider pathsWithQueryString
     */
    public function testUsesQueryStringFromProvidedPath(UriInterface $uri, $path, $expected)
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }

    public function pathsWithFragment()
    {
        $uri = new Uri('https://example.com/resource');
        // @codingStandardsIgnoreStart
        return [
            'empty-path'    => [$uri, '#bar',         'https://example.com/resource#bar'],
            'root-path'     => [$uri, '/#bar',        'https://example.com/#bar'],
            'relative-path' => [$uri, 'foo/bar#bar',  'https://example.com/resource/foo/bar#bar'],
            'abs-path'      => [$uri, '/foo/bar#bar', 'https://example.com/foo/bar#bar'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider pathsWithFragment
     */
    public function testUsesFragmentFromProvidedPath(UriInterface $uri, $path, $expected)
    {
        $helper = new ServerUrlHelper();
        $helper->setUri($uri);
        $this->assertEquals($expected, $helper($path));
    }
}
