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

namespace lunarauth;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\entity\Effect;
use pocketmine\{
    Player,
    Server
};
use lunarauth\{
    command\ChangePasswordCommand,
    command\LoginCommand,
    command\LunarAuthCommand,
    command\RegisterCommand,
    command\RemoveUserCommand,
    command\UserInfoCommand,
    event\ChatAuthListener,
    event\EventListener,
    task\LoginTask
};

use function strtolower;

final class LunarAuth extends PluginBase {

    public static $instance = null;

    public $prefix = "[LunarAuth]";

    public $authenticated = array();

    public $loginAttempts = array();

    public static function getInstance() {
        self::$instance = $this;
    }

    private function registerCommands() {
        Server::getInstance()->getCommandMap()->registerAll("LunarAuth", array(
            new ChangePasswordCommand($this),
            new LoginCommand($this),
            new LunarAuthCommand($this),
            new RegisterCommand($this),
            new RemoveUserCommand($this),
            new UserInfoCommand($this)
        ));
    }

    private function registerListeners() {
        Server::getInstance()->getPluginManager()->registerEvents(new ChatAuthListener($this), $this);
        Server::getInstance()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    private function scheduleTasks() {
        Server::getInstance()->getScheduler()->scheduleRepeatingTask(new LoginTask($this), 20);
    }

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->registerCommands();
        $this->registerListeners();
        $this->scheduleTasks();
        if(!(is_dir($this->getDataFolder() . "data"))) {
            @mkdir($this->getDataFolder() . "data");
        }
        $this->usersConfig = new Config($this->getDataFolder() . "data/users.json", Config::JSON);
    }

    private function getUsersConfig() {
        return $this->usersConfig;
    }

    private function saveData() {
        $usersConfig = $this->getUsersConfig();
        $usersConfig->save();
    }

    public function setUserPassword(string $name, string $password) {
        $config = $this->getUsersConfig();
        $config->setNested($name . ".password", $password);
        $config->save();
    }

    public function setUserAddress(string $name, string $address) {
        $config = $this->getUsersConfig();
        $config->setNested($name . ".ip", $address);
        $config->save();
    }

    public function getUserPassword(string $name) {
        $config = $this->getUsersConfig();
        if(!($config->getNested($name . ".password") == null)) {
            $password = $config->getNested($name . ".password");
        } else {
            $password = null;
        }
        return $password;
    }

    public function getUserAddress(string $name) {
        $config = $this->getUsersConfig();
        if(!($config->getNested($name . ".ip") == null)) {
            $address = $config->getNested($name . ".ip");
        } else {
            $address = null;
        }
        return $address;
    }

    public function isUserRegistred(string $name) {
        $config = $this->getUsersConfig();
        if($config->exists($name)) {
            $bool = true;
        } else {
            $bool = false;
        }
        return $bool;
    }

    public function isUserAuthenticated(Player $player) {
        $name = strtolower($player->getName());
        if(!(isset($this->authenticated[$name]))) {
            $bool = false;
        } else {
            $bool = $this->authenticated[$name];
        }
        return $bool;
    }

    public function authenticateUser(Player $player, bool $bool) {
        $name = strtolower($player->getName());
        $this->authenticated[$name] = $bool;
        if($this->getConfig()->getNested("settings.effects") == true and $bool == true) {
            $player->removeEffect(Effect::INVISIBILITY);
            $player->removeEffect(Effect::BLINDNESS);
        }
    }

    public function removeAuthenticatedUser(Player $player) {
        $name = strtolower($player->getName());
        if(isset($this->authenticated[$name])) {
            unset($this->authenticated[$name]);
        }
    }

    public function getUserLoginAttempts(Player $player) {
        $name = strtolower($player->getName());
        if(!(isset($this->loginAttempts[$name]))) {
            $attempts = 0;
        } else {
            $attempts = $this->loginAttempts[$name];
        }
        return $attempts;
    }

    public function addUserLoginAttempt(Player $player, int $value) {
        $name = strtolower($player->getName());
        $attempts = $this->getUserLoginAttempts($player);
        $this->loginAttempts[$name] = $attempts + $value;
    }

    public function removeUserLoginAttempts(Player $player) {
        $name = strtolower($player->getName());
        if(isset($this->loginAttempts[$name])) {
            unset($this->loginAttempts[$name]);
        }
    }
    
    public function registerUser(Player $player, string $password) {
        $name = strtolower($player->getName());
        $address = $player->getAddress();
        $this->setUserPassword($name, $password);
        $this->setUserAddress($name, $address);
        $this->authenticateUser($player, true);
    }

    public function loginUser(Player $player) {
        $name = strtolower($player->getName());
        $address = $player->getAddress();
        $this->setUserAddress($name, $address);
        $this->authenticateUser($player, true);
    }

    public function changeUserPassword(Player $player, string $password) {
        $name = strtolower($player->getName());
        $this->setUserPassword($name, $password);
    }

    public function removeUser(string $name) {
        $config = $this->getUsersConfig();
        $name = strtolower($name);
        if($config->exists($name)) {
            $config->remove($name);
            $config->save();
        }
    }

    public function onDisable() {
        $this->saveData();
        $this->getLogger()->info("Saving data...");
    }
}