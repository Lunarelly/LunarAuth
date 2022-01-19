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

namespace lunarauth\command;

use pocketmine\command\{
    Command,
    CommandSender,
    ConsoleCommandSender,
    PluginIdentifiableCommand
};
use pocketmine\utils\TextFormat;
use lunarauth\LunarAuth;

use function strtolower;
use function str_replace;

class RemoveUserCommand extends Command implements PluginIdentifiableCommand
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

        $this->setDescription("Remove user command");
        $this->setPermission("lunarauth.command.removeuser");
        $this->setUsage($this->main->getConfig()->getNested("usages.removeuser"));
        $this->setAliases(["deluser", "rmuser"]);

        parent::__construct("removeuser", $this->description, $this->usageMessage, $this->getAliases());
    }

    /**
     * @param CommandSender $sender
     * @param $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $commandLabel, array $args): bool
    {
        if(!($this->testPermission($sender))) {
            return false;
        }

        if(!($sender instanceof ConsoleCommandSender)) {
            $sender->sendMessage(TextFormat::RED . "Only from console!");
            return false;
        }

        if(empty($args) or !(isset($args[0]))) {
            $sender->sendMessage($this->usageMessage);
            return false;
        }

        $username = strtolower($args[0]);
        $config = $this->main->getConfig();

        if(!($this->main->isUserRegistered($username))) {
            $sender->sendMessage($config->getNested("messages.userNotRegisteredConsole"));
            return false;
        }

        $this->main->removeUser($username);
        $sender->sendMessage(str_replace("{USER}", $username, $config->getNested("messages.successfulUserRemove")));
        return true;
    }

    /**
     * @return LunarAuth
     */
    public function getPlugin(): LunarAuth
    {
        return $this->main;
    }
}