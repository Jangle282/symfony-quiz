<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Rebuild the test database from scratch on every PHPUnit run
$kernel = new App\Kernel('test', true);
$kernel->boot();

$application = new Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
$application->setAutoExit(false);

// Drop the existing test database if it exists
$application->run(new Symfony\Component\Console\Input\ArrayInput([
    'command' => 'doctrine:database:drop',
    '--force' => true,
    '--if-exists' => true,
    '--quiet' => true,
]), new Symfony\Component\Console\Output\NullOutput());

// Create the database
$application->run(new Symfony\Component\Console\Input\ArrayInput([
    'command' => 'doctrine:database:create',
    '--quiet' => true,
]), new Symfony\Component\Console\Output\NullOutput());

// Run migrations
$application->run(new Symfony\Component\Console\Input\ArrayInput([
    'command' => 'doctrine:migrations:migrate',
    '--no-interaction' => true,
    '--quiet' => true,
]), new Symfony\Component\Console\Output\NullOutput());

$kernel->shutdown();
