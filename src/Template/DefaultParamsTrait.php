<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Template;

use Laminas\Stdlib\ArrayUtils;
use Mezzio\Exception;
use Traversable;

trait DefaultParamsTrait
{
    /**
     * @var array
     */
    private $defaultParams = [];

    /**
     * Add a default parameter to use with a template.
     *
     * Use this method to provide a default parameter to use when a template is
     * rendered. The parameter may be overridden by providing it when calling
     * `render()`, or by calling this method again with a null value.
     *
     * The parameter will be specific to the template name provided. To make
     * the parameter available to any template, pass the TEMPLATE_ALL constant
     * for the template name.
     *
     * If the default parameter existed previously, subsequent invocations with
     * the same template name and parameter name will overwrite.
     *
     * @param string $templateName Name of template to which the param applies;
     *     use TEMPLATE_ALL to apply to all templates.
     * @param string $param Param name.
     * @param mixed $value
     */
    public function addDefaultParam($templateName, $param, $value)
    {
        if (! is_string($templateName) || empty($templateName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$templateName must be a non-empty string; received %s',
                (is_object($templateName) ? get_class($templateName) : gettype($templateName))
            ));
        }

        if (! is_string($param) || empty($param)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '$param must be a non-empty string; received %s',
                (is_object($param) ? get_class($param) : gettype($param))
            ));
        }

        if (! isset($this->defaultParams[$templateName])) {
            $this->defaultParams[$templateName] = [];
        }

        $this->defaultParams[$templateName][$param] = $value;
    }

    /**
     * Returns merged global, template-specific and given params
     *
     * @param string $template
     * @param array $params
     * @return array
     */
    private function mergeParams($template, array $params)
    {
        $globalDefaults = isset($this->defaultParams[TemplateRendererInterface::TEMPLATE_ALL])
            ? $this->defaultParams[TemplateRendererInterface::TEMPLATE_ALL]
            : [];

        $templateDefaults = isset($this->defaultParams[$template])
            ? $this->defaultParams[$template]
            : [];

        $defaults = ArrayUtils::merge($globalDefaults, $templateDefaults);

        return ArrayUtils::merge($defaults, $params);
    }
}
