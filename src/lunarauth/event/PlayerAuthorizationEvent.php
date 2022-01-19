<?php

/*
 *  _                               _ _
 * | |   _   _ _ __   __ _ _ __ ___| | |_   _
 * | |  | | | |  _ \ / _  |  __/ _ \ | | | | |
 * | |__| |_| | | | | (_| | | |  __/ | | |_| |
 * |_____\____|_| |_|\____|_|  \___|_|_|\___ |
 *                                      |___/
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

    /**
     * @var null
     */
    public static $handlerList = null;

    /**
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->player = $player;
    }
}