<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito;

use Fogito\App;

interface ModuleInterface
{    
    /**
     * register
     *
     * @param  mixed $app
     * @return void
     */
    public function register(App $app);
}
