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

namespace lunarauth\event;

use pocketmine\event\{
    Cancellable,
    player\PlayerEvent
};
use pocketmine\Player;

class PlayerAuthorizationEvent extends PlayerEvent implements Cancellable
{

    public static $handlerList = null;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }
}