<?php
namespace Fogito\Events;

interface EventsAwareInterface
{
    /**
     * Sets the events manager
     *
     * @param \Fogito\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager);

    /**
     * Returns the internal event manager
     *
     * @return \Fogito\Events\ManagerInterface
     */
    public function getEventsManager();
}
