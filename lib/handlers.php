<?php

namespace MSergeev\Packages\Patterns\Lib;

use MSergeev\Core\Entity\Query;
use MSergeev\Core\Lib as CoreLib;
use MSergeev\Packages\Kuzmahome\Lib as KuzmaLib;
use MSergeev\Packages\Patterns\Tables\UsersTable;

class Handlers
{
	/**
	 * Обработчик события OnAfterSay пакета kuzmahome
	 *
	 * @param array $arMessage  Массив параметров сообщения
	 */
	public static function OnAfterSayHandler (array $arMessage)
	{
		/*
		$arRec = array(
			'MESSAGE' => $strPhrase,
			'DATETIME' => date('d.m.Y H:i:s'),
			'ROOM_ID' => intval($iRoomID),
			'MEMBER_ID' => intval($iMemberID),
			'SOURCE' => $strSource,
			'LEVEL' => intval($iLevel)
		);
		*/

		//Очищаем текст ссобщения от специальных символов
		$strMessage = KuzmaLib\Say::clearMessage($arMessage['MESSAGE']);
		//Сохраняем ID пользователя
		$iMemberID = intval($arMessage['MEMBER_ID']);
		//Сохраняем сообщение для последующего анализа
		if ($iMemberID!=0)
		{
			Main::saveMessageToFile($iMemberID,$strMessage);
		}

		//Получаем список шаблонов Глобального контекста и обрабатываем их
		$arPatterns = Main::getPatternsList(null,true);
		if ($arPatterns)
		{
			foreach ($arPatterns as $objPattern)
			{
				$result = $objPattern->checkPattern($strMessage,$iMemberID);
				if ($result == 'script')
				{
					//msDebug($result);
					if ($objPattern->isLast())
					{
						return;
					}
					else
					{
						continue;
					}
				}
			}
		}

		//Получаем текущий контекст для пользователя
		$context = Main::getActiveContext($iMemberID);

		//Получаем полный список шаблонов, либо список шаблонов-потомков текущего контекста
		$arPatterns = Main::getPatternsList($context);
		//msDebug($arPatterns);

		if ($context)
		{
			//Если установлен контекст, обрабатываем шаблоны-потомки
			foreach ($arPatterns as $objPattern)
			{
				$result = $objPattern->checkPattern($strMessage,$iMemberID);
				if ($result == 'script')
				{
					//Если было совпадение шаблона
					if ($objPattern->isLast())
					{
						//И шаблон обозначен как последний, прекращаем обработку
						return;
					}
					else
					{
						//Если шаблон НЕ обозначен как последний, проверяем остальные шаблоны
						continue;
					}
				}
				elseif ($result == 'restart')
				{
					//Если был изменен контекст на новый, запускаем обработчик заново, а при исполнении завершаем работу
					self::OnAfterSayHandler($arMessage);
					return;
				}
			}
			//Если шаблоны не совпали, выполняем скрипт текущего контекста
			$objContextPattern = Main::getPatternsList(null,false,$context)[0];
			$objContextPattern->runContextScript($iMemberID);
		}
		else
		{
			//Если контекст не установлен, обрабатываем все шаблоны
			foreach ($arPatterns as $objPattern)
			{
				$result = $objPattern->checkPattern($strMessage,$iMemberID);
				if ($result == 'script')
				{
					//Если было совпадение шаблона
					if ($objPattern->isLast())
					{
						//И шаблон обозначен как последний, прекращаем обработку
						return;
					}
					else
					{
						//Если шаблон НЕ обозначен как последний, проверяем остальные шаблоны
						continue;
					}
				}
				elseif ($result == 'restart')
				{
					//Если был изменен контекст на новый, запускаем обработчик заново, а при исполнении завершаем работу
					self::OnAfterSayHandler($arMessage);
					return;
				}
			}
		}
	}
}