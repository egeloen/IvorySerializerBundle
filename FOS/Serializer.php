<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\FOS;

use FOS\RestBundle\Context\Context as FOSContext;
use FOS\RestBundle\Serializer\Serializer as FOSSerializerInterface;
use Ivory\Serializer\Context\Context;
use Ivory\Serializer\Exclusion\ChainExclusionStrategy;
use Ivory\Serializer\Exclusion\ExclusionStrategyInterface;
use Ivory\Serializer\Exclusion\GroupsExclusionStrategy;
use Ivory\Serializer\Exclusion\MaxDepthExclusionStrategy;
use Ivory\Serializer\Exclusion\VersionExclusionStrategy;
use Ivory\Serializer\SerializerInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Serializer implements FOSSerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, FOSContext $context)
    {
        return $this->serializer->serialize($data, $format, $this->convertContext($context));
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, FOSContext $context)
    {
        return $this->serializer->deserialize($data, $type, $format, $this->convertContext($context));
    }

    /**
     * @param FOSContext $fosContext
     *
     * @return Context
     */
    private function convertContext(FOSContext $fosContext)
    {
        $context = new Context();
        $context->addOptions($fosContext->getAttributes());

        if ($fosContext->getSerializeNull() !== null) {
            $context->setIgnoreNull(!$fosContext->getSerializeNull());
        }

        $exclusionStrategies = $this->createExclusionStrategies($fosContext);

        if (!empty($exclusionStrategies)) {
            $exclusionStrategy = count($exclusionStrategies) > 1
                ? new ChainExclusionStrategy($exclusionStrategies)
                : array_shift($exclusionStrategies);

            $context->setExclusionStrategy($exclusionStrategy);
        }

        return $context;
    }

    /**
     * @param FOSContext $context
     *
     * @return ExclusionStrategyInterface[]
     */
    private function createExclusionStrategies(FOSContext $context)
    {
        $exclusionStrategies = [];

        $groups = $context->getGroups();
        $version = $context->getVersion();

        if (!empty($groups)) {
            $exclusionStrategies[] = new GroupsExclusionStrategy($groups);
        }

        if (!empty($version)) {
            $exclusionStrategies[] = new VersionExclusionStrategy($version);
        }

        if (method_exists($context, 'isMaxDepthEnabled')) {
            if ($context->isMaxDepthEnabled()) {
                $exclusionStrategies[] = new MaxDepthExclusionStrategy();
            }
        } else {
            $maxDepth = $context->getMaxDepth();

            if (!empty($maxDepth)) {
                $exclusionStrategies[] = new MaxDepthExclusionStrategy($maxDepth);
            }
        }

        $customExclusionStrategies = $context->getAttribute($attribute = 'ivory_exclusion_strategies') ?: [];

        if (!is_array($customExclusionStrategies) && !$customExclusionStrategies instanceof \Traversable) {
            throw new \RuntimeException(sprintf(
                'The "%s" context attribute must be an array or implement "%s".',
                $attribute,
                \Traversable::class
            ));
        }

        foreach ($customExclusionStrategies as $customExclusionStrategy) {
            if (!$customExclusionStrategy instanceof ExclusionStrategyInterface) {
                throw new \RuntimeException(sprintf(
                    'The "%s" context attribute must be an array of "%s", got "%s".',
                    $attribute,
                    ExclusionStrategyInterface::class,
                    is_object($customExclusionStrategy)
                        ? get_class($customExclusionStrategy)
                        : gettype($customExclusionStrategy)
                ));
            }

            $exclusionStrategies[] = $customExclusionStrategy;
        }

        return $exclusionStrategies;
    }
}
