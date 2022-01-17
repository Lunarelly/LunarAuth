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

class UserInfoCommand extends Command implements PluginIdentifiableCommand {

    private $main;

    private $aliases;

    public function __construct(LunarAuth $main) {
        $this->main = $main;
        $this->setDescription("User info command");
        $this->setPermission("lunarauth.command.userinfo");
        $this->setUsage($this->main->getConfig()->getNested("usages.userinfo"));
        $this->aliases = ["checkpassword", "checkuser"];
        parent::__construct("userinfo", $this->description, $this->usageMessage, $this->aliases);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args) {
        if(!($this->testPermission($sender))) {
            return false;
        }
        if(!($sender instanceof ConsoleCommandSender)) {
            return $sender->sendMessage("Only from console!");
        }
        if(empty($args) or !(isset($args[0]))) {
            return $sender->sendMessage($this->usageMessage);
        }
        $username = strtolower($args[0]);
        $config = $this->main->getConfig();
        if($this->main->isUserRegistered($username) == false) {
            return $sender->sendMessage($config->getNested("messages.userNotRegisteredConsole"));
        }
        $password = $this->main->getUserPassword($username);
        $address = $this->main->getUserAddress($username);
        $sender->sendMessage(str_replace(["{USER}", "{PASSWORD}", "{IP}", "{EOL}"], [$username, $password, $address, "\n"], $config->getNested("messages.userInfo")));
        return true;
    }

    public function getPlugin() {
        return $this->main;
    }
}