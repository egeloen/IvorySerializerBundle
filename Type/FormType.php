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
use Ivory\Serializer\Direction;
use Ivory\Serializer\Mapping\TypeMetadataInterface;
use Ivory\Serializer\Type\TypeInterface;
use Symfony\Component\Form\Form;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class FormType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($data, TypeMetadataInterface $type, ContextInterface $context)
    {
        if ($context->getDirection() === Direction::DESERIALIZATION) {
            throw new \RuntimeException(sprintf('Deserializing a "%s" is not supported.', Form::class));
        }

        return $context->getVisitor()->visitArray([
            'code'    => 400,
            'message' => 'Validation Failed',
            'errors'  => $this->serializeForm($data),
        ], $type, $context);
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
