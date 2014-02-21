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



    public function cssTag($name)
    {
        $manifest_parser = $this->getSprocketeer();

        $js_renderer = $this->renderers['css'];

        if ($this->options['debug']) {
            $js_files = $manifest_parser->getJsFiles($name);
            array_walk(
                $js_files,
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
            foreach ($js_files as $asset) {
                $asset_list[] = $js_renderer("/app/asset/css/{$asset}");
            }

            $html = implode("\n", $asset_list);
        } else {
            $html = $js_renderer("/app/asset/css/{$name}");
        }

        return $html;
    }



    public function jsTag($name)
    {
        $manifest_parser = $this->getSprocketeer();

        $js_renderer = $this->renderers['js'];

        if ($this->options['debug']) {
            $js_files = $manifest_parser->getJsFiles($name);
            array_walk(
                $js_files,
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
            foreach ($js_files as $asset) {
                $asset_list[] = $js_renderer("/app/asset/js/{$asset}");
            }

            $html = implode("\n", $asset_list);
        } else {
            $html = $js_renderer("/app/asset/js/{$name}");
        }

        return $html;
    }
}
