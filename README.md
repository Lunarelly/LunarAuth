# LunarAuth
Functional and easy to use auth plugin.

## Commands
- /login
- /register
- /changepassword
- /userinfo
- /removeuser
- /lunarauth

## Features
- IP login
- Passwords encryption (you can disable it)
- MySQL, SQLite3, JSON, YAML support
- Chat auth (you can disable it)
- Full-customizible player's activity when not authorized
- Single auth: player can't join if there is another player with this nick online (you can disable it)
- Effects on join (you can disable it)
- Accidentally sent password into the chat will not be shown (you can disable it)
- Customizible passwords length, login timeout, max login attempts, messages interval
- Customizible messages (and usages)

## API
- PlayerAuthorizationEvent calls when player is authorized
```php

$auth = Server::getInstance()->getPluginManager()->getPlugin("LunarAuth");
$player = new Player();

if ($auth->isPlayerAuthenticated($player)) { # True | False
# TODO
}

if ($auth->isPlayerRegistered($player)) { # True | False
# TODO
}
```
