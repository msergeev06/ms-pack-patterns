<?php

namespace MSergeev\Packages\Patterns\Entity;

use MSergeev\Core\Entity\Query;
use MSergeev\Core\Lib as CoreLib;
use MSergeev\Packages\Kuzmahome\Tables\JobsTable;
use MSergeev\Packages\Patterns\Tables\PatternsTable;
use MSergeev\Packages\Patterns\Lib;
use MSergeev\Packages\Kuzmahome\Lib as KuzmaLib;

class Pattern
{
	/**
	 * @var int|null ID шаблона
	 */
	private $id = null;

	/**
	 * @var null|string Название шаблона
	 */
	private $title = null;

	/**
	 * @var int Приоритет шаблона
	 */
	private $priority = 100;

	/**
	 * @var null|string Паттерн шаблона
	 */
	private $pattern = null;

	/**
	 * @var null|int ID скрипта, исполняемого при совпадении шаблона
	 */
	private $scriptID = null;

	/**
	 * @var null|string Код, выполняемый при совпадении шаблона
	 */
	private $script = null;

	/**
	 * @var bool Флаг, обозначающий, что шаблон является контекстом
	 */
	private $is_context = false;

	/**
	 * @var int Время, которое контекст действует
	 */
	private $context_timeout = 0;

	/**
	 * @var null|string Код, выпоняемый при выходе из контекста
	 */
	private $context_timeout_script = null;

	/**
	 * @var bool Флаг, обозначающий, что необходимо игнорировать сообщения системы
	 */
	private $ignore_system=true;

	/**
	 * @var bool Флаг, обозначающий, что при совпадении шаблона, нужно прекращать обработку других шаблонов
	 */
	private $is_last = true;

	/**
	 * @var null|string Описание функциональности шаблона
	 */
	private $description = null;

	/**
	 * Конструктор класса
	 *
	 * @param array $arPattern Массив параметров шаблона
	 */
	public function __construct(array $arPattern)
	{
		//'ID','TITLE','PRIORITY','PATTERN','SCRIPT_ID','SCRIPT','IS_CONTEXT','TIME_LIMIT','SCRIPT_EXIT','SKIP_SYSTEM','IS_LAST','DESCRIPTION'
		$this->id = intval($arPattern['ID']);
		$this->title = $arPattern['TITLE'];
		if (isset($arPattern['PRIORITY']) && intval($arPattern['PRIORITY'])!=100)
		{
			$this->priority = intval($arPattern['PRIORITY']);
		}
		if (isset($arPattern['PATTERN']) && !is_null($arPattern['PATTERN']))
		{
			$this->pattern = $arPattern['PATTERN'];
		}
		if (isset($arPattern['SCRIPT_ID']) && !is_null($arPattern['SCRIPT_ID']))
		{
			$this->scriptID = $arPattern['SCRIPT_ID'];
		}
		if (isset($arPattern['SCRIPT']) && !is_null($arPattern['SCRIPT']))
		{
			$this->script = $arPattern['SCRIPT'];
		}
		if (isset($arPattern['IS_CONTEXT']) && $arPattern['IS_CONTEXT']===true)
		{
			$this->is_context = true;
			$this->context_timeout = intval($arPattern['TIME_LIMIT']);
			if (isset($arPattern['SCRIPT_EXIT']) && !is_null($arPattern['SCRIPT_EXIT']))
			{
				$this->context_timeout_script = $arPattern['SCRIPT_EXIT'];
			}
		}
		if (isset($arPattern['SKIP_SYSTEM']) && $arPattern['SKIP_SYSTEM']===false)
		{
			$this->ignore_system = false;
		}
		if (isset($arPattern['IS_LAST']) && $arPattern['IS_LAST']===false)
		{
			$this->is_last = false;
		}
		if (isset($arPattern['DESCRIPTION']) && !is_null($arPattern['DESCRIPTION']))
		{
			$this->description = $arPattern['DESCRIPTION'];
		}
	}

	public function getDescription()
	{
		return KuzmaLib\Say::clearMessage($this->description);
	}

	public function isContext ()
	{
		return $this->is_context;
	}

	public function getID ()
	{
		return intval($this->id);
	}

	public function getPriority()
	{
		return intval($this->priority);
	}

	public function getTitle ()
	{
		return $this->title;
	}

	/**
	 * Возвращает паттерн шаблона
	 *
	 * @access private
	 *
	 * @return null|string
	 */
	public function getPattern ()
	{
		if (!is_null($this->pattern))
		{
			return $this->pattern;
		}
		else
		{
			return $this->title;
		}
	}

	/**
	 * Возвращает время действия контекста шаблона
	 *
	 * @return int
	 */
	public function getContextTimeout ()
	{
		return intval($this->context_timeout);
	}

	/**
	 * Возвращает флаг последнего обрабатываемого шаблона
	 *
	 * @return bool
	 */
	public function isLast()
	{
		return $this->is_last;
	}

	/**
	 * Проверяет паттерн шаблона и запускает скрипт, при совпадении
	 *
	 * @param string $strMessage    Текст сообщения
	 * @param int    $iMemberID     ID пользователя ядра
	 *
	 * @return bool|string
	 */
	public function checkPattern($strMessage,$iMemberID)
	{
		//Если нужно игнорировать сообщения системы и говорит система - пропускаем обработку этого шаблона
		if ($this->ignore_system===true && $iMemberID==0) return false;

		$pattern = $this->getPattern();
		$lowerMess = mb_strtolower($strMessage,'UTF-8');

		if (preg_match('/'.$pattern.'/',$lowerMess,$m))
		{
			//Устанавливаем время запуска шаблона
			$this->setExecutedTime();
			if (!$this->is_context)
			{
				//Если шаблон не контекст
				if (!is_null($this->scriptID) && intval($this->scriptID)>0)
				{
					//Если указан ID скрипта, выполняем его
					$this->updateContext($iMemberID);
					KuzmaLib\Scripts::runScript(intval($this->scriptID));
					return 'script';
				}
				elseif ($script = $this->getScript ($m, $iMemberID))
				{
					//Если существует код, выполняем его
					$this->updateContext($iMemberID);
					$this->runScript($script);
					return 'script';
				}
			}
			else
			{
				//Если шаблон является контекстом, устанавливаем контекст пользователю
				Lib\Main::setActiveContext($this->id,$this->getContextTimeout(),$iMemberID);
				return 'restart';
			}
		}

		return false;
	}

	/**
	 * Выполняет скрипт контекста
	 *
	 * @param null|int $userID ID пользователя ядра
	 */
	public function runContextScript ($userID=null)
	{
		if (is_null($userID))
		{
			$userID = 0;
		}
		$script = $this->getScript(array(),$userID);
		$this->runScript($script);
	}

	/**
	 * Выполняет скрипт выхода из контекста
	 *
	 * @param null|int $userID ID пользователя ядра
	 *
	 * @return bool|mixed|null
	 */
	public function runClearContextScript ($userID=null)
	{
		if (is_null($userID))
		{
			$userID = 0;
		}
		$optionSayLevel = CoreLib\Options::getOptionInt('PATTERN_SAY_LEVEL');
		if (!$optionSayLevel) $optionSayLevel = 0;
		if (!is_null($this->context_timeout_script))
		{
			$script = str_replace('#PATTERN_SAY_LEVEL#',$optionSayLevel,$this->context_timeout_script);
			$script = str_replace('#USER_ID#',$userID,$script);

			$result = $this->runScript($script);

			return $result;
		}

		return false;
	}

	/**
	 * Устанавливает время совпадения шаблона
	 * @access private
	 */
	private function setExecutedTime ()
	{
		PatternsTable::update($this->id,array("VALUES"=>array("EXECUTED"=>date('d.m.Y H:i:s'))));
	}

	/**
	 * Продлевает время действия контекста шаблона
	 *
	 * @access private
	 * @param null|int $userID ID пользователя ядра
	 */
	private function updateContext ($userID=null)
	{
		if (is_null ($userID))
		{
			$userID = 0;
		}
		//'timer_active_context_user_'.$userID
		$arRes = JobsTable::getOne(
			array(
				'select' => array('ID'),
				'filter' => array('TITLE'=>'timer_active_context_user_'.$userID)
			)
		);
		if ($arRes)
		{
			$context = Lib\Main::getActiveContext($userID);
			$objContextPattern = Lib\Main::getPatternsList(null,false,$context)[0];
			$newRuntime = time()+$objContextPattern->context_timeout;
			$newExpire = $newRuntime+1800;
			JobsTable::update(
				$arRes['ID'],
				array(
					"VALUES"=>array(
						"RUNTIME"=>date('d.m.Y H:i:s',$newRuntime),
						"EXPIRE"=>date('d.m.Y H:i:s',$newExpire)
					)
				)
			);
		}
	}

	/**
	 * Выполняет скрипт
	 *
	 * @access private
	 * @param string $script Код скрипта
	 *
	 * @return mixed|null
	 */
	private function runScript ($script)
	{
		$result = null;
		if ($script)
		{
			try
			{
				$result = eval($script);
			}
			catch (\Exception $e)
			{
				KuzmaLib\Logs::debMes('Patterns script error: '.$e->getMessage().",\ncode: ".$script."\nresult: ".$result);
			}
		}

		return $result;
	}

	/**
	 * Возращает код скрипта, обработав #шаблоны#
	 *
	 * Возможные шаблоны:
	 * #PATTERN_SAY_LEVEL# - глобальный уровень сообщений шаблонов
	 * #USER_ID# - ID пользователя ядра
	 * #MATCHES# - сериализованный массив совпадений паттерна
	 * #MATCH_0#,#MATCH_1#,...#MATCH_N# - значения найденных совпадений паттерна шаблона
	 *
	 * TODO: Добавить возможность установки других #шаблонов#, например #global:System.minMsgLevel#
	 *
	 * @access private
	 * @param array $arMatches
	 * @param null  $userID
	 *
	 * @return bool|mixed
	 */
	private function getScript ($arMatches = array(), $userID = null)
	{
		if (is_null($userID))
		{
			$userID = 0;
		}
		$optionSayLevel = CoreLib\Options::getOptionInt('PATTERN_SAY_LEVEL');
		if (!$optionSayLevel) $optionSayLevel = 0;
		if (!is_null($this->script))
		{
			$script = str_replace('#PATTERN_SAY_LEVEL#',$optionSayLevel,$this->script);
			$script = str_replace('#USER_ID#',$userID,$script);
			if (!empty($arMatches))
			{
				foreach ($arMatches as $i=>$match)
				{
					$script = str_replace('#MATCH_'.$i.'#',$match,$script);
				}
				$script = str_replace('#MATCHES#',serialize($arMatches),$script);
			}
			return $script;
		}

		return false;
	}
}