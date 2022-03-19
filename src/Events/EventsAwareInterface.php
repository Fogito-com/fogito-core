<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
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
