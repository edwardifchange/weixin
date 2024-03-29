<?php
define("TOKEN", "edward1994");
$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        // $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
        //extract post data
        if (!empty($postStr)) {

            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $type = $postObj->MsgType;
            $customrevent = $postObj->Event;
            $latitude = $postObj->Location_X;
            $longitude = $postObj->Location_Y;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                            </xml>";
            switch ($type) {
                case "event";
                    if ($customrevent == "subscribe") {
                        $contentStr = "感谢你的关注！";
                    }
                    break;
                case "image";
                    $contentStr = "你的图片很棒！";
                    break;
                case "location";
                    $weatherurl = "http://api.map.baidu.com/telematics/v2/weather?location={$longitude},{$latitude}&ak=1a3cde429f38434f1811a75e1a90310c";
                    $apistr = file_get_contents($weatherurl);
                    $apiobj = simplexml_load_string($apistr);
                    $placeobj = $apiobj->currentCity;//读取城市
                    $todayobj = $apiobj->results->result[0]->date;//读取星期
                    $weatherobj = $apiobj->results->result[0]->weather;//读取天气
                    $windobj = $apiobj->results->result[0]->wind;//读取风力
                    $temobj = $apiobj->results->result[0]->temperature;//读取温度
                    $contentStr = "{$placeobj}{$todayobj}天气{$weatherobj}，风力{$windobj}，温度{$temobj}";
                    break;
                case "link";
                    $contentStr = "你的链接有病毒吧！";
                    break;
                case "text";

        
                    if(preg_match("/^1[34578]{1}\d{9}$/",$keyword)){
                        $res = json_decode(file_get_contents('http://mobsec-dianhua.baidu.com/dianhua_api/open/location?tel=' . $keyword), true);
                        if(isset($res['responseHeader']['status']) && $res['responseHeader']['status'] == '200'){
                            $contentStr = $res['response'][$keyword]['location'];
                        }
                    }elseif(strstr($keyword, '笑话')){
                        $time = 1418745237;
                        $rangPage = rand(1, 20);
                        $url = 'http://v.juhe.cn/joke/content/list.php?key=ded3491809b5c8474d670f5832b9f21e&page=1&pagesize=10&sort=asc&time='.$time;
                        
                        $apiData = json_decode(file_get_contents($url), true);
                        $rand = rand(0, 9);
                        $data = $apiData['result']['data'][$rand];
                        $res = $data['content'];
                        $contentStr = $res;
                    }else{
                        $weatherurl = "http://api.map.baidu.com/telematics/v2/weather?location={$keyword}&ak=1a3cde429f38434f1811a75e1a90310c";
                       $apistr = file_get_contents($weatherurl);
                       $apiobj = simplexml_load_string($apistr);
                       $placeobj = $apiobj->currentCity;//读取城市
                       $todayobj = $apiobj->results->result[0]->date;//读取星期
                       $weatherobj = $apiobj->results->result[0]->weather;//读取天气
                       $windobj = $apiobj->results->result[0]->wind;//读取风力
                       $temobj = $apiobj->results->result[0]->temperature;//读取温度
                       $contentStr = "你好！{$placeobj}{$todayobj}天气{$weatherobj}，风力{$windobj}，温度{$temobj}";
                    }
                    break;
                default;
                    $contentStr = "此项功能尚未开发";
            }
            $msgType = "text";
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;


        } else {
            echo "";
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}
