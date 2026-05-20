<?php
// config/constants.php

// Definir separador de diretório
define('DS', DIRECTORY_SEPARATOR);

// Caminhos absolutos
define('ROOT_PATH', realpath(__DIR__ . DS . '..'));
define('APP_PATH', ROOT_PATH . DS . 'App');
define('CORE_PATH', APP_PATH . DS . 'Core');
define('CONTROLLERS_PATH', APP_PATH . DS . 'Controllers');
define('MODELS_PATH', APP_PATH . DS . 'Models');
define('PUBLIC_PATH', ROOT_PATH . DS . 'Public');
define('VIEWS_PATH', PUBLIC_PATH . DS . 'views');
define('DATA_PATH', ROOT_PATH . DS . 'data');
define('CONFIG_PATH', ROOT_PATH . DS . 'config');

// URL base (ajuste conforme necessidade)
define('BASE_URL', '/pcd/Public');

// Modo debug
define('DEBUG_MODE', true);

// Criar diretório de dados se não existir
if (!file_exists(DATA_PATH)) {
    mkdir(DATA_PATH, 0777, true);
}

// Criar arquivos JSON se não existirem
$messagesFile = DATA_PATH . DS . 'messages.json';
$queueFile = DATA_PATH . DS . 'queue.json';

if (!file_exists($messagesFile)) {
    file_put_contents($messagesFile, json_encode([]));
}
if (!file_exists($queueFile)) {
    file_put_contents($queueFile, json_encode([]));
}