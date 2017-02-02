<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\FOS\Type;

use FOS\RestBundle\Serializer\Normalizer\AbstractExceptionNormalizer;
use FOS\RestBundle\Util\ExceptionValueMap;
use Ivory\Serializer\Context\ContextInterface;
use Ivory\Serializer\Direction;
use Ivory\Serializer\Mapping\TypeMetadataInterface;
use Ivory\Serializer\Type\TypeInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class ExceptionType extends AbstractExceptionNormalizer implements TypeInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param ExceptionValueMap $messagesMap
     * @param bool              $debug
     */
    public function __construct(ExceptionValueMap $messagesMap, $debug = false)
    {
        parent::__construct($messagesMap, $debug);

        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($exception, TypeMetadataInterface $type, ContextInterface $context)
    {
        if ($context->getDirection() === Direction::DESERIALIZATION) {
            throw new \RuntimeException('Deserializing an "Exception" is not supported.');
        }

        $result = ['code' => 500];

        if ($context->hasOption('template_data')) {
            $templateData = $context->getOption('template_data');

            if (isset($templateData['status_code'])) {
                $result['code'] = $templateData['status_code'];
            }
        }

        $result['message'] = $this->getExceptionMessage($exception, $result['code']);

        if ($this->debug) {
            $result['exception'] = $this->serializeException($exception);
        }

        return $context->getVisitor()->visitArray($result, $type, $context);
    }

    /**
     * @param \Exception $exception
     *
     * @return mixed[]
     */
    private function serializeException(\Exception $exception)
    {
        $result = [
            'code'    => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString(),
        ];

        if ($exception->getPrevious() !== null) {
            $result['previous'] = $this->serializeException($exception->getPrevious());
        }

        return $result;
    }
}
