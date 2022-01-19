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

interface DataProvider {

    /**
     * @return mixed
     */
    public function getDatabase();

    /**
     * @param string $username
     * @return void
     */
    public function checkUserData(string $username);

    /**
     * @param string $username
     * @param string $password
     * @return void
     */
    public function setUserPassword(string $username, string $password);

    /**
     * @param string $username
     * @param string $address
     * @return void
     */
    public function setUserAddress(string $username, string $address);

    /**
     * @param string $username
     * @return string
     */
    public function getUserPassword(string $username): string;

    /**
     * @param string $username
     * @return string
     */
    public function getUserAddress(string $username): string;

    /**
     * @param string $username
     * @return boolean
     */
    public function isUserRegistered(string $username): bool;

    /**
     * @param string $username
     * @param string $password
     * @param string $address
     * @return void
     */
    public function registerUser(string $username, string $password, string $address);

    /**
     * @param string $username
     * @return void
     */
    public function removeUser(string $username);

    /**
     * @return void
     */
    public function closeDatabase();
}