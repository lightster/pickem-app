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
    private $app;
    private $path;

    private $sprocketeer;



    public function __construct(Application $app)
    {
        $this->app     = $app;
        $this->path    = $app['lidsys.asset.path'];
    }



    private function getSprocketeer()
    {
        if (null !== $this->sprocketeer) {
            return $this->sprocketeer;
        }

        $this->sprocketeer = new SprocketeerParser($this->path);

        return $this->sprocketeer;
    }



    public function jsTag($name)
    {
        $app = $this->app;

        $manifest_parser = $this->getSprocketeer();

        $js_renderer = $app['lidsys.asset.renderer']['js'];

        if ($app['debug']) {
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
