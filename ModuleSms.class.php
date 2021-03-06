<?php
/**
 * 이 파일은 iModule SMS모듈의 일부입니다. (https://www.imodule.kr)
 *
 * SMS발송과 관련된 모든 기능을 제어한다.
 * 
 * @file /modules/sms/ModuleSms.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160910
 */
class ModuleSms {
	/**
	 * iModule 및 Module 코어클래스
	 */
	private $IM;
	private $Module;
	
	/**
	 * DB 관련 변수정의
	 *
	 * @private object $DB DB접속객체
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table;
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * SMS 발송을 위한 정보
	 */
	private $sender = null;
	private $receiver = null;
	private $message = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @param Module $Module Module 코어클래스
	 * @see /classes/iModule.class.php
	 * @see /classes/Module.class.php
	 */
	function __construct($IM,$Module) {
		/**
		 * iModule 및 Module 코어 선언
		 */
		$this->IM = $IM;
		$this->Module = $Module;
		
		/**
		 * 모듈에서 사용하는 DB 테이블 별칭 정의
		 * @see 모듈폴더의 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->send = 'sms_send_table';
	}
	
	/**
	 * 모듈 코어 클래스를 반환한다.
	 * 현재 모듈의 각종 설정값이나 모듈의 package.json 설정값을 모듈 코어 클래스를 통해 확인할 수 있다.
	 *
	 * @return Module $Module
	 */
	function getModule() {
		return $this->Module;
	}
	
	/**
	 * 모듈 설치시 정의된 DB코드를 사용하여 모듈에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db($this->getModule()->getInstalled()->database);
		return $this->DB;
	}
	
	/**
	 * 모듈에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * [코어] 사이트 외부에서 현재 모듈의 API를 호출하였을 경우, API 요청을 처리하기 위한 함수로 API 실행결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 *
	 * @param string $api API명
	 * @return object $datas API처리후 반환 데이터 (해당 데이터는 /api/index.php 를 통해 API호출자에게 전달된다.)
	 * @see /api/index.php
	 */
	function getApi($api) {
		$data = new stdClass();
		
		/**
		 * 이벤트를 호출한다.
		 */
		$this->IM->fireEvent('beforeGetApi','member',$api,$values,null);
		
		/**
		 * 모듈의 api 폴더에 $api 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/api/'.$api.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/api/'.$api.'.php';
		}
		
		/**
		 * 이벤트를 호출한다.
		 */
		$this->IM->fireEvent('afterGetApi','member',$api,$values,$data);
		
		return $data;
	}
	
	/**
	 * [코어] 알림메세지를 구성한다.
	 *
	 * @param string $code 알림코드
	 * @param int $fromcode 알림이 발생한 대상의 고유값
	 * @param array $content 알림데이터
	 * @return string $push 알림메세지
	 */
	function getPush($code,$fromcode,$content) {
		return null;
	}
	
	/**
	 * [사이트관리자] 모듈 설정패널을 구성한다.
	 *
	 * @return string $panel 설정패널 HTML
	 *
	function getConfigPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 *
		$IM = $this->IM;
		$Module = $this->getModule();
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/configs.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	*/
	
	/**
	 * [사이트관리자] 모듈 관리자패널 구성한다.
	 *
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this;
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/index.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->lang == null) {
			if (is_file($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getModule()->getPackage()->language && is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}
		
		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}
			
			if ($string != null) $returnString = $string;
		}
		
		/**
		 * 언어셋 텍스트가 없는경우 iModule 코어에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메세지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			case 'NOT_ALLOWED_SIGNUP' :
				if ($value != null && is_object($value) == true) {
					$description = $value->title;
				}
				break;
				
			case 'DISABLED_LOGIN' :
				if ($value != null && is_numeric($value) == true) {
					$description = str_replace('{SECOND}',$value,$this->getText('text/remain_time_second'));
				}
				break;
			
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		$error->type = 'BACK';
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * 발송자 정보를 입력한다.
	 *
	 * @param int $midx 발송회원고유번호 (없을경우 현재 로그인한 사용자)
	 * @param string $cellphone 발송번호 (없을경우 $midx 회원 전화번호)
	 */
	function setSender($midx=null,$cellphone=null) {
		$this->sender = new stdClass();
		$this->sender->midx = $midx ? $midx : $this->IM->getModule('member')->getLogged();
		$this->sender->cellphone = $cellphone ? $cellphone : $this->IM->getModule('member')->getMember($this->sender->midx)->cellphone;
		
		return $this;
	}
	
	/**
	 * 수신자 정보를 입력한다.
	 *
	 * @param int $midx 받는회원고유번호 (없을경우 현재 로그인한 사용자)
	 * @param string $cellphone 받는번호 (없을경우 $midx 회원 전화번호)
	 */
	function setReceiver($midx=null,$cellphone=null) {
		$this->receiver = new stdClass();
		$this->receiver->midx = $midx ? $midx : $this->IM->getModule('member')->getLogged();
		$this->receiver->cellphone = $cellphone ? $cellphone : $this->IM->getModule('member')->getMember($this->receiver->midx)->cellphone;
		
		return $this;
	}
	
	/**
	 * 발송내용을 입력한다.
	 *
	 * @param string $message
	 */
	function setMessage($message) {
		$this->message = $message;
		
		return $this;
	}
	
	/**
	 * 발송정보를 초기화한다.
	 */
	function reset() {
		$this->sender = null;
		$this->receiver = null;
		$this->message = null;
	}
	
	/**
	 * 메세지를 전송한다.
	 */
	function send($is_split_message=true,$is_fire_event=true) {
		if ($this->sender == null) {
			$this->reset();
			return 'WRONG_SENDER';
		}
		
		if ($this->receiver == null) {
			$this->reset();
			return 'WRONG_RECEIVER';
		}
		
		if ($this->message == null) {
			$this->reset();
			return 'WRONG_MESSAGE';
		}
		
		$this->sender->cellphone = preg_replace('/[^0-9]/','',$this->sender->cellphone);
		$this->receiver->cellphone = preg_replace('/[^0-9]/','',$this->receiver->cellphone);
		
		if (preg_match('/^0[0-9]{9,10}$/',$this->sender->cellphone) == false) {
			$this->reset();
			return 'WRONG_SENDER_CELLPHONE';
		}
		
		if (preg_match('/^0[0-9]{9,10}$/',$this->sender->cellphone) == false) {
			$this->reset();
			return 'WRONG_RECEIVER_CELLPHONE';
		}
		
		$oMessage = trim($this->message);
		
		while (true) {
			if ($is_split_message == true && $this->getMessageLength($oMessage) > 80) {
				$message = $this->getCutMessage($oMessage,80);
				$oMessage = trim(str_replace($message,'',$oMessage));
			} else {
				$message = $oMessage;
				$oMessage = '';
			}
			
			if ($is_fire_event == true) {
				$results = new stdClass();
				$results->success = false;
				
				$values = new stdClass();
				$values->sender = $this->sender;
				$values->receiver = $this->receiver;
				$values->message = $message;
				
				/**
				 * 이벤트를 호출한다.
				 */
				$this->IM->fireEvent('beforeDoProcess','sms','sending',$values,$results);
			}
			
			$this->db()->insert($this->table->send,array('frommidx'=>$values->sender->midx,'tomidx'=>$values->receiver->midx,'sender'=>$values->sender->cellphone,'receiver'=>$values->receiver->cellphone,'message'=>$message,'reg_date'=>time(),'status'=>$results->success == true ? 'SUCCESS' : 'FAIL'))->execute();
			
			
			if ($is_fire_event == true) {
				$values = new stdClass();
				$values->sender = $this->sender;
				$values->receiver = $this->receiver;
				$values->message = $message;
				
				/**
				 * 이벤트를 호출한다.
				 */
				$this->IM->fireEvent('afterDoProcess','sms','sending',$values,$results);
			}
			
			if ($oMessage == '') break;
		}
		
		$this->reset();
		return true;
	}
	
	/**
	 * 메세지의 길이를 가져온다.
	 *
	 * @param string $message
	 * @return int $length
	 */
	function getMessageLength($message) {
		return strlen(iconv('UTF-8','CP949//IGNORE',$message));
	}
	
	/**
	 * 메세지의 길이를 자른다.
	 *
	 * @param string $message 자를 메세지
	 * @param int $length 자를길이
	 * @return string $cutted
	 */
	function getCutMessage($message,$length) {
		for ($i=0, $loop=mb_strlen($message);$i<$loop;$i++) {
			if (strlen(iconv('UTF-8','CP949//IGNORE',trim(mb_substr($message,0,$i)))) == $length) {
				return trim(mb_substr($message,0,$i,'UTF-8'));
			} elseif (strlen(iconv('UTF-8','CP949//IGNORE',trim(mb_substr($message,0,$i)))) > $length) {
				return trim(mb_substr($message,0,$i-1,'UTF-8'));
			}
		}
		
		return $message;
	}
}
?>