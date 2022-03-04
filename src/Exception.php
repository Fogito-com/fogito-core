<?php
namespace Fogito;

use Fogito\AbstractException;

class Exception extends AbstractException
{        
    const ERROR_NOT_FOUND_MODULE     = 404;
    const ERROR_NOT_FOUND_CONTROLLER = 405;
    const ERROR_NOT_FOUND_ACTION     = 406;
}
