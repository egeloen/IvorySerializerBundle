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
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class FormErrorType implements TypeInterface
{
    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    /**
     * @param TranslatorInterface|null $translator
     */
    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($data, TypeMetadataInterface $type, ContextInterface $context)
    {
        if ($context->getDirection() === Direction::DESERIALIZATION) {
            throw new \RuntimeException(sprintf('Deserializing a "%s" is not supported.', FormError::class));
        }

        return $context->getVisitor()->visitString($this->translateError($data), $type, $context);
    }

    /**
     * @param FormError $error
     *
     * @return string
     */
    private function translateError(FormError $error)
    {
        if ($this->translator === null) {
            return $error->getMessage();
        }

        if ($error->getMessagePluralization() !== null) {
            return $this->translator->transChoice(
                $error->getMessageTemplate(),
                $error->getMessagePluralization(),
                $error->getMessageParameters(),
                'validators'
            );
        }

        return $this->translator->trans(
            $error->getMessageTemplate(),
            $error->getMessageParameters(),
            'validators'
        );
    }
}
