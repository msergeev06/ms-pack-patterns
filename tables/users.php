<?php

namespace MSergeev\Packages\Patterns\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;
use MSergeev\Core\Lib\TableHelper;

class UsersTable extends DataManager
{
	public static function getTableName()
	{
		return 'ms_patterns_users';
	}

	public static function getTableTitle ()
	{
		return 'Пользователи';
	}

	public static function getMap ()
	{
		return array(
			TableHelper::primaryField(),
			new Entity\IntegerField('USER_ID',array(
				'required' => true,
				'link' => 'ms_core_users.ID',
				'title' => 'ID пользователя в таблице пользователей ядра'
			)),
			new Entity\StringField('ACTIVE_CONTEXT_ID',array(
				'title' => 'ID активного контекста'
			)),
			new Entity\TextField('ACTIVE_CONTEXT_PARAMS',array(
				'serialized' => true,
				'title' => 'Параметры контекста'
			)), 
			new Entity\DatetimeField('ACTIVE_CONTEXT_UPDATED',array(
				'title' => 'Время обновления активного контекста'
			))
		);
	}

	public static function OnAfterCreateTable ()
	{
		$arUsers = \MSergeev\Core\Tables\UsersTable::getList(
			array(
				'select' => array('ID'=>'USER_ID'),
				'order' => array('ID'=>'ASC')
			)
		);
		static::add(array("VALUES"=>$arUsers));
	}
}