<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Application\Service;

use Silex\Application;
use Sprocketeer\Parser as SprocketeerParser;

class AssetService
{
    private $path;
    private $renderers;
    private $options;

    private $sprocketeer;



    public function __construct($path, $renderers, array $options)
    {
        $this->path      = $path;
        $this->renderers = $renderers;
        $this->options   = $options;
    }



    private function getSprocketeer()
    {
        if (null !== $this->sprocketeer) {
            return $this->sprocketeer;
        }

        $this->sprocketeer = new SprocketeerParser($this->path);

        return $this->sprocketeer;
    }



    private function generateTag($name, $type)
    {
        $manifest_parser = $this->getSprocketeer();

        $renderer = $this->renderers[$type];

        if ($this->options['debug']) {
            $files = $manifest_parser->getJsFiles($name);
            array_walk(
                $files,
                function (& $asset) {
                    $asset = ltrim(
                        str_replace(
                            $this->path->getArrayCopy(),
                            '',
                            $asset
                        ),
                        '/'
                    );
                }
            );

            $asset_list = array();
            foreach ($files as $asset) {
                $asset_list[] = $renderer("/app/asset/{$asset}");
            }

            $html = implode("\n", $asset_list);
        } else {
            $html = $renderer("/app/asset/{$name}");
        }

        return $html;
    }



    public function cssTag($name)
    {
        return $this->generateTag($name, 'css');
    }



    public function jsTag($name)
    {
        return $this->generateTag($name, 'js');
    }
}
