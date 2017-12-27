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
    public function members_count_last_2week()
    {
        return $this->members_count_expr('interval -14 day');
    }
    public function members_count_last_week()
    {
        return $this->members_count_expr('interval -7 day');
    }
    public function members_count_last_month()
    {
        return $this->members_count_expr('interval -1 month');
    }
    public function members_count_expr($query = 'interval -1 day')
    {
        $query_raw = <<<EOT
(
(titan_last_played != "0000-00-00 00:00:00" and titan_last_played between date_add(CURRENT_DATE, $query) and CURRENT_DATE) or 
(warlock_last_played != "0000-00-00 00:00:00" and warlock_last_played between date_add(CURRENT_DATE, $query) and CURRENT_DATE) or 
(hunter_last_played != "0000-00-00 00:00:00" and hunter_last_played between date_add(CURRENT_DATE, $query) and CURRENT_DATE)
)
EOT;

        $count = $this->has_many('Member', 'clan_id', 'clan_id')
            ->where_raw($query_raw)
            ->count();

        return $count;
    }
}