<?php

/*
 *  _							    _ _	   
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _ 
 * | |  | | | | '_ \ / _` | '__/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\__,_|_| |_|\__,_|_|  \___|_|_|\__, |
 *									    |___/ 
 * 
 * Author: Lunarelly
 * 
 * GitHub: https://github.com/Lunarelly
 * 
 * Telegram: https://t.me/lunarellyy
 * 
 */

namespace lunarauth\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use lunarauth\LunarAuth;

use function strtolower;

class MessageTask extends PluginTask {

    private $main;

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        parent::__construct($main);
    }

    public function onRun($currentTick) {
        foreach(Server::getInstance()->getOnlinePlayers() as $players) {
            if($this->main->isUserAuthenticated($players) == false) {
                $config = $this->main->getConfig();
                $username = strtolower($players->getName());
                $this->main->addUserLoginMessageTime($players, 1);
                if($this->main->getUserLoginMessageTime($players) >= $config->getNested("settings.messageInterval")) {
                    $this->main->removeUserLoginMessageTime($players);
                    if($this->main->isUserRegistered($username) == false) {
                        if($config->getNested("settings.chatAuth") == true) {
                            $players->sendMessage($config->getNested("messages.userChatRegistration"));
                        } else {
                            $players->sendMessage($config->getNested("messages.userRegistration"));
                        }
                    } else {
                        if($config->getNested("settings.chatAuth") == true) {
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