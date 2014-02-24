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

use ArrayObject;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CoffeeScriptFilter;
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\UglifyCssFilter;
use Assetic\Filter\UglifyJs2Filter;
use Assetic\FilterManager;
use Silex\Application;
use Sprocketeer\Parser as SprocketeerParser;
use Symfony\Component\HttpFoundation\Response;

class AssetService
{
    private $path;
    private $renderers;
    private $options;

    private $sprocketeer;



    public function __construct($path, $renderers, array $options)
    {
        if ($path instanceof ArrayObject) {
            $path = $path->getArrayCopy();
        }

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



    public function getAssetResponse($name)
    {
        $manifest_parser = $this->getSprocketeer();

        if ($this->options['debug']) {
            $assets = array(
                $name,
            );
        } else {
            $assets = $manifest_parser->getJsFiles($name);
            array_walk(
                $assets,
                function (& $asset) {
                    $asset = str_replace(
                        $this->path,
                        '',
                        $asset
                    );
                }
            );
        }

        $binaries = $this->options['assetrinc.binaries'];

        $filters = array(
            'coffee' => new CoffeeScriptFilter($binaries['coffee']),
            'uglifyJs' => new UglifyJs2Filter($binaries['uglifyJs']),
            'cssUrls' => new CssRewriteFilter(),
            'uglifyCss' => new UglifyCssFilter($binaries['uglifyCss']),
        );

        $filter_names_by_ext = array(
            'coffee' => array(
                'coffee',
            ),
            'js' => array(
                '?uglifyJs',
            ),
            'css' => array(
                'cssUrls',
                '?uglifyCss',
            ),
        );

        $filters_by_ext = array();
        foreach ($filter_names_by_ext as $ext => $filter_names) {
            foreach ($filter_names as $filter_name) {
                $filter = null;
                if (substr($filter_name, 0, 1) === '?') {
                    if (!$this->options['debug']) {
                        $filter = $filters[substr($filter_name, 1)];
                    }
                } else {
                    $filter = $filters[$filter_name];
                }

                if ($filter) {
                    $filters_by_ext[$ext][$filter_name] = $filter;
                }
            }
        }

        $extension  = null;
        $asset_list = array();
        foreach ($assets as $asset) {
            $extensions = explode('.', $asset);
            $extension  = end($extensions);

            $filters = array();
            foreach (array_reverse($extensions) as $ext) {
                if (array_key_exists($ext, $filters_by_ext)) {
                    $filters = array_merge($filters, $filters_by_ext[$ext]);
                }
            }

            $file_asset = new FileAsset(
                $this->path[0] . '/' . $asset,
                $filters,
                dirname($this->path[0] . '/' . $asset),
                "/app/asset/{$asset}"
            );

            $asset_list[] = $file_asset;
        }

        $collection = new AssetCollection($asset_list);

        $content_types = array(
            ''       => 'text/text',
            'coffee' => 'text/javascript',
            'css'    => 'text/css',
            'gif'    => 'image/gif',
            'ico'    => 'image/vnd.microsoft.icon',
            'jpg'    => 'image/jpeg',
            'js'     => 'text/javascript',
            'png'    => 'image/png',
        );

        $content = $collection->dump();
        return new Response($content, 200, array(
            'Content-Type' => $content_types["{$extension}"],
        ));
    }
}