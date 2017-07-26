<?php

namespace MSergeev\Packages\Patterns\Lib;

use MSergeev\Core\Lib as CoreLib;
use MSergeev\Packages\Patterns\Entity;
use MSergeev\Packages\Kuzmahome\Lib as KuzmaLib;
use MSergeev\Packages\Patterns\Tables;
use MSergeev\Core\Entity\Query;

class Main
{
	/**
	 * Возвращает ID текущего контекста для пользователя, либо false
	 *
	 * @param null|int $userID  ID пользователя ядра
	 *
	 * @return bool|int
	 */
	public static function getActiveContext ($userID = NULL)
	{
		if (is_null ($userID))
		{
			$userID = 0;
		}
		if ($userID==0)
		{
			$context = CoreLib\Options::getOptionStr('PATTERNS_SYSTEM_CONTEXT');
			if (!$context)
			{
				$context = false;
			}

			return $context;
		}

		$context = Tables\UsersTable::getOne (
			array (
				'select' => array ('ACTIVE_CONTEXT_ID'),
				'filter' => array ('USER_ID' => $userID)
			)
		);

		if ($context !== FALSE && !is_null ($context['ACTIVE_CONTEXT_ID']))
		{
			return intval($context['ACTIVE_CONTEXT_ID']);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Возвращает список объектов-шаблонов, либо пустой массив
	 * Если задан контекст, возвращает шаблоны-потомки
	 * Если указан флаг bGlobal - возвращает шаблоны глобального контекста
	 * Если указан ID возвращает список состоящий из 1 шаблона с заданным ID
	 *
	 * @param null|int $iContext    ID текущего контекста пользователя
	 * @param bool $bGlobal         Флаг глобального контекста
	 * @param null|int $ID          ID шаблона
	 *
	 * @return array
	 */
	public static function getPatternsList ($iContext=null,$bGlobal=false,$ID=null)
	{
		$arList = array(
			'select' => array(
				'ID','TITLE','PRIORITY','PATTERN','SCRIPT_ID','SCRIPT','IS_CONTEXT','TIME_LIMIT','SCRIPT_EXIT',
				'SKIP_SYSTEM', 'IS_LAST', 'DESCRIPTION'
			),
			'filter' => array('IS_GLOBAL'=>$bGlobal),
			'order' => array('PRIORITY'=>'DESC','ID'=>'ASC')
		);
		if (!is_null($ID) && intval($ID)>0)
		{
			$arList['filter']['ID'] = intval($ID);
		}
		else
		{
			if (!is_null($iContext) && $iContext !==false && intval($iContext)>0)
			{
				$arList['filter']['PARENT_ID'] = intval($iContext);
			}
			else
			{
				$arList['filter']['PARENT_ID'] = NULL;
			}
		}

		$arRes = Tables\PatternsTable::getList($arList);
		$arPatterns = array();
		if ($arRes)
		{
			foreach($arRes as $ar_res)
			{
				$arPatterns[] = new Entity\Pattern($ar_res);
			}
		}

		return $arPatterns;
	}

	/**
	 * Устанавливает контекст для пользователя и устанавливает таймер сброса контекста
	 *
	 * @param int  $contextID       ID текущего контекста пользователя
	 * @param int  $contextTimeout  Таймаут контекста в секундах
	 * @param null $userID          ID пользователя ядра
	 */
	public static function setActiveContext ($contextID, $contextTimeout = 0, $userID = NULL)
	{
		global $USER;
		if (is_null ($userID))
		{
			$userID = $USER->getID ();
		}
		if ($userID==0)
		{
			CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT',$contextID);
			CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_UPDATED',date('d.m.Y H:i:s'));
			KuzmaLib\Jobs::setTimeOut (
				'timer_active_context_user_'.$userID,
				self::strIncludePackage().'MSergeev\Packages\Patterns\Lib\Main::clearContext('.$userID.');',
				intval ($contextTimeout)
			);
			return;
		}

		$query = new Query('update');
		$sqlHelp = new CoreLib\SqlHelper(Tables\UsersTable::getTableName ());
		$sql = "UPDATE\n\t"
			.$sqlHelp->wrapTableQuotes ()."\nSET\n\t"
			.$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_ID')
			." = ".$contextID.",\n\t"
			.$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_UPDATED')
			." = '".date ('Y-m-d H:i:s')."'\n"
			."WHERE\n\t"
			.$sqlHelp->wrapFieldQuotes ('USER_ID')." =".$userID.";";
		$query->setQueryBuildParts ($sql);
		$res = $query->exec ();
		if ($res->getResult ())
		{
//			msEchoVar('setActiveContext: '.$contextID);
			KuzmaLib\Jobs::setTimeOut (
				'timer_active_context_user_'.$userID,
				self::strIncludePackage().'MSergeev\Packages\Patterns\Lib\Main::clearContext('.$userID.');',
				intval ($contextTimeout)
			);
		}
	}

	/**
	 * Возвращает массив параметров текущего контекста пользователя, либо false
	 *
	 * @param null|int $userID  ID пользователя ядра
	 *
	 * @return bool
	 */
	public static function getActiveContextParams ($userID = null)
	{
		if (is_null($userID))
		{
			$userID = 0;
		}
		if ($userID == 0)
		{
			$params = CoreLib\Options::getOptionStr('PATTERNS_SYSTEM_CONTEXT_PARAMS');
			if ($params!='null')
			{
				return unserialize($params);
			}
			else
			{
				return false;
			}
		}
		else
		{
			$arRes = Tables\UsersTable::getOne(
				array(
					'select' => array('ACTIVE_CONTEXT_PARAMS'),
					'filter' => array('USER_ID'=>$userID)
				)
			);
			if ($arRes)
			{
				return $arRes['ACTIVE_CONTEXT_PARAMS'];
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Устанавливает параметры контекста пользователя, если массив параметров пуст - устанавливает null
	 *
	 * @param null|int  $userID
	 * @param array $arParams
	 */
	public static function setActiveContextParams ($userID=null,$arParams=array())
	{
		if (is_null($userID))
		{
			$userID = 0;
		}
		if ($userID == 0)
		{
			if (!empty($arParams))
			{
				CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_PARAMS',serialize($arParams));
				CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_UPDATED',date('d.m.Y H:i:s'));
			}
			else
			{
				CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_PARAMS','null');
				CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_UPDATED',date('d.m.Y H:i:s'));
			}
		}
		else
		{
			$query = new Query('update');
			$sqlHelp = new CoreLib\SqlHelper(Tables\UsersTable::getTableName ());
			$sql = "UPDATE\n\t"
				.$sqlHelp->wrapTableQuotes ()."\nSET\n\t";
			if (!empty($arParams))
			{
				$params = serialize($arParams);
				$sql.=$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_PARAMS')
					." = '".$params."',\n\t";
			}
			else
			{
				$sql.=$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_PARAMS')
					." = NULL,\n\t";
			}
			$sql.=$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_UPDATED')
				." = '".date ('Y-m-d H:i:s')."'\n"
				."WHERE\n\t"
				.$sqlHelp->wrapFieldQuotes ('USER_ID')." =".$userID.";";
			$query->setQueryBuildParts ($sql);
			$query->exec ();
		}
	}

	/**
	 * Сбрасывает контекст пользователя.
	 *
	 * @param null|int $userID  ID пользователя ядра
	 * @param bool $bRunScript  Флаг необходимости выполнения скрипта выхода из контекста
	 */
	public static function clearContext ($userID = NULL, $bRunScript = true)
	{
//		msEchoVar('clearActiveContext for user: '.$userID);
		if (is_null($userID))
		{
			$userID = 0;
		}
		KuzmaLib\Jobs::clearTimeOut ('timer_active_context_user_'.$userID);
		if ($bRunScript)
		{
			self::runClearContextScript($userID);
		}
		if ($userID == 0)
		{
			CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT','null');
			CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_PARAMS','null');
			CoreLib\Options::setOption('PATTERNS_SYSTEM_CONTEXT_UPDATED',date('d.m.Y H:i:s'));
		}
		else
		{
			$query = new Query('update');
			$sqlHelp = new CoreLib\SqlHelper(Tables\UsersTable::getTableName ());
			$sql = "UPDATE\n\t"
				.$sqlHelp->wrapTableQuotes ()."\nSET\n\t"
				.$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_ID')
				." = NULL,\n\t"
				.$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_PARAMS')
				." = NULL,\n\t"
				.$sqlHelp->wrapFieldQuotes ('ACTIVE_CONTEXT_UPDATED')
				." = '".date ('Y-m-d H:i:s')."'\n"
				."WHERE\n\t"
				.$sqlHelp->wrapFieldQuotes ('USER_ID')." =".$userID.";";
			$query->setQueryBuildParts ($sql);
			$query->exec ();
		}
	}

	/**
	 * Выполняет скрипт выхода из текущего контекста
	 *
	 * @param null|int $userID  ID пользователя ядра
	 */
	public static function runClearContextScript ($userID=null)
	{
		global $USER;
		if (is_null($userID))
		{
			$userID = $USER->getID();
		}
		$context = self::getActiveContext($userID);
		$arPatterns = self::getPatternsList(null,false,$context);
		if ($context)
		{
			foreach ($arPatterns as $objPattern)
			{
				$objPattern->runClearContextScript($userID);
			}
		}
	}

	/**
	 * Сохраняет исходное сообщение в лог-файл для последующего анализа
	 *
	 * @param int       $userID     ID пользователя ядра
	 * @param string    $strMessage Исходное сообщение
	 */
	public static function saveMessageToFile($userID, $strMessage)
	{
		$logsDir = KuzmaLib\Logs::getLogsDir();
		$f1 = fopen($logsDir.'commands-'.date('Y-m').'.txt','a');
		fwrite($f1, '[user-'.$userID.'] '.$strMessage."\n");
		fclose($f1);
	}

	public static function showPatternsListView ()
	{
		?>
		<table class="table table-bordered">
			<tbody>
			<tr>
				<td align="center"><b>Название</b> (Приоритет)</td>
				<td>&nbsp;</td>
			</tr>
			<? $arPatterns = self::getPatternsList(null,true); ?>
			<?if($arPatterns):?>
				<tr><td valign="top" colspan="2" style="text-align: center"><i>Глобальные шаблоны</i></td></tr>
				<?foreach($arPatterns as $objPattern):?>
					<tr>
						<td><b><?=$objPattern->getTitle()?></b> [<?=$objPattern->getPattern()?>] (<?=$objPattern->getPriority()?>)<br><i><?=$objPattern->getDescription()?></i></td>
						<td>
							<?/*<a href="?action=moveup&id=<?=$objPattern->getID()?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-arrow-up"></i></a>
							<a href="?action=movedown&id=<?=$objPattern->getID()?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-arrow-down"></i></a>*/?>
							<a href="edit.php?id=<?=$objPattern->getID()?>" title="Редактировать" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-pencil"></i></a>
							<a href="?mode=delete&id=<?=$objPattern->getID()?>" onclick="return confirm('Вы уверены? Пожалуйста, подтвердите операцию.')" title="Удалить" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-remove"></i></a>
						</td>
					</tr>
					<?if ($objPattern->isContext()):?>
						<? self::showParentPatternsListView($objPattern); ?>
					<?endif;?>
				<?endforeach;?>
			<?endif;?>
			<? $arPatterns = self::getPatternsList();?>
			<?if($arPatterns):?>
				<tr><td valign="top" colspan="2" style="text-align: center"><i>Стандартные шаблоны</i></td></tr>
				<?foreach($arPatterns as $objPattern):?>
					<tr>
						<td><b><?=$objPattern->getTitle()?></b> [<?=$objPattern->getPattern()?>] (<?=$objPattern->getPriority()?>)<br><i><?=$objPattern->getDescription()?></i></td>
						<td>
							<?/*<a href="?action=moveup&id=<?=$objPattern->getID()?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-arrow-up"></i></a>
							<a href="?action=movedown&id=<?=$objPattern->getID()?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-arrow-down"></i></a>*/?>
							<a href="edit.php?id=<?=$objPattern->getID()?>" title="Редактировать" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-pencil"></i></a>
							<a href="?mode=delete&id=<?=$objPattern->getID()?>" onclick="return confirm('Вы уверены? Пожалуйста, подтвердите операцию.')" title="Удалить" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-remove"></i></a>
						</td>
					</tr>
					<?if ($objPattern->isContext()):?>
						<? self::showParentPatternsListView($objPattern); ?>
					<?endif;?>
				<?endforeach;?>
			<?endif;?>
			</tbody>
		</table>
		<?
	}

	public static function showParentPatternsListView (Entity\Pattern $objPattern)
	{
		$arParentPatterns = self::getPatternsList($objPattern->getID());
		if ($arParentPatterns)
		{
			?>
			<tr>
				<td valign="top" colspan="2" style="padding-left:50px">
					<table width="100%" border="0">
						<tbody>
						<?foreach($arParentPatterns as $objParentPattern):?>
							<tr>
								<td><b><?=$objParentPattern->getTitle()?></b> [<?=$objParentPattern->getPattern()?>] (<?=$objParentPattern->getPriority()?>)<br><i><?=$objParentPattern->getDescription()?></i></td>
								<td>
									<?/*<a href="?action=moveup&id=<?=$objParentPattern->getID()?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-arrow-up"></i></a>
									<a href="?action=movedown&id=<?=$objParentPattern->getID()?>" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-arrow-down"></i></a>*/?>
									<a href="edit.php?id=<?=$objParentPattern->getID()?>" title="Редактировать" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-pencil"></i></a>
									<a href="?mode=delete&id=<?=$objParentPattern->getID()?>" onclick="return confirm('Вы уверены? Пожалуйста, подтвердите операцию.')" title="Удалить" class="btn btn-default btn-sm"><i class="glyphicon glyphicon-remove"></i></a>
								</td>
							</tr>
							<?if ($objParentPattern->isContext()):?>
								<? self::showParentPatternsListView($objParentPattern); ?>
							<?endif;?>
						<?endforeach;?>
						</tbody>
					</table>
				</td>
			</tr>
		<?
		}
	}

	/**
	 * Возвращает строку с кодом подключения пакета
	 *
	 * @access private
	 *
	 * @return string
	 */
	private static function strIncludePackage()
	{
		return 'MSergeev\Core\Lib\Loader::IncludePackage("patterns");';
	}

}