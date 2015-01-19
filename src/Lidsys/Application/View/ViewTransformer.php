<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Application\View;

class ViewTransformer
{
    public function transform(TransformationInterface $transformation, $entity)
    {
        return $transformation->transform($entity);
    }

    public function transformList(TransformationInterface $transformation, $entities)
    {
        $new_entities = [];
        foreach ($entities as $entity_key => $entity) {
            $new_entities[$entity_key] = $this->transform($transformation, $entity);
        }

        return $new_entities;
    }
}
