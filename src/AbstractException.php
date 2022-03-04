<?php
namespace Fogito;

use Fogito\Response;

class AbstractException extends \Exception
{
    /**
     * httpCode
     *
     * @var mixed
     */
    protected $httpCode;

    /**
     * httpMessage
     *
     * @var mixed
     */
    protected $httpMessage;

    /**
     * appError
     *
     * @var array
     */
    protected $appError = [];

    /**
     * __construct
     *
     * @param  mixed $httpMessage
     * @param  mixed $httpCode
     * @param  mixed $type
     * @param  mixed $previous
     * @return void
     */
    public function __construct($httpMessage = null, $httpCode = null, \Exception $previous = null)
    {
        if ($previous instanceof \Exception) {
            if (is_null($httpCode)) {
                $httpCode = $previous->getCode();
            }

            if (is_null($httpMessage)) {
                $httpMessage = $previous->getMessage();
            }
        }

        $this->appError = [
            Response::$keyCode    => $httpCode,
            Response::$keyMessage => $httpMessage,
        ];
        parent::__construct($this->httpMessage, $this->httpCode, $previous);
    }

    /**
     * getAppError
     *
     * @return void
     */
    public function getAppError()
    {
        return $this->appError;
    }

    /**
     * setErrorCode
     *
     * @param  mixed $code
     * @return void
     */
    public function setErrorCode($code)
    {
        return $this->httpCode = $code;
    }

    /**
     * addErrorDetails
     *
     * @param  mixed $details
     * @return void
     */
    public function addErrorDetails(array $details)
    {
        if (!is_null($this->appError['details'])) {
            $details = \array_merge($this->appError['details'], $details);
        }

        $this->appError['details'] = $details;
        return $this;
    }
}
