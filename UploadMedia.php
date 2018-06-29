<?php 
define("UPLOAD_FILE_PATH", "/wwwdata/wxapp/");
#require_once 'TokenUntil.php';
class UploadMedia {
    private function upMedia($filefullname,$type,$acsToken){
        //$appid = WCHAT_APPID;
	//$appsecret = WCHAT_SECRET_KEY;
	//$token= new TokenUntil();
    	//$url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$token->getAccessToken($appid,$appsecret,"Wchat").'&type='.$type;
        $url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$acsToken.'&type='.$type;
        $file = realpath($filefullname); //要上传的文件
        $fields['media'] = '@'.$file;
        $ch = curl_init($url) ;
        curl_setopt ($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch) ;
        if (curl_errno($ch)) {
         return curl_error($ch);
        }
        curl_close($ch);
        $res = json_decode($result);
        $mediaid=$res->media_id;
        return $mediaid;
    } 
    public function getMediaId($speech,$acsToken){
        $filenamestr= '%s'.rand(0,100).date("YmdHis").'.mp3';
    	$filefullname = sprintf($filenamestr, UPLOAD_FILE_PATH);
    	file_put_contents($filefullname,$speech,FILE_APPEND);
    	$mediaid=$this->upMedia($filefullname,'voice',$acsToken);
        unlink($filefullname);
    	return $mediaid;
    }
}
?>
