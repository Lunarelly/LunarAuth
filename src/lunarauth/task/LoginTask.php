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

class LoginTask extends PluginTask {

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->notAuthenticatedTimeout = array();
        parent::__construct($main);
    }

    public function onRun($currentTick) {
        foreach(Server::getInstance()->getOnlinePlayers() as $players) {
            if($this->main->isUserAuthenticated($players) == false) {
                $name = strtolower($players->getName());
                $config = $this->main->getConfig();
                if(!(isset($this->notAuthenticatedTimeout[$name]))) {
                    $this->notAuthenticatedTimeout[$name] = 0;
                }
                $this->notAuthenticatedTimeout[$name]++;
                if($this->notAuthenticatedTimeout[$name] >= $config->getNested("settings.loginTimeout")) {
                    $this->main->removeAuthenticatedUser($players);
                    $this->main->removeUserLoginAttempts($players);
                    unset($this->notAuthenticatedTimeout[$name]);
                    $players->kick($config->getNested("kicks.loginTimeout"), false);
                }
            }
        }
    }
}