<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Events;

interface ManagerInterface
{
    /**
     * Attach a listener to the events manager
     *
     * @param string $eventType
     * @param object $handler
     * @param int|null $priority
     */
    public function attach($eventType, $handler, $priority = null);

    /**
     * Removes all events from the EventsManager
     *
     * @param string|null $type
     */
    public function dettachAll($type = null);

    /**
     * Fires a event in the events manager causing that the acive listeners will be notified about it
     *
     * @param string $eventType
     * @param object $source
     * @param mixed|null $data
     * @param boolean|null $cancelable
     * @return mixed
     */
    public function fire($eventType, $source, $data = null, $cancelable = null);

    /**
     * Returns all the attached listeners of a certain type
     *
     * @param string $type
     * @return array
     */
    public function getListeners($type);
}
