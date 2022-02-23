<?php

/**
 *  _                               _ _
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _
 * | |  | | | |  _ \ / _  |  __/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\____|_| |_|\____|_|  \___|_|_|\___ |
 *                                      |___/
 *
 * @author Lunarelly
 * @link https://github.com/Lunarelly
 *
 */

namespace lunarelly\lunarauth\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use lunarelly\lunarauth\LunarAuth;

use function strtolower;

class MessageTask extends PluginTask
{

    /**
     * @var LunarAuth
     */
    private $main;

    /**
     * @param LunarAuth $main
     */
    public function __construct(LunarAuth $main)
    {
        $this->main = $main;
        parent::__construct($main);
    }

    /**
     * @param $currentTick
     * @return void
     */
    public function onRun($currentTick)
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
            if (!($this->main->isUserAuthenticated($players))) {

                $config = $this->main->getConfig();
                $username = strtolower($players->getName());
                $this->main->addUserLoginMessageTime($players, 1);

                if ($this->main->getUserLoginMessageTime($players) >= $config->getNested("settings.messageInterval")) {
                    $this->main->removeUserLoginMessageTime($players);

                    if (!($this->main->isUserRegistered($username))) {
                        if ($config->getNested("settings.chatAuth")) {
                            $players->sendMessage($config->getNested("messages.userChatRegistration"));
                        } else {
                            $players->sendMessage($config->getNested("messages.userRegistration"));
                        }
                    } else {
                        if ($config->getNested("settings.chatAuth")) {
                            $players->sendMessage($config->getNested("messages.userChatLogin"));
                        } else {
                            $players->sendMessage($config->getNested("messages.userLogin"));
                        }
                    }
                }
            }
        }
    }
}