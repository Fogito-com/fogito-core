<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito;

/**
 * Fogito\Session
 *
 * Session client-server persistent state data management. This component
 * allows you to separate your session data between application or modules.
 * With this, it's possible to use the same index to refer a variable
 * but it can be in different applications.
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
abstract class Session
{
}
