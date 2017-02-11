<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\DependencyInjection\Compiler;

use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass as AbstractRegisterListenerPass;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class RegisterListenerPass extends AbstractRegisterListenerPass
{
    public function __construct()
    {
        parent::__construct(
            'ivory.serializer.event.dispatcher',
            'ivory.serializer.listener',
            'ivory.serializer.subscriber'
        );
    }
}
