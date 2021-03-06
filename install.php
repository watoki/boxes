<?php

if (!file_exists('build/composer.phar')) {
    echo "Downloading Composer installer..." . PHP_EOL;
    mkdir('build');
    file_put_contents("build/install_composer.php", file_get_contents('http://getcomposer.org/installer'));

    echo "Installing composer.phar" . PHP_EOL;
    echo shell_exec("php build/install_composer.php --install-dir build");
}

echo shell_exec("php build/composer.phar install --dev");