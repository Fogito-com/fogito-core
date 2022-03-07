<?php
namespace Fogito;

use Fogito\AppInterface;

interface ModuleInterface
{    
    /**
     * register
     *
     * @param  mixed $app
     * @return void
     */
    public function register(AppInterface $app);
}
