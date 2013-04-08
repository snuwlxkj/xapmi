<?php
class xap{
	private $xapfile,$xapinfo;
	static $Capabilities = array('ID_CAP_NETWORKING'=>'网络访问',
				 'ID_CAP_IDENTITY_DEVICE'=>'设备的信息',
				 'ID_CAP_IDENTITY_USER'=>'用户的匿名身份信息',
				 'ID_CAP_LOCATION'=>'位置信息',
				 'ID_CAP_SENSORS'=>'传感器',
				 'ID_CAP_MICROPHONE'=>'麦克风，用于录音',
				 'ID_CAP_MEDIALIB'=>'访问媒体库',
				 'ID_CAP_GAMERSERVICES'=>'XBox Live的一些服务',
				 'ID_CAP_PHONEDIALER'=>'拨打电话',
				 'ID_CAP_PUSH_NOTIFICATION'=>'推送消息',
				 'ID_CAP_WEBBROWSERCOMPONENT'=>'浏览器组件');
	public function __construct($xapfile){
		$this->xapfile = $xapfile;
		if(!file_exists($this->xapfile)) die('ERROR FILE NOT EXISTS!');
	}
	public function movexap($xappath = ''){
		$xappath = empty($xappath) ? 'xapmi.xap/'.date('ym').'/' : $xappath;
		is_dir($xappath) || mkdir($xappath,0777,true) || E(500);
		$savefile = $xappath.TIMESTAMP.random(5).'.file';
		return rename($this->xapfile,$savefile) ? true : false;
	}
	public function readZipFile($zfileName){
		if($zip = zip_open($this->xapfile)){
			while($zip_entry = zip_read($zip)){
				if($zfileName == zip_entry_name($zip_entry)){
					if(zip_entry_open($zip, $zip_entry)) return zip_entry_read($zip_entry,zip_entry_filesize($zip_entry));
					else return '';
				}
			}	
			zip_close($zip);			
		}
		return '';
	}
	public function parse(){
		$result = array('ProductID'=>'','Title'=>'','Version'=>'','AppPlatformVersion'=>'','Author'=>'','Description'=>'','Publisher'=>'','TokenID'=>'','BackgroundImageURI'=>'','Capability'=>array());
		$xml = $this->readZipFile('WMAppManifest.xml');
		if($xml){
			foreach($result as $k=>$v){
				switch($k){
					case 'ProductID':
						preg_match('/'.$k.'="\{(.*?)\}"/i',$xml,$m);
					break;
					case 'BackgroundImageURI':
						preg_match('/([^>]*)<\/BackgroundImageURI>/i',$xml,$m);
						break;
					case 'Capability':
						preg_match_all('/<Capability Name="(.*?)"/i',$xml,$m);
					break;
					default:
						preg_match('/'.$k.'="(.*?)"/i',$xml,$m);
				}
				if(!empty($m[1])) $result[$k] = $m[1];	
			}
		}
		$result['BackgroundImageURI'] = str_replace('\\','/',$result['BackgroundImageURI']);
		if(!empty($result['Capability'])){
			$tarr = array();
			foreach($result['Capability'] as $c) $tarr[$c] = isset(self::$Capabilities[$c]) ? self::$Capabilities[$c] : '';
			$result['Capability'] = $tarr;
		} 
		$this->xapinfo = $result;
		return $result;
	}
	public function saveIcon($iconPath = ''){
		if(empty($this->xapinfo['BackgroundImageURI'])) return false;
		$iconPath = $iconPath ? $iconPath : 'upload/ico/';
		is_dir($iconPath) || mkdir($iconPath,0777,true) || E(500);
		$savefile = $iconPath.TIMESTAMP.random(5).'.png';
		file_put_contents($savefile,$this->readZipFile($this->xapinfo['BackgroundImageURI']));
		return $savefile;
	}
}
define('TIMESTAMP', $_SERVER['REQUEST_TIME']);
function random($size = 6, $word = 0, $case = 0){
	$cand = '2345678';#不要0,1,9,G,L,O以免分不清楚
	$word && $cand .= 'ABCDEFHIJKMNPQRSTUVWXYZ' . $cand AND
	$case && $cand .= strtolower($cand);$clen = strlen($cand);
	$size > $clen && $cand = str_repeat($cand, ceil($size * 2 / $clen));
	return substr(str_shuffle($cand), 0, $size);
}
header('content-type:text/html;charset=utf-8');
$xap = new xap(dirname(__FILE__).'/xapfile/kuwoyinyue_v1.6.0.0.xap');
$info = $xap->parse();
#var_export($_SERVER);
#var_export($info);
function dec($m){
	return json_decode('"\\u'.dechex($m[1]).'"');
}
function uni_decode ($uncode)
{
    $word = preg_replace_callback('/&#(\d{2,});/', "dec", $uncode);
    return $word;
}
$url = 'http://www.windowsphone.com/zh-cn/store/app/'.urlencode($info['Title']).'/'.$info['ProductID'];
$con = file_get_contents($url);
echo uni_decode($con);
#echo $con;
#trigger_error('eroor',E_USER_NOTICE);
#$xap->saveIcon();
#$xap->movexap();
?>