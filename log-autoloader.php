<?php

$mapping = array(
    'Monolog\Logger' => __DIR__ . '/Monolog/Logger.php',
    'Monolog\Handler\StreamHandler' => __DIR__ . '/Monolog/Handler/StreamHandler.php',
    'Monolog\Handler\HandlerInterface' => __DIR__ . '/Monolog/Handler/HandlerInterface.php',
    'Psr\Log\LoggerInterface' => __DIR__ . '/Monolog/Psr/Log/LoggerInterface.php',
    'Psr\Log\InvalidArgumentException' => __DIR__ . '/Psr/Log/InvalidArgumentException.php',
    'Monolog\ResettableInterface' => __DIR__ . '/Monolog/ResettableInterface.php',
    'Monolog\Handler\AbstractProcessingHandler' => __DIR__ . '/Monolog/Handler/AbstractProcessingHandler.php',
    'Monolog\Handler\AbstractHandler' => __DIR__ . '/Monolog/Handler/AbstractHandler.php',
    'Monolog\Handler\Handler' => __DIR__ . '/Monolog/Handler/Handler.php',
    'Monolog\Handler\ProcessableHandlerTrait' => __DIR__ . '/Monolog/Handler/ProcessableHandlerTrait.php',
    'Monolog\Handler\FormattableHandlerTrait' => __DIR__ . '/Monolog/Handler/FormattableHandlerTrait.php',
    'Monolog\Handler\ProcessableHandlerInterface' => __DIR__ . '/Monolog/Handler/ProcessableHandlerInterface.php',
    'Monolog\Handler\FormattableHandlerInterface' => __DIR__ . '/Monolog/Handler/FormattableHandlerInterface.php',
    'Monolog\Utils' => __DIR__ . '/Monolog/Utils.php',
    'Monolog\DateTimeImmutable' => __DIR__ . '/Monolog/DateTimeImmutable.php',
    'Monolog\Formatter\LineFormatter' => __DIR__ . '/Monolog/Formatter/LineFormatter.php',
    'Monolog\Formatter\NormalizerFormatter' => __DIR__ . '/Monolog/Formatter/NormalizerFormatter.php',
    'Monolog\Formatter\FormatterInterface' => __DIR__ . '/Monolog/Formatter/FormatterInterface.php',
    'Throwable' => __DIR__ . 'Throwable.php',
    'DateTimeZone' => __DIR__ . 'DateTimeZone.php',
);

spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class])) {
        require $mapping[$class];
    }
}, true);
