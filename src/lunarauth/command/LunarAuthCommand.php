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
    PluginIdentifiableCommand
};
use pocketmine\utils\TextFormat;
use lunarauth\LunarAuth;

use function in_array;

class LunarAuthCommand extends Command implements PluginIdentifiableCommand
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

        $this->setDescription("LunarAuth command");
        $this->setPermission("lunarauth.command.default");
        $this->setUsage($this->main->getConfig()->getNested("usages.default"));
        $this->setAliases(["la", "auth"]);

        parent::__construct("lunarauth", $this->getDescription(), $this->getUsage(), $this->getAliases());
    }

    /**
     * @param CommandSender $sender
     * @param $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $commandLabel, array $args): bool
    {
        if (!($this->testPermission($sender))) {
            return false;
        }

        $subcommands = ["help", "about"];
        if (empty($args) or !(isset($args[0])) or !(in_array($args[0], $subcommands))) {
            $sender->sendMessage($this->usageMessage);
            return false;
        }

        if ($args[0] == "help") {
            $sender->sendMessage(TextFormat::LIGHT_PURPLE . $this->main->getPrefix() . " Commands: /register, /login, /changepassword, /removeuser, /userinfo");
        }

        if ($args[0] == "about") {
            $description = $this->main->getDescription();
            $name = $description->getName();
            $version = $description->getVersion();
            $author = implode($description->getAuthors());
            $website = $description->getWebsite();

            $sender->sendMessage(TextFormat::LIGHT_PURPLE . $this->main->getPrefix() . " This server is running " . $name . " v" . $version . "\n" . TextFormat::LIGHT_PURPLE . "Author: " . $author . "\n" . TextFormat::LIGHT_PURPLE . "GitHub: " . $website);
        }
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