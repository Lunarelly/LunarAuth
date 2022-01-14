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

namespace lunarauth\command;

use pocketmine\command\{
    Command,
    CommandSender,
    ConsoleCommandSender,
    PluginIdentifiableCommand
};
use lunarauth\LunarAuth;

use function strtolower;
use function str_replace;

class RemoveUserCommand extends Command implements PluginIdentifiableCommand {

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->setDescription("Remove user command");
        $this->setPermission("lunarauth.command.removeuser");
        $this->setUsage($this->main->getConfig()->getNested("usage.removeuser"));
        $this->aliases = ["deluser", "rmuser"];
        parent::__construct("removeuser", $this->description, $this->usageMessage, $this->aliases);
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        if(!($this->testPermission($sender))) {
            return false;
        }
        if(!($sender instanceof ConsoleCommandSender)) {
            return $sender->sendMessage("Only from console!");
        }
        $name = strtolower($args[0]);
        $config = $this->main->getConfig();
        if($this->main->isUserRegistred($name) == false) {
            return $sender->sendMessage($config->getNested("messages.userNotRegistredConsole"));
        }
        $this->main->removeUser($name);
        $sender->sendMessage(str_replace("{USER}", $name, $config->getNested("messages.successfulUserRemove")));
        return true;
    }

    public function getPlugin() {
        return $this->main;
    }
}