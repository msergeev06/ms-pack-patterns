<?php

namespace MSergeev\Packages\Patterns\Tables;

use MSergeev\Core\Lib\DataManager;
use MSergeev\Core\Entity;
use MSergeev\Core\Lib\TableHelper;

class PatternsTable extends DataManager
{
	public static function getTableName ()
	{
		return 'ms_patterns_patterns';
	}

	public static function getTableTitle ()
	{
		return 'Основные шаблоны';
	}

	public static function getMap ()
	{
		return array(
			TableHelper::primaryField(),
			new Entity\StringField('TITLE',array(
				'required' => true,
				'title' => 'Название шаблона, может содержать регулярное выражение'
			)),
			new Entity\IntegerField('PRIORITY',array(
				'required' => true,
				'default_value' => 100,
				'title' => 'Приоритет шаблона'
			)),
			new Entity\TextField('PATTERN',array(
				'title' => 'Регулярное выражение, если отсутствует или null - будет использовано название шаблона'
			)),
			new Entity\IntegerField('SCRIPT_ID',array(
				'link' => 'ms_kuzmahome_scripts.ID',
				'title' => 'ID скрипта'
			)),
			new Entity\TextField('SCRIPT',array(
				'title' => 'Программный код'
			)),
			new Entity\TextField('LOG',array(
				'title' => 'Лог'
			)),
			new Entity\DatetimeField('EXECUTED',array(
				'title' => 'DateTime, когда был исполнен'
			)),
			new Entity\BooleanField('IS_GLOBAL',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, является ли глобальным шаблоном'
			)),
			new Entity\BooleanField('IS_CONTEXT',array(
				'required' => true,
				'default_value' => false,
				'title' => 'Флаг, является ли контекстом'
			)),
			new Entity\IntegerField('TIME_LIMIT',array(
				'required' => true,
				'size' => 15,
				'default_value' => 0,
				'title' => 'Лимит времени контекста'
			)),
			new Entity\TextField('SCRIPT_EXIT',array(
				'title' => 'Код, выполняемый при выходе из контекста'
			)),
			new Entity\IntegerField('PARENT_ID',array(
				'title' => 'Родительский шаблон'
			)),
			new Entity\BooleanField('SKIP_SYSTEM',array(
				'required' => true,
				'default_value' => true,
				'title' => 'Пропускать сообщения системы'
			)),
			new Entity\BooleanField('IS_LAST',array(
				'required' => true,
				'default_value' => true,
				'title' => 'Флаг, является последним шаблоном'
			)),
			new Entity\StringField('DESCRIPTION',array(
				'title' => 'Описание возможностей'
			))
		);
	}
}