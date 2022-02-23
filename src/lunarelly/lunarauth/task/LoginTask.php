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

class LoginTask extends PluginTask
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
                $this->main->addUserLoginTime($players, 1);

                if ($this->main->getUserLoginTime($players) >= $config->getNested("settings.loginTimeout")) {
                    $this->main->removeAuthenticatedUser($players);
                    $this->main->removeUserLoginAttempts($players);
                    $this->main->removeUserLoginTime($players);

                    $players->kick($config->getNested("kicks.loginTimeout"), false);
                }
            }
        }
    }
}