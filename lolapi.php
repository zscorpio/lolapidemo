<?php
require_once 'Snoopy.class.php';
require_once 'filecache.php';
class LOLApi
{
    private $token;
    private $qquin;
    private $vaid;
    private $fileCache;

    public function __construct($token){
        $this->token = $token;
        $this->fileCache = new LeoFileCache('./cache');
    }

    public function setQquin($qquin){
        $this->qquin = $qquin;
    }

    public function setVaid($vaid){
        $this->vaid = $vaid;
    }

    public function getGameDetail($gameid){
        $file_key = "gameDetail".$gameid;
        $value = $this->fileCache->get($file_key);
        // var_dump($value);
        if($value){
            return json_encode($value);
        }
        $snoopy = new Snoopy; 
        $snoopy->rawheaders['DAIWAN-API-TOKEN']= $this->token;
        $link = "http://lolapi.games-cube.com/GameDetail?qquin=".$this->qquin."&vaid=".$this->vaid."&gameid=".$gameid;
        $snoopy->fetchtext($link);
        $result = $snoopy->results;
        $detail = json_decode($result,1);
        // var_dump($detail);
        if(!is_array($detail) || $detail['code'] != 0){
            return false;
        }else{
            $result = $detail['data'][0]['battle'];
            $gamer_records = array();
            foreach ($result['gamer_records'] as $key => $value) {
                $gamer_records[$value['qquin']] = $value;
            }
            $result['gamer_records'] = $gamer_records;
            $this->fileCache->set($file_key, $value=$result, $expire = 10*60);
            return $result;
        }
    }
}
