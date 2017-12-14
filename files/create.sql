create database if not exists d2ca default character set utf8mb4;

use d2ca;

create table if not exists clans(
	id INTEGER PRIMARY KEY AUTO_INCREMENT,
	clan_id BIGINT not null UNIQUE,
	clan_name VARCHAR(255),
	members_updated_at TIMESTAMP DEFAULT '0000-00-00 00:00:00',
	created_at TIMESTAMP not null default current_timestamp,
	updated_at TIMESTAMP not null default current_timestamp on update current_timestamp,
	deleted_at TIMESTAMP default '0000-00-00 00:00:00'
);

create table if not exists members(
	id INTEGER primary key AUTO_INCREMENT,
	clan_id BIGINT not null,
	membership_types INTEGER NOT NULL,
	d2_name VARCHAR(255),
	d2_uid BIGINT default 0,
	bungie_uid BIGINT default 0,
	bungie_name VARCHAR(255),
	titan_level INTEGER default 0,
	titan_last_played TIMESTAMP,
	warlock_level INTEGER default 0,
	warlock_last_played TIMESTAMP,
	hunter_level INTEGER default 0,
	hunter_last_played TIMESTAMP,
	created_at TIMESTAMP not null default current_timestamp,
	updated_at TIMESTAMP not null default current_timestamp on update current_timestamp,
	deleted_at TIMESTAMP default '0000-00-00 00:00:00',
	FOREIGN KEY (clan_id) REFERENCES clans(clan_id)
);
