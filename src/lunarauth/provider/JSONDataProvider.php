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

namespace lunarauth\provider;

use pocketmine\utils\Config;
use lunarauth\LunarAuth;

use function strtolower;

class JSONDataProvider implements DataProvider {

    /**
     * @var LunarAuth
     */
    private $main;

    /**
     * @var Config
     */
    private $database;

    /**
     * @param LunarAuth $main
     */
    public function __construct(LunarAuth $main)
    {
        $this->main = $main;

        if (!(is_dir($this->main->getDataFolder() . "data"))) {
            @mkdir($this->main->getDataFolder() . "data");
        }

        $this->database = new Config($this->main->getDataFolder() . "data/users.json", Config::JSON);
    }

    /**
     * @return Config
     */
    public function getDatabase(): Config
    {
        return $this->database;
    }

    /**
     * @param string $username
     * @return void
     */
    public function checkUserData(string $username)
    {
    }

    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setUserPassword(string $username, string $password)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $database->setNested($username . ".password", $password);
        $database->save();
    }

    /**
     * @param string $username
     * @param string $address
     * @return void
     */
    public function setUserAddress(string $username, string $address)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $database->setNested($username . ".address", $address);
        $database->save();
    }

    /**
     * @param string $username
     * @param string $clientSecret
     * @return void
     */
    public function setUserClientSecret(string $username, string $clientSecret)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $database->setNested($username . ".clientsecret", $clientSecret);
        $database->save();
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserPassword(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        return $database->getNested($username . ".password");
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserAddress(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        return $database->getNested($username . ".address");
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserClientSecret(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        return $database->getNested($username . ".clientsecret");
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserRegistered(string $username): bool
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        if (!($database->exists($username))) {
            $bool = false;
        } else {
            $bool = true;
        }
        return $bool;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $address
     * @param string $clientSecret
     * @return void
     */
    public function registerUser(string $username, string $password, string $address, string $clientSecret)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $database->setNested($username . ".password", $password);
        $database->setNested($username . ".address", $address);
        $database->setNested($username . ".clientsecret", $clientSecret);
        $database->save();
    }

    /**
     * @param string $username
     * @return void
     */
    public function removeUser(string $username)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $database->remove($username);
        $database->save();
    }

    /**
     * @return void
     */
    public function closeDatabase()
    {
        $this->getDatabase()->save();
    }
}