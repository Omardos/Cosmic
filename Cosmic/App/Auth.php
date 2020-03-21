<?php
namespace App;

use App\Models\Admin;
use App\Models\Ban;
use App\Models\Log;
use App\Models\Core;
use App\Models\RememberedLogin;
use App\Models\Permission;
use App\Models\Player;

use Core\Locale;
use Core\Session;

use Library\Json;

class Auth
{
    public static function login(Player $player)
    {
        self::banExists($player);
        session_regenerate_id(true);

        if (in_array('housekeeping', array_column(Permission::get($player->rank), 'permission'))) {
            Log::addStaffLog('-1', 'Staff logged in: ' . request()->getIp(), $player->id, 'LOGIN');
        }

        Session::set(['player_id' => $player->id, 'ip_address' => request()->getIp()]);
        Player::update($player->id, ['ip_current' => request()->getIp(), 'last_online' => time()]);

        return $player;
    }

    public static function banExists($player)
    {
        $ban = Ban::getBanByUserId($player->id, request()->getIp());
        if($ban) {
            response()->json(["status" => "error", "message" => Locale::get('core/notification/banned_1').' ' . $ban->ban_reason . '. '.Locale::get('core/notification/banned_2').' ' . \App\Core::timediff($ban->ban_expire, true)]);
        }
    }

    public static function logout()
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function maintenance()
    {
        return Core::settings()->maintenance ?? false;
    }
}
