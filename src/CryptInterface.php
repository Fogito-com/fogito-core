<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito;

/**
 * Fogito\CryptInterface initializer
 */
interface CryptInterface
{
    /**
     * Sets the cipher algorithm
     *
     * @param string $cipher
     * @return \Fogito\EncryptInterface
     */
    public function setCipher($cipher);

    /**
     * Returns the current cipher
     *
     * @return string
     */
    public function getCipher();

    /**
     * Sets the encrypt/decrypt mode
     *
     * @param string $mode
     * @return \Fogito\EncryptInterface
     */
    public function setMode($mode);

    /**
     * Returns the current encryption mode
     *
     * @return string
     */
    public function getMode();

    /**
     * Sets the encryption key
     *
     * @param string $key
     * @return \Fogito\EncryptInterface
     */
    public function setKey($key);

    /**
     * Returns the encryption key
     *
     * @return string
     */
    public function getKey();

    /**
     * Encrypts a text
     *
     * @param string $text
     * @param string|null $key
     * @return string
     */
    public function encrypt($text, $key = null);

    /**
     * Decrypts a text
     *
     * @param string $text
     * @param string|null $key
     * @return string
     */
    public function decrypt($text, $key = null);

    /**
     * Encrypts a text returning the result as a base64 string
     *
     * @param string $text
     * @param string|null $key
     * @return string
     */
    public function encryptBase64($text, $key = null);

    /**
     * Decrypt a text that is coded as a base64 string
     *
     * @param string $text
     * @param string|null $key
     * @return string
     */
    public function decryptBase64($text, $key = null);

    /**
     * Returns a list of available cyphers
     *
     * @return array
     */
    public function getAvailableCiphers();

    /**
     * Returns a list of available modes
     *
     * @return array
     */
    public function getAvailableModes();
}
