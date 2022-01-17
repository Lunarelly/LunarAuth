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

    private $main;

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        parent::__construct($main);
    }

    public function onRun($currentTick) {
        foreach(Server::getInstance()->getOnlinePlayers() as $players) {
            if($this->main->isUserAuthenticated($players) == false) {
                $config = $this->main->getConfig();
                $this->main->addUserLoginTime($players, 1);
                if($this->main->getUserLoginTime($players) >= $config->getNested("settings.loginTimeout")) {
                    $this->main->removeAuthenticatedUser($players);
                    $this->main->removeUserLoginAttempts($players);
                    $this->main->removeUserLoginTime($players);
                    $players->kick($config->getNested("kicks.loginTimeout"), false);
                }
            }
        }
    }
}