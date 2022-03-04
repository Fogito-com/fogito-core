<?php
namespace Fogito;

use Fogito\Exception;

class Loader
{
    private static $prefix;
    private static $baseDir    = '/';
    private static $namespaces = [];

    /**
     * registerNamespaces
     *
     * @param  mixed $namespaces
     * @return void
     */
    public function registerNamespaces(array $namespaces = [])
    {
        foreach ($namespaces as $key => $value) {
            if (self::$prefix) {
                $key = \str_replace(self::$prefix, '', $key);
            }
            $key                    = trim($key, '\\');
            self::$namespaces[$key] = '/' . trim($value, '/');
        }
    }

    /**
     * setBaseDir
     *
     * @param  mixed $dir
     * @return void
     */
    public function setBaseDir($dir)
    {
        self::$baseDir = rtrim($dir, '/') . '/';
    }

    /**
     * getBaseDir
     *
     * @return void
     */
    public function getBaseDir()
    {
        return self::$baseDir;
    }

    /**
     * setPrefix
     *
     * @param  mixed $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        self::$prefix = trim($prefix, '\\');
    }

    /**
     * getPrefix
     *
     * @return void
     */
    public function getPrefix()
    {
        return self::$prefix;
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     *
     * @return bool|null True, if loaded
     */
    private function loadClass($class)
    {
        if (strncmp(self::$prefix, $class, strlen(self::$prefix)) !== 0) {
            return;
        }

        if (self::$prefix) {
            $class = \str_replace(self::$prefix, '', $class);
        }

        $class     = trim($class, '\\');
        $exp       = \explode('\\', $class);
        $classPath = \implode('\\', \array_slice($exp, 0, \count($exp) - 1));
        $className = \end($exp);

        $file = self::$baseDir . \strtolower(\str_replace('\\', DIRECTORY_SEPARATOR, $classPath)) . DIRECTORY_SEPARATOR . $className . '.php';

        if (\array_key_exists($class, self::$namespaces) || \array_key_exists($classPath, self::$namespaces)) {
            $namespace = \array_key_exists($class, self::$namespaces) ? self::$namespaces[$class] : self::$namespaces[$classPath];
            if ($namespace) {
                if (\is_file($namespace) && \pathinfo($namespace, PATHINFO_EXTENSION) == 'php') {
                    $file = rtrim(self::$baseDir, DIRECTORY_SEPARATOR) . $namespace;
                } else {
                    $file = rtrim(self::$baseDir, DIRECTORY_SEPARATOR) . rtrim($namespace, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $className . '.php';
                }
            }
        }

        if (!\file_exists($file)) {
            throw new Exception('Class not found "' . $file . '"');
        }

        require $file;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     * @return void
     */
    public function register($prepend = false)
    {
        \spl_autoload_register([Loader::class, 'loadClass'], true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     *
     * @return void
     */
    public function unregister()
    {
        \spl_autoload_unregister([Loader::class, 'loadClass']);
    }
}
