<?php
namespace D2ca\Models;

/**
 * @property int $id
 * @property int $clan_id
 * @property string $clan_name
 * @property string $members_updated_at
 *
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class Clan extends \Model
{
    public static $_table = 'clans';

    public function members()
    {
        return $this->has_many('Member', 'clan_id', 'clan_id');
    }
    public function members_count()
    {
        return $this->has_many('Member', 'clan_id', 'clan_id')->count();
    }
}