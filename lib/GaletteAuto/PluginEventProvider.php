<?php

/**
 * This file is part of Galette Auto plugin (https://galette.eu).
 * SPDX-FileCopyrightText: Copyright © 2009-2026 The Galette Team
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace GaletteAuto;

use Galette\Entity\Adherent;
use Galette\Events\GaletteEvent;
use GaletteAuto\Repository\Vehicles;
use League\Event\ListenerRegistry;
use League\Event\ListenerSubscriber;
use Psr\Container\ContainerInterface;

/**
 * Auto listeners on core events
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 */
class PluginEventProvider implements ListenerSubscriber
{
    /**
     * Constructor
     *
     * Built while plugins are loaded: the vehicles repository is resolved
     * only when an event is emitted.
     *
     * @param ContainerInterface $container Container
     */
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * Set up listeners
     *
     * @param ListenerRegistry $acceptor Listener
     */
    public function subscribeListeners(ListenerRegistry $acceptor): void
    {
        $acceptor->subscribeTo(
            'member.before_remove',
            function (GaletteEvent $event): void {
                /** @var \ArrayObject<string, mixed> $member */
                $member = $event->getObject();
                $this->container->get(Vehicles::class)->removeForMember((int)$member[Adherent::PK]);
            }
        );
    }
}
