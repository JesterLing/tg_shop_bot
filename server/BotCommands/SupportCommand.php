<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class SupportCommand extends UserCommand
{

    protected $name = 'support';
    protected $description = 'Поддежка';
    protected $usage = '/support';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        return $this->replyToChat($this->getConfig('support'));
    }

}
