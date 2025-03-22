<?php
namespace Fogito\Events;

use Closure;
use Fogito\Exception;
use SplPriorityQueue;

class Manager
{
    /**
     * Events
     *
     * @var array|null
     * @access protected
     */
    protected $_events;

    /**
     * Collect
     *
     * @var boolean
     * @access protected
     */
    protected $_collect = false;

    /**
     * Enable Priorities
     *
     * @var boolean
     * @access protected
     */
    protected $_enablePriorities = false;

    /**
     * Responses
     *
     * @var array|null
     * @access protected
     */
    protected $_responses;

    /**
     * Attach a listener to the events manager
     *
     * @param string $eventType
     * @param object|callable $handler
     * @param int|null $priority
     * @throws Exception
     */
    public function attach($eventType, $handler, $priority = null)
    {
        if (is_null($priority) === true) {
            $priority = 100;
        } elseif (is_int($priority) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($eventType) === false) {
            throw new Exception('Event type must be a string');
        }

        if (is_object($handler) === false &&
            is_callable($handler) === false) {
            throw new Exception('Event handler must be an Object');
        }

        if (is_array($this->_events) === false) {
            $this->_events = array();
        }

        if (isset($this->_events[$eventType]) === false) {
            if ($this->_enablePriorities === true) {
                //Create a SplPriorityQueue to store the events with priorities
                $priorityQueue = new SplPriorityQueue();
                $priorityQueue->setExtractFlags(1);
                $this->_events[$eventType] = $priorityQueue;
            } else {
                $this->_events[$eventType] = array();
            }
        }

        //Get the current queue
        $priorityQueue = $this->_events[$eventType];

        //Insert the handler in the queue
        if (is_object($priorityQueue) === true) {
            //Pointer usage
            $priorityQueue->insert($handler, $priority);
        } else {
            $priorityQueue[] = $handler;

            //Append the events to the queue
            $this->_events[$eventType] = $priorityQueue;
        }
    }

    /**
     * Set if priorities are enabled in the EventsManager
     *
     * @param boolean $enablePriorities
     * @throws Exception
     */
    public function enablePriorities($enablePriorities)
    {
        if (is_bool($enablePriorities) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_enablePriorities = $enablePriorities;
    }

    /**
     * Returns if priorities are enabled
     *
     * @return boolean
     */
    public function arePrioritiesEnabled()
    {
        return $this->_enablePriorities;
    }

    /**
     * Tells the event manager if it needs to collect all the responses returned by every
     * registered listener in a single fire
     *
     * @param boolean $collect
     * @throws Exception
     */
    public function collectResponses($collect)
    {
        if (is_bool($collect) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_collect = $collect;
    }

    /**
     * Check if the events manager is collecting all all the responses returned by every
     * registered listener in a single fire
     *
     * @return boolean
     */
    public function isCollecting()
    {
        return $this->_collect;
    }

    /**
     * Returns all the responses returned by every handler executed by the last 'fire' executed
     *
     * @return array|null
     */
    public function getResponses()
    {
        return $this->_responses;
    }

    /**
     * Removes all events from the EventsManager
     *
     * @param string|null $type
     * @throws Exception
     */
    public function detachAll($type = null)
    {
        if (is_null($type) === true) {
            $this->_events = null;
        } elseif (is_string($type) === true) {
            unset($this->_events[$type]);
        } else {
            throw new Exception('Invalid parameter type.');
        }
    }

    /**
     * Removes all events from the EventsManager; alias of detachAll
     *
     * @deprecated
     * @param string|null $type
     */
    public function dettachAll($type = null)
    {
        $this->detachAll($type);
    }

    /**
     * Internal handler to call a queue of events
     *
     * @param \SplPriorityQueue|array $queue
     * @param \Fogito\Events\Event $event
     * @return mixed
     * @throws Exception
     */
    public function fireQueue($queue, $event)
    {
        if (is_array($queue) === false &&
            (is_object($queue) === false ||
                $queue instanceof SplPriorityQueue === false)) {
            throw new Exception('The SplPriorityQueue is not valid');
        }

        if (is_object($event) === false ||
            $event instanceof Event === false) {
            throw new Exception('The event is not valid');
        }

        $status    = null;
        $arguments = null;

        //Get the event type
        $eventName = $event->getType();
        if (is_string($eventName) === false) {
            //@note missing "is"
            throw new Exception('The event type not vaid');
        }

        $source     = $event->getSource();
        $data       = $event->getData();
        $cancelable = $event->getCancelable();

        if (is_object($queue) === true) {
            //We need to clone the queue before iterate over it
            $iterator = clone $queue;

            //Move the queue to the top
            $iterator->top();

            while ($iterator->valid() === true) {
                $handler = $iterator->current();

                if (is_object($handler) === true) {
                    //Only handler objects are valid
                    if ($handler instanceof Closure) {
                        //Create the closure arguments
                        if (is_null($arguments) === true) {
                            $arguments = array($event, $source, $data);
                        }

                        //Call the function in the PHP userland
                        $status = call_user_func_array($handler, $arguments);

                        //Trace the responses
                        if ($this->_collect === true) {
                            $this->_responses[] = $status;
                        }

                        if ($cancelable === true) {
                            //Check if the event was stopped by the user
                            if ($event->isStopped() === true) {
                                break;
                            }
                        }
                    } else {
                        //Check if the listener has implemented an event with the same name
                        if (is_object($handler) && method_exists($handler, $eventName) === true) {
                            //Call the function in the PHP userland
                            $status = $handler->$eventName($event, $source, $data);

                            //Collect the responses
                            if ($this->_collect === true) {
                                $this->_responses[] = $status;
                            }

                            if ($cancelable === true) {
                                //Check if the event was stopped by the user
                                if ($event->isStopped() === true) {
                                    break;
                                }
                            }
                        }
                    }
                }

                $iterator->next();
            }
        } else {
            foreach ($queue as $handler) {
                //Only handler objects are valid
                if (is_object($handler) === true) {
                    //Check if the event is a closure
                    if ($handler instanceof Closure === true) {
                        //Create the closure arguments
                        if (is_null($arguments) === true) {
                            $arguments = array($event, $source, $data);
                        }

                        //Call the function in the PHP userland
                        $status = call_user_func_array($handler, $arguments);

                        //Trace the response
                        if ($this->_collect === true) {
                            $this->_responses[] = $status;
                        }

                        if ($cancelable === true) {
                            //Check if the event was stopped by the user
                            if ($event->isStopped() === true) {
                                break;
                            }
                        }
                    } else {
                        //Ćheck if the listener has implemented an event with the same name
                        if (is_object($handler) && method_exists($handler, $eventName) === true) {
                            //Call the function in the PHP userland
                            $status = $handler->$eventName($event, $source, $data);

                            //Collect the responses
                            if ($this->_collect === true) {
                                $this->_responses[] = $status;
                            }

                            if ($cancelable === true) {
                                //Check if the event was stopped by the user
                                if ($event->isStopped() === true) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Fires an event in the events manager causing that active listeners be notified about it
     *
     *<code>
     *  $eventsManager->fire('db', $connection);
     *</code>
     *
     * @param string $eventType
     * @param object $source
     * @param mixed $data
     * @param boolean|null $cancelable
     * @return mixed
     * @throws Exception
     */
    public function fire($eventType, $source, $data = null, $cancelable = null)
    {
        if (is_null($cancelable) === true) {
            $cancelable = true;
        } elseif (is_bool($cancelable) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($eventType) === false) {
            throw new Exception('Event type must be a string');
        }

        if (is_object($source) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_events) === false) {
            return null;
        }

        //All valid events must be a colon seperator
        if (strpos($eventType, ':') === false) {
            throw new Exception('Invalid event type ' . $eventType);
        }

        $eventParts = explode(':', $eventType);
        //@note no isset check for $eventParts[0], $eventParts[1]
        //type: 0
        //name: 1

        //Responses must be traces?
        if ($this->_collect === true) {
            $this->_responses = null;
        }

        $status = null;
        $event  = null;
        //Check if events are grouped by type
        if (isset($this->_events[$eventParts[0]]) === true) {
            $fireEvents = $this->_events[$eventParts[0]];

            if (is_array($fireEvents) === true ||
                is_object($fireEvents) === true) {
                //Create the event context
                $event  = new Event($eventParts[1], $source, $data, $cancelable);
                $status = $this->fireQueue($fireEvents, $event);
            }
        }

        //Check if there are listeners for the event type itself
        if (isset($this->_events[$eventType]) === true) {
            $fireEvents = $this->_events[$eventType];

            if (is_array($fireEvents) === true ||
                is_object($fireEvents) === true) {
                //Create the event if it wasn't created before
                if (is_null($event) === true) {
                    $event = new Event($eventParts[1], $source, $data, $cancelable);
                }

                //Call the events queue
                $status = $this->fireQueue($fireEvents, $event);
            }
        }

        return $status;
    }

    /**
     * Check whether certain type of event has listeners
     *
     * @param string $type
     * @return boolean
     * @throws Exception
     */
    public function hasListeners($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_events) === true) {
            return isset($this->_events[$type]);
        }

        return false;
    }

    /**
     * Returns all the attached listeners of a certain type
     *
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function getListeners($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_events) === true &&
            isset($this->_events[$type]) === true) {
            return $this->_events[$type];
        }

        return array();
    }
}
