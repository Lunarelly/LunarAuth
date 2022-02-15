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

use lunarauth\LunarAuth;

use SQLite3;

use function strtolower;

class SQLite3DataProvider implements DataProvider {

    /**
     * @var LunarAuth
     */
    private $main;

    /**
     * @var SQLite3
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

        $this->database = new SQLite3($this->main->getDataFolder() . "data/users.db");
        $this->database->exec("CREATE TABLE IF NOT EXISTS `users` (`username` VARCHAR(16) NOT NULL, `password` TEXT NOT NULL, `address` TEXT NOT NULL, `clientsecret` TEXT NOT NULL);");
    }

    /**
     * @return SQLite3
     */
    public function getDatabase(): SQLite3
    {
        return $this->database;
    }

    /**
     * @param string $username
     * @return void
     */
    public function checkUserData(string $username)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = $database->query("SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = $query->fetchArray(SQLITE3_ASSOC);

        if (!($result)) {
            $database->exec("INSERT INTO `users` VALUES ('" . $username . "', '0', '0', '0')");
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setUserPassword(string $username, string $password)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getDatabase();

        $database->exec("UPDATE `users` SET `password` = '" . $password . "' WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @param string $address
     * @return void
     */
    public function setUserAddress(string $username, string $address)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getDatabase();

        $database->exec("UPDATE `users` SET `address` = '" . $address . "' WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @param string $clientSecret
     * @return void
     */
    public function setUserClientSecret(string $username, string $clientSecret)
    {
        $username = strtolower($username);
        $this->checkUserData($username);
        $database = $this->getDatabase();

        $database->exec("UPDATE `users` SET `clientsecret` = '" . $clientSecret . "' WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserPassword(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        return $database->querySingle("SELECT `password` FROM `users` WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserAddress(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        return $database->querySingle("SELECT `address` FROM `users` WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserClientSecret(string $username): string
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        return $database->querySingle("SELECT `clientsecret` FROM `users` WHERE `username` = '" . $username . "';");
    }

    /**
     * @param string $username
     * @return bool
     */
    public function isUserRegistered(string $username): bool
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $query = $database->query("SELECT * FROM `users` WHERE `username` = '" . $username . "';");
        $result = $query->fetchArray(SQLITE3_ASSOC);

        if (!($result) or $result["password"] == "0") {
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

        $database->exec("INSERT INTO `users` VALUES ('" . $username . "', '" . $password . "', '" . $address . "', '" . $clientSecret . "');");
    }

    /**
     * @param string $username
     * @return void
     */
    public function removeUser(string $username)
    {
        $username = strtolower($username);
        $database = $this->getDatabase();

        $database->exec("DELETE FROM `users` WHERE `username` = '" . $username . "';");
    }

    /**
     * @return void
     */
    public function closeDatabase()
    {
        $this->getDatabase()->close();
    }
}