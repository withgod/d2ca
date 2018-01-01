<?php
require_once(realpath(__DIR__ . '/../src/bootstrap.php'));

if ($argc !== 3) {
    $logger->error('parameter error', ['argc' => $argc, 'argv' => $argv]);
    die('php update_members.php clan_id token');
}
try {
    /* @var $logger \Monolog\Logger */
    $logger = \D2ca\Helper::logger();


    $clan_id = (int)$argv[1];
    $token = $argv[2];
    $client = new \Destiny\Client(getenv('OAUTH_API_KEY'), $token);

    $classes = [
        '0' => 'Titan',
        '1' => 'Hunter',
        '2' => 'Warlock',
        '3' => 'Unknown',
    ];
    $tz = date_default_timezone_get();
    $tz_object = new DateTimeZone($tz);


    if (\D2ca\Helper::membersUpdateBatchCount($clan_id) > 1) {
        $tmp = `ps auxfw | grep [u]pdate_members.php | grep -v sudo | grep $clan_id`;
        $logger->error('update batch already running', [$tmp]);
        exit;
    }
    \D2ca\Helper::updateClanMembersUpdatedAt($clan_id);

    $user = $client->getCurrentBungieUser();
    $clan = $client->getGroup($clan_id);
    $members = $client->getClanMembers($clan_id);
    $logger->info("update_members.php sarted [" . $clan_id . '/' . $user->displayName() . '/' . $user->membershipId() . ']');

    # current members
    $members_d2_uid = [];
    /* @var $member \Destiny\Objects\GroupMember */
    foreach ($members as $member) {
        $dinfo = $member->destinyUserInfo();
        $binfo = $member->bungieNetUserInfo();
        $destiny_membershiptype = $dinfo->membershipType();
        $destiny_membershipid = $dinfo->membershipId();
        $destiny_name = $dinfo->displayName();
        $bungie_membershipid = 0;
        $bungie_name = '';
        if (!empty($binfo)) {
            $bungie_membershipid = $binfo->membershipId();
            $bungie_name = $binfo->displayName();
        } else {
            // happen
            $logger->info(sprintf("destiny user [%d:%s] is empty bungie account", $destiny_membershipid, $destiny_name));
        }
        /* @var $profile \Destiny\Objects\DestinyProfileResponse */
        $profile = $client->getProfile($dinfo->membershipType(), $dinfo->membershipId(), 100, 200);
        $characters = $profile->characters();
        /* @var $character \Destiny\Objects\DestinyCharacterComponent */
        list ($t_level, $t_played, $w_level, $w_played, $h_level, $h_played) = [0, '', 0, '', 0, ''];
        usort($characters, function($a, $b) {
            if ($a->dateLastPlayed() == $b->dateLastPlayed()) {
                return 0;
            }
            return ($a->dateLastPlayed() <= $b->dateLastPlayed()) ? -1 : 1;
        });
        foreach ($characters as $character) {
            $class = $character->classType() == NULL ? 0 : $character->classType();
            $class = $classes[$class];
            $level = $character->levelProgression()['level'];
            /* utc to local tz */
            $last_played = $character->dateLastPlayed();
            $last_played->setTimezone($tz_object);
            $played = $last_played->format('Y-m-d H:i:s');
            switch ($class) {
                case 'Titan':
                    $t_level = $level;
                    $t_played = $played;
                    break;
                case 'Warlock':
                    $w_level = $level;
                    $w_played = $played;
                    break;
                case 'Hunter':
                    $h_level = $level;
                    $h_played = $played;
                    break;
            }
        }

        $user_model = \Model::factory('Member')->where('d2_uid', $destiny_membershipid)->find_one();
        if (empty($user_model)) {
            // create one
            $user_model = \Model::factory('Member')->create();
        }

        $user_model->clan_id = $clan_id;
        $user_model->membership_types = $destiny_membershiptype;
        $user_model->d2_name = $destiny_name;
        $user_model->d2_uid = $destiny_membershipid;
        $user_model->bungie_name = $bungie_name;
        $user_model->bungie_uid = $bungie_membershipid;
        $user_model->titan_level = $t_level;
        $user_model->titan_last_played = $t_played;
        $user_model->warlock_level = $w_level;
        $user_model->warlock_last_played = $w_played;
        $user_model->hunter_level = $h_level;
        $user_model->hunter_last_played = $h_played;

        $user_model->save();
        $members_d2_uid[] = $destiny_membershipid;
    }
    $not_updated_members = \Model::factory('Member')
        ->where('clan_id', $clan_id)
        ->where_not_in('d2_uid', $members_d2_uid)
        ->where_raw('created_at <= NOW() - INTERVAL 5 MINUTE')
        ->find_many();
    $logger->info("delete leaved members.");
    $logger->info("current member list [" . count($members_d2_uid) . "]", $members_d2_uid);
    foreach ($not_updated_members as $member) {
        $logger->info("delete member", [$member->d2_name, $member->d2_uid, $member->created_at]);
        $member->set_expr('deleted_at', 'current_timestamp');
        $member->save();
    }

} catch(Exception $e) {
    $logger->error("update_members.php have problem", [$e->getMessage()]);
}
