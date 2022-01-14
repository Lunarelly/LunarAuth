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
    PluginIdentifiableCommand
};
use lunarauth\LunarAuth;

use function in_array;
use function strtolower;

class LunarAuthCommand extends Command implements PluginIdentifiableCommand {

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->setDescription("LunarAuth command");
        $this->setPermission("lunarauth.command.default");
        $this->setUsage($this->main->getConfig()->getNested("usage.default"));
        $this->aliases = ["la", "auth"];
        parent::__construct("lunarauth", $this->description, $this->usageMessage, $this->aliases);
    }

    public function execute(CommandSender $sender, $alias, array $args) {
        if(!($this->testPermission($sender))) {
            return false;
        }
        $subcommands = ["help", "info"];
        if(empty($args) or !(isset($args[0])) or !(in_array($args[0], $subcommands))) {
            return $sender->sendMessage($this->usageMessage);
        }
        if($args[0] == "help") {
            return $sender->sendMessage($this->main->prefix . " Commands: /register, /login, /changepassword, /removeuser, /userinfo");
        }
        if($args[0] == "info") {
            $description = $this->main->getDescription();
            $name = $description->getName();
            $version = $description->getVersion();
            return $sender->sendMessage($this->main->prefix . " This server is running " . $name . " v" . $version . "\nAuthor: Lunarelly\nGitHub: https://github.com/Lunarelly");
        }
    }

    public function getPlugin() {
        return $this->main;
    }
}