<?php
declare(strict_types=1);

require dirname(__DIR__).'/src/Text.php';
require dirname(__DIR__).'/src/Loader.php';

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

final class LoaderTestEvents
{
    public function fire($eventName, $source, $data = null)
    {
    }
}

$basePath = DIRECTORY_SEPARATOR.'fogito-loader-regression-path';
$namespaceLoader = new Fogito\Loader();
$namespaceLoader->setEventsManager(new LoaderTestEvents());
$namespaceLoader->registerNamespaces(array('Acme' => $basePath));
$namespaceLoader->autoLoad('Acme\\Example\\Missing');

$namespacePath = $basePath.DIRECTORY_SEPARATOR.'Example'.DIRECTORY_SEPARATOR.'Missing.php';
if ($namespaceLoader->getCheckedPath() !== $namespacePath) {
    throw new RuntimeException('Namespace autoload path was not generated correctly.');
}

$prefixLoader = new Fogito\Loader();
$prefixLoader->setEventsManager(new LoaderTestEvents());
$prefixLoader->registerPrefixes(array('Legacy_' => $basePath));
$prefixLoader->autoLoad('Legacy_Example_Missing');

$prefixPath = $basePath.DIRECTORY_SEPARATOR.'Example'.DIRECTORY_SEPARATOR.'Missing.php';
if ($prefixLoader->getCheckedPath() !== $prefixPath) {
    throw new RuntimeException('Prefix autoload path was not generated correctly.');
}

echo "Loader separator regression checks passed.\n";
