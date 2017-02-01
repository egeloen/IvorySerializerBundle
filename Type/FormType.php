<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\Type;

use Ivory\Serializer\Context\ContextInterface;
use Ivory\Serializer\Mapping\TypeMetadataInterface;
use Ivory\Serializer\Type\AbstractClassType;
use Symfony\Component\Form\Form;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class FormType extends AbstractClassType
{
    /**
     * {@inheritdoc}
     */
    protected function serialize($form, TypeMetadataInterface $type, ContextInterface $context)
    {
        return $context->getVisitor()->visitArray([
            'code'    => 400,
            'message' => 'Validation Failed',
            'errors'  => $this->serializeForm($form),
        ], $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function deserialize($data, TypeMetadataInterface $type, ContextInterface $context)
    {
        throw new \RuntimeException(sprintf('Deserializing a "%s" is not supported.', Form::class));
    }

    /**
     * @param Form $form
     *
     * @return mixed[]
     */
    private function serializeForm(Form $form)
    {
        $result = $children = [];
        $errors = iterator_to_array($form->getErrors());

        foreach ($form as $child) {
            if ($child instanceof Form) {
                $children[$child->getName()] = $this->serializeForm($child);
            }
        }

        if (!empty($errors)) {
            $result['errors'] = $errors;
        }

        if (!empty($children)) {
            $result['children'] = $children;
        }

        return $result;
    }
}
