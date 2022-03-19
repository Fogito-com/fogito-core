<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito;

class Exception extends \Exception
{
    const ERROR_NOT_FOUND_MODULE     = 404;
    const ERROR_NOT_FOUND_CONTROLLER = 405;
    const ERROR_NOT_FOUND_ACTION     = 406;

    /**
     * code
     *
     * @var mixed
     */
    protected $code = 400;

    /**
     * message
     *
     * @var mixed
     */
    protected $message;

    /**
     * data
     *
     * @var array
     */
    protected $data = [];

    /**
     * __construct
     *
     * @param  mixed $message
     * @param  mixed $code
     * @param  mixed $previous
     * @return void
     */
    public function __construct($message = null, $code = null, \Exception $previous = null)
    {
        if ($previous instanceof \Exception) {
            if (is_null($code)) {
                $code = $previous->getCode();
            }

            if (is_null($message)) {
                $message = $previous->getMessage();
            }
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * getData
     *
     * @return void
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public function setData($data = [])
    {
        $this->data = $data;
    }
}
