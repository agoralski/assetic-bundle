<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Bundle\AsseticBundle\Twig;

use Assetic\Asset\AssetInterface;
use Assetic\Extension\Twig\AsseticNode as BaseAsseticNode;

/**
 * Assetic node.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class AsseticNode extends BaseAsseticNode
{
    protected function compileAssetUrl(\Twig_Compiler $compiler, AssetInterface $asset, $name)
    {
        $vars = array();
        foreach ($asset->getVars() as $var) {
            $vars[] = new \Twig_Node_Expression_Constant($var, $this->lineno);

            // Retrieves values of assetic vars from the context, $context['assetic']['vars'][$var].
            $vars[] = new \Twig_Node_Expression_GetAttr(
                new \Twig_Node_Expression_GetAttr(
                    new \Twig_Node_Expression_Name('assetic', $this->lineno),
                    new \Twig_Node_Expression_Constant('vars', $this->lineno),
                    new \Twig_Node_Expression_Array(array(), $this->lineno),
                    \Twig_Template::ARRAY_CALL,
                    $this->lineno
                ),
                new \Twig_Node_Expression_Constant($var, $this->lineno),
                new \Twig_Node_Expression_Array(array(), $this->lineno),
                \Twig_Template::ARRAY_CALL,
                $this->lineno
            );
        }
        $compiler
            ->raw('isset($context[\'assetic\'][\'use_controller\']) && $context[\'assetic\'][\'use_controller\'] ? ')
            ->subcompile($this->getPathFunction($name, $vars))
            ->raw(' : ')
            ->subcompile($this->getAssetFunction(new TargetPathNode($this, $asset, $name)))
        ;
    }

    private function getPathFunction($name, array $vars = array())
    {

        $nodes = array(new \Twig_Node_Expression_Constant('_assetic_'.$name, $this->lineno));

        if (!empty($vars)) {
            $nodes[] = new \Twig_Node_Expression_Array($vars, $this->lineno);
        }

        return new \Twig_Node_Expression_Function(
            'path',
            new \Twig_Node($nodes),
            $this->lineno
        );
    }

    private function getAssetFunction($path)
    {
        $arguments = array($path);

        if ($this->hasAttribute('package')) {
            $arguments[] = new \Twig_Node_Expression_Constant($this->getAttribute('package'), $this->lineno);
        }

        return new \Twig_Node_Expression_Function(
            'asset',
            new \Twig_Node($arguments),
            $this->lineno
        );
    }
}

class TargetPathNode extends AsseticNode
{
    private $node;
    private $asset;
    private $name;

    public function __construct(AsseticNode $node, AssetInterface $asset, $name)
    {
        $this->node = $node;
        $this->asset = $asset;
        $this->name = $name;
    }

    public function compile(\Twig_Compiler $compiler)
    {
        BaseAsseticNode::compileAssetUrl($compiler, $this->asset, $this->name);
    }

    public function getLine()
    {
        return $this->node->getLine();
    }
}
