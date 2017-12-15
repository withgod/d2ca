<?php
namespace D2ca\Models;

/**
 * @property int $id
 * @property int $clan_id
 * @property int $membership_types
 * @property string $d2_name
 * @property int $d2_uid
 * @property string $bungie_name
 * @property int $bungie_uid
 * @property int $titan_level
 * @property string $titan_last_played
 * @property int $warlock_level
 * @property string $warlock_last_played
 * @property int $hunter_level
 * @property string $hunter_last_played

 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class Member extends \Model
{
    public static $_table = 'members';

    public function clan() {
        return $this->has_one('Clan', 'clan_id');
    }

    public function last_played() {
        $arr = [$this->hunter_last_played, $this->titan_last_played, $this->warlock_last_played];
        rsort($arr);
        return $arr[0];
    }
}