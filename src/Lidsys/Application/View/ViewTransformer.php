<?php

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
