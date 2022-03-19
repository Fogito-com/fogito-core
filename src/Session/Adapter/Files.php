<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Session\Adapter;

use Fogito\Session\Adapter;
use Fogito\Session\AdapterInterface;

/**
 * Fogito\Session\Adapter\Files
 *
 * This adapter store sessions in plain files
 *
 *<code>
 * $session = new Fogito\Session\Adapter\Files(array(
 *    'uniqueId' => 'my-private-app'
 * ));
 *
 * $session->start();
 *
 * $session->set('var', 'some-value');
 *
 * echo $session->get('var');
 *</code>
 */
class Files extends Adapter implements AdapterInterface
{
}
