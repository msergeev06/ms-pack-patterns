<?php

namespace MSergeev\Packages\Patterns\Lib;

use MSergeev\Core\Lib as CoreLib;
use MSergeev\Packages\Kuzmahome\Lib as KuzmaLib;

/**
 * Class Patterns
 * Класс обработчиков шаблонов
 *
 * @package MSergeev\Packages\Patterns\Lib
 */
class Patterns
{
	/**
	 * Обработчик шаблона Голосовое напоминание
	 *
	 * @param string    $strSay Текст сообщения
	 * @param null|int  $userID ID пользователя ядра
	 */
	public static function noticeHandling ($strSay,$userID=null)
	{
		if (is_null($userID))
		{
			$userID = 0;
		}

		$lowerMess = mb_strtolower($strSay,'UTF-8');
		if (preg_match('/^напомни$/',$lowerMess,$m))
		{
			KuzmaLib\Say::sayPattern('О чём нап+омнить н+ужно?');
		}
		else
		{
			$arContextParams = Main::getActiveContextParams($userID);
			//msDebug($arContextParams);
			if (is_null($arContextParams))
			{
				//Первый заход, был вопрос о чём напомнить
				if (preg_match('/((Н|н)апомни)?[ ]?(.*)/',$lowerMess,$m))
				{
					//фраза содержится в $m[3]
					$phrase = $m[3];
					KuzmaLib\Say::sayPattern('Напомн+ить '.$phrase.'. Да или нет');
					$arContextParams = array('STEP'=>1,'NOTICE_TEXT'=>$phrase);
					Main::setActiveContextParams($userID,$arContextParams);
				}
			}
			else
			{
				//Параметры уже существуют
				if ($arContextParams['STEP']==1)
				{
					//Если спрашивает правильная ли фраза напоминания
					if (preg_match('/((Д|д)а)|((Н|н)ет)/',$lowerMess,$m))
					{
						if (count($m)==5)
						{
							//msEchoVar('Нет');
							//Если нет, снова нужно спрашивать о чем напомнить
							KuzmaLib\Say::sayPattern('О чём нап+омнить н+ужно?');
							$arContextParams = array();
							Main::setActiveContextParams($userID,$arContextParams);
						}
						else
						{
							//msEchoVar('Да');
							KuzmaLib\Say::sayPattern('Когд+а н+ужно нап+омнить?');
							$arContextParams['STEP'] = 2;
							Main::setActiveContextParams($userID,$arContextParams);
						}
					}
				}
				elseif ($arContextParams['STEP']==2)
				{
					//через 5 минут
					//через 3 часа
					//через 2 часа и 30 минут
					//через 10 секунд
					//сегодня вечером
					//завтра утром
					//в понедельник
					//Если спрашивает когда нужно напомнить
					$res = KuzmaLib\DateTime::recognizeTime($strSay, $strSay2);
					if ($res)
					{
						$text = 'Напоминаю';
						if ($userID!=0 && $userID!=2)
						{
							$object = KuzmaLib\Users::getUserObject($userID);
							if ($object)
							{
								$userName = KuzmaLib\Objects::getGlobal($object.'.propFullName');
								if ($userName)
								{
									$text = $userName.', напоминаю';
								}
							}
						}
						$time = $res-time();
						KuzmaLib\Say::sayPattern('Напомин+ание устан+овлено');
						KuzmaLib\Jobs::setTimeOut(
							'timer_pattern_notice_'.time(),
							'sayPattern("'.$text.': '.$arContextParams['NOTICE_TEXT'].'");',
							$time
						);
						Main::clearContext($userID);
					}
					else
					{
						KuzmaLib\Say::sayPattern('Я не п+онял когд+а н+ужно нап+омнить');
					}
				}
			}
		}

	}

	public static function rusLoto ()
	{
		$arNumberNames = array(
			1 => 'кол',
			2 => array('одна маленькая утка','пара','гусь','лебедь'),
			3 => array('трое','на троих','троица'),
			4 => 'стул',
			5 => 'отличник',
			7 => array('счастливая семерка','кочерга','топор'),
			8 => 'витушка',
			10=> 'бычий глаз',
			11=> array('ноги','барабанные палочки'),
			12=> array('дюжина','один и два'),
			13=> 'чертова дюжина',
			14=> 'день святого валентина',
			20=> 'гусь на тарелке',
			21=> 'очко',
			22=> array('утята','все двойки'),
			24=> 'две дюжины',
			25=> 'опять 25',
			33=> array('все тройки','33 зуба'),
			36=> 'три дюжины',
			38=> '38 попугаев',
			41=> 'ем один',
			44=> array('все четверки','стульчики'),
			48=> array('четыре дюжины','сено косим','половинку просим'),
			50=> array('полвека','полста','полтинник'),
			55=> 'все пятёрки',
			60=> 'пять дюжин',
			66=> array('все шестерки','валенки','салазки','девяносто девять'),
			69=> 'туда-сюда',
			77=> array('все семерки','костыли','топорики','семён семёныч'),
			80=> 'бабушка',
			84=> 'семь дюжин',
			88=> array('все восьмёрки','матрёшки','витушки'),
			89=> 'дедушкин сосед',
			90=> 'дедушка'
		);

	}

	public static function wikiWhat ($serializedMatches, $userID=null)
	{
		$optionSayLevel = CoreLib\Options::getOptionInt('PATTERN_SAY_LEVEL');

		$arMatches = unserialize($serializedMatches);
		if (is_null($userID))
		{
			$userID = 0;
		}

		//msDebug($arMatches);

		if (isset($arMatches[3]))
		{
			$request = $arMatches[3]; //получаем искомое слово
		}
		else
		{
			$request = $arMatches[2]; //получаем искомое слово
		}
		$space_replace = preg_match_all("#\s#isu", $request, $s); //проверяем, есть ли пробелы в запросе
		if ($space_replace === 1) { //если есть
			$request = preg_replace("#\s#", '_', $request); //меняем их на _
		}
		$request = str_replace(' ','_',$request);

		$url = 'https://ru.wikipedia.org/w/api.php?action=opensearch&search='.$request.'&format=xml'; //формируем запрос
		$ch = curl_init(); //инициируем curl
		curl_setopt($ch, CURLOPT_URL, $url); //передаем url
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); //возвращаем результат в виде строки
		curl_setopt($ch, CURLOPT_USERAGENT, 'MyBot/1.0 (http://www.mysite.com/)'); //имитируем браузер
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //отключаем проверку ssl-сертификата узла

		$result = curl_exec($ch); //выполяем curl

		if ($result)
		{
			$cachedDir = KuzmaLib\Files::getCachedDir();
			$xmlFileName = $cachedDir.'user_'.$userID.'.xml';
			$data = fopen($xmlFileName, 'w'); //открываем файл для записи
			fputs($data, $result); //записываем результат выполнения
			fclose($data); //закрываем

			$data_xml = simplexml_load_file($xmlFileName); //загружаем его и раскладываем на массив
			$text = $data_xml->Section[0]->Item[0]->Text[0]; //получаем первый найденный вариант
			//msEchoVar($text);
			$description = $data_xml->Section[0]->Item[0]->Description[0]; //получаем определение слова
			$description = mb_convert_encoding($description, 'UTF-8', 'UTF-8'); //конвертируем utf-8 без bom в простой utf-8
			$description = str_replace('а́','+а',$description);
			$description = str_replace('е́','+е',$description);
			$description = str_replace('у́','+у',$description);
			$description = str_replace('я́','+я',$description);
			$description = str_replace('и́','+и',$description);
			$description = str_replace('о́','+о',$description);
			$description = str_replace('И́','+И',$description);
			$description = str_replace('«','',$description);
			$description = str_replace('»','',$description);
			$description = preg_replace('/ \(.*\)/','',$description,1);
			//msEchoVar($description);

			KuzmaLib\Say::sayPattern($description);
			return;
		}

		KuzmaLib\Say::sayPattern("Я не знаю такого слова.",$optionSayLevel);
	}
}