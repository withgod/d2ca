<?php

namespace D2ca;


use D2ca\Models\Member;

class Helper
{
    /* @var $logger \Monolog\Logger */
    static $logger = null;
    /* @var $provider \League\OAuth2\Client\Provider\GenericProvider */
    static $provider = null;

    public static function logger()
    {
        if (static::$logger == null) {
            $logger = new \Monolog\Logger('d2ca');
            if (PHP_SAPI === 'cli') {
                $formatter = new \Monolog\Formatter\LineFormatter(null, null, true);
                $stream = new \Monolog\Handler\StreamHandler(sprintf("%s/logs/cli.%s.log", APP_ROOT, date("Ymd")), getenv('LOG_LEVEL'));
                $stream->setFormatter($formatter);

                $logger->pushHandler($stream);

                // console detection
                if(defined("STDERR") && posix_isatty(STDERR)){
                    /* console output */
                    $console = new \Monolog\Handler\StreamHandler(STDOUT, getenv('LOG_LEVEL'));
                    $console->setFormatter($formatter);

                    $logger->pushHandler($console);
                }
            } else {
                $prefix = 'unknown';
                if (strstr(PHP_SAPI, 'apache') !== false || PHP_SAPI === 'fpm-fcgi') {
                    $prefix = 'app';
                }
                $output = "[%datetime%] %remote_address% %request_method% %request_uri% %level_name%: %message% %context% %extra%\n";
                $formatter = new \Monolog\Formatter\LineFormatter($output, null, true);

                $stream = new \Monolog\Handler\StreamHandler(sprintf("%s/logs/%s.%s.log", APP_ROOT, $prefix, date("Ymd")), getenv('LOG_LEVEL'));
                $stream->setFormatter($formatter);
                $logger->pushProcessor(function ($record) {
                    $record['remote_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                    $record['request_uri'] = $_SERVER['REQUEST_URI'] ?? '-1';
                    $record['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'None';

                    return $record;
                });
                $logger->pushHandler($stream);
            }

            static::$logger = $logger;
        }

        return static::$logger;
    }

    public static function provider()
    {
        if (static::$provider == null) {
            $provider = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId' => getenv('OAUTH_CLIENT_ID'),    // The client ID assigned to you by the provider
                'clientSecret' => getenv('OAUTH_CLIENT_SECRET'),   // The client password assigned to you by the provider
                'redirectUri' => getenv('OAUTH_REDIRECT_URL'),
                'urlAuthorize' => 'https://www.bungie.net/en/OAuth/Authorize',
                'urlAccessToken' => 'https://www.bungie.net/platform/app/oauth/token/',
                'urlResourceOwnerDetails' => ''
            ]);
            static::$provider = $provider;
        }
        return static::$provider;
    }

    public static function membersUpdateBatchCount($clan_id)
    {
        $result = `ps auxfw | grep [u]pdate_members.php | grep $clan_id | grep -v sudo | wc -l`;
        return trim($result);
    }

    public static function updateClanMembersUpdatedAt($clan_id)
    {
        $clan = \Model::factory('Clan')->where('clan_id', $clan_id)->find_one();
        $clan->set_expr('members_updated_at', 'NOW()');
        $clan->save();
    }
    public static function createClan(array $clan)
    {
        $clan_id = $clan['groupId'];
        $clan_name = $clan['name'];

        $exists = \Model::factory('Clan')->where('clan_id', $clan_id)->count();
        if (empty($exists)) {
            $clan = \Model::factory('Clan')->create();
            $clan->clan_name = $clan_name;
            $clan->clan_id = $clan_id;
            $clan->save();
        }
    }
    public static function getMembers($clan_id)
    {

        $members = \Model::factory('Member')->where('clan_id', $clan_id)->find_many();
        $result = [];
        foreach ($members as $member) {
            /* @var $member Member */
            $tmp = $member->as_array();
            if ($tmp['titan_last_played'] == '0000-00-00 00:00:00') {
                $tmp['titan_last_played'] = null;
            }
            if ($tmp['warlock_last_played'] == '0000-00-00 00:00:00') {
                $tmp['warlock_last_played'] = null;
            }
            if ($tmp['hunter_last_played'] == '0000-00-00 00:00:00') {
                $tmp['hunter_last_played'] = null;
            }

            $tmp['all_last_played'] = $member->last_played();
            //ar_dump([$tmp, $last_played_all]);
            $result[] = $tmp;
        }

        return $result;
    }

    public static function getClan($clan_id)
    {
        $clan = \Model::factory('Clan')->where('clan_id', $clan_id)->find_one();
        if (!empty($clan)) {
            $tmp = $clan->as_array();
            $tmp['members_count'] = $clan->members_count();
            $tmp['members_count_last_week'] = $clan->members_count_last_week();
            $tmp['members_count_last_month'] = $clan->members_count_last_month();
            return $tmp;
        } else {
            return NULL;
        }
    }
    public static function getClans()
    {
        $clans = \Model::factory('Clan')->find_many();
        $result = [];
        foreach ($clans as $clan) {
            $tmp = $clan->as_array();
            $tmp['members_count'] = $clan->members_count();
            $tmp['members_count_last_week']  = $clan->members_count_last_week();
            $tmp['members_count_last_2week'] = $clan->members_count_last_2week();
            $tmp['members_count_last_month'] = $clan->members_count_last_month();
            $result[] = $tmp;
        }

        return $result;
    }

    public static function updateMembers($clan, $token)
    {
        $clan_id = $clan['groupId'];
        $clan_model = \Model::factory('Clan')->where('clan_id', $clan_id)->find_one();
        $one_hours_ago = date_create('1 hours ago');
        //$one_hours_ago = date_create('10 mins ago');
        $last_update = date_create($clan_model->members_updated_at);
        //var_dump([$clan_model->members_updated_at, $last_update, $one_hours_ago, $one_hours_ago > $last_update]);

        static::$logger->debug("updateMembers[ $clan_id, $token ]", ['one_hours_ago' => $one_hours_ago, 'last_update' => $last_update]);
        if ($one_hours_ago > $last_update) {
            static::$logger->info(sprintf("updated_at[%s] update clan[%d] members", $last_update->format('Y-m-d H:i:s'), $clan_id));
            $bin = APP_ROOT . '/bin/update_members.php';
            $cmd = "nohup php $bin $clan_id '$token' > /dev/null &";
            static::$logger->debug("executing [" . $cmd . "]");
            exec($cmd);
        }
    }
}
