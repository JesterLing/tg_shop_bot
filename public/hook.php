<?php
require_once '../server/vendor/autoload.php';

$logger = new Monolog\Logger('telegram_bot', [
    (new Monolog\Handler\StreamHandler('../server/logs/botLogDebug.log', Monolog\Logger::DEBUG))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
    (new Monolog\Handler\StreamHandler('../server/logs/botLogError.log', Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
]);
Monolog\ErrorHandler::register($logger);

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET', 'KEY']);

try {
    $config = AdminPanel\Model\Settings::getAll();

    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

    // Enable admin users
    //$telegram->enableAdmins($config['admins']);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPath('../server/BotCommands');

    // Enable MySQL if required
    $telegram->enableExternalMySql(AdminPanel\Module\DB::getPdo());
    //$telegram->enableExternalMySql($pdo, 'tg');

    // Logging (Error, Debug and Raw Updates)
    // https://github.com/php-telegram-bot/core/blob/master/doc/01-utils.md#logging
    Longman\TelegramBot\TelegramLog::initialize($logger);

    // Set custom Download and Upload paths
    $telegram->setDownloadPath(__DIR__ . '/Download');
    $telegram->setUploadPath(__DIR__ . '/Upload');

    // Load all command-specific configurations
    $telegram->setCommandConfig('start', ['type' => $config['type'], 'greetings' => $config['greetings']]);
    $telegram->setCommandConfig('cancel', ['type' => $config['type']]);
    $telegram->setCommandConfig('categories', ['currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency'])]);
    $telegram->setCommandConfig('product', ['type' => $config['type'], 'currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency'])]);
    $telegram->setCommandConfig('cart', ['type' => $config['type'], 'currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency'])]);
    $telegram->setCommandConfig('profile', ['type' => $config['type'], 'currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency'])]);
    $telegram->setCommandConfig('orders', ['type' => $config['type'], 'currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency'])]);
    $telegram->setCommandConfig('payment', ['type' => $config['type'], 'currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency']), 'success_order' => $config['success_order'], 'bot_username' => $config['bot_username'], 'pay_delivery' => $config['pay_delivery'], 'pay_balance' => $config['pay_balance'], 'pay_crystalpay' => $config['pay_crystalpay'], 'crystalpay_login' => $config['crystalpay_login'], 'crystalpay_key' => $config['crystalpay_key'], 'pay_qiwi' => $config['pay_qiwi'], 'pay_coinbase' => $config['pay_coinbase'], 'qiwi_private_key' => $config['qiwi_private_key']]);
    $telegram->setCommandConfig('callbackquery', ['type' => $config['type'], 'currency' => \AdminPanel\Module\Exchange::convertInternal($config['currency']), 'success_order' => $config['success_order'], 'crystalpay_login' => $config['crystalpay_login'], 'crystalpay_key' => $config['crystalpay_key'], 'qiwi_private_key' => $config['qiwi_private_key']]);
    $telegram->setCommandConfig('support', ['support' => 'ABOUT TEXT']);

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter(['enabled' => true]);

    // Handle telegram webhook request
    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Log telegram errors
    Longman\TelegramBot\TelegramLog::error($e);

    // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
    // echo $e;
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
    // echo $e;
    $logger->error($e);
}
