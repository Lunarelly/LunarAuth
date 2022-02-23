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

namespace lunarelly\lunarauth\command;

use pocketmine\command\{
    Command,
    CommandSender,
    PluginIdentifiableCommand
};
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use lunarelly\lunarauth\LunarAuth;

use function strtolower;

class LoginCommand extends Command implements PluginIdentifiableCommand
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

        $this->setDescription("Login command");
        $this->setPermission("lunarauth.command.login");
        $this->setUsage($this->main->getConfig()->getNested("usages.login"));
        $this->setAliases(["l", "log"]);

        parent::__construct("login", $this->getDescription(), $this->getUsage(), $this->getAliases());
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

        if (!($sender instanceof Player)) {
            $sender->sendMessage(TextFormat::RED . "Only in-game!");
            return false;
        }

        $username = strtolower($sender->getName());
        $config = $this->main->getConfig();

        if (!($this->main->isUserRegistered($username))) {
            $sender->sendMessage($config->getNested("messages.userNotRegistered"));
            return false;
        }

        if ($this->main->isUserAuthenticated($sender)) {
            $sender->sendMessage($config->getNested("messages.userAlreadyLoggedIn"));
            return false;
        }

        if (empty($args) or !(isset($args[0]))) {
            $sender->sendMessage($this->usageMessage);
            return false;
        }

        if ($this->main->getConfig()->getNested("settings.encrypt")) {
            $password = $this->main->hash($args[0]);
        } else {
            $password = $args[0];
        }

        if (!($password === $this->main->getUserPassword($username))) {
            if ($this->main->getUserLoginAttempts($sender) >= $config->getNested("settings.maxLoginAttempts")) {
                $this->main->removeUserLoginAttempts($sender);
                $sender->kick($config->getNested("kicks.tooManyLoginAttempts"), false);
                return false;
            }

            $this->main->addUserLoginAttempt($sender, 1);
            $sender->sendMessage($config->getNested("messages.incorrectPassword"));
            return false;
        }

        $this->main->loginUser($sender);
        $this->main->removeUserLoginAttempts($sender);

        $sender->sendMessage($config->getNested("messages.successfulLogin"));
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
