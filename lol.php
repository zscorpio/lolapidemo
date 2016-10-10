<?php
	require_once 'Snoopy.class.php';
	require_once 'config.php';
	require_once 'filecache.php';
	require_once 'lolapi.php';
	$fileCache = new LeoFileCache('./cache');
	// $fileCache->flush();
	$lolapi = new LOLApi(DAIWAN_API_TOKEN);
	$areaArray = array("艾欧尼亚","比尔吉沃特","祖安","诺克萨斯","班德尔城","德玛西亚","皮尔特沃夫","战争学院","弗雷尔卓德","巨神峰","雷瑟守备","无畏先锋","裁决之地","黑色玫瑰","暗影岛","恕瑞玛","钢铁烈阳","水晶之痕","均衡教派","扭曲丛林","教育网专区","影流","守望之海","征服之海","卡拉曼达","巨龙之巢","皮城警备");
	$mapArray = array(
		'11'	=> "召唤师峡谷",
		'12'	=> "嚎哭深渊"
	);
	$gameTypeArray = array('未知0','自定义','新手关','匹配赛','排位赛','战队赛','大乱斗','人机','统治战场','大对决','未知10','克隆赛','未知12','未知13','无限火力','镜像赛','末日赛','飞升赛','六杀丛林','魄罗乱斗','互选征召','佣兵赛');
	$gameTypeArray[24] = '无限乱斗';

	function getRank($tier,$queue){
		$tierArray = array("最强王者","钻石","铂金","黄金","白银","青铜");
		$queueArray = array("I","II","III","IⅤ","V");
		$imageArray = array("Challenger","Diamond","Platinum","Gold","Silver","Bronze");
		if($tier == 255 && $queue == 255){
			return array("rank"=>"无段位","image"=>"NoRank");
		}else{
			return array("rank"=>$tierArray[$tier].$queueArray[$queue],"image"=>$imageArray[$tier]);
		}
	}

	if( isset($_GET['api']) ){
		$api = $_GET["api"];
		if($api == 'uinfo'){
			if( isset($_GET['name']) ){
				$name = $_GET['name'];
				$file_key = 'UserArea'.$api.$name;
				$value = $fileCache->get($file_key);
				if($value){
					echo json_encode($value);
					exit();
				}
				$snoopy = new Snoopy; 
				$snoopy->rawheaders['DAIWAN-API-TOKEN']= DAIWAN_API_TOKEN;
				$link = "http://lolapi.games-cube.com/UserArea?keyword=".urlencode($name);
				$snoopy->fetchtext($link);
				$result = $snoopy->results;
				$data = json_decode($result,1);
				if($data['code'] != 0){
					$last = array("code" => $data['code'],'msg'=>$data['msg']);
					echo json_encode($last);
				}else{
					$uinfo = $data["data"][0];
					$rank = getRank($uinfo["tier"],$uinfo["queue"]);

					$snoopy = new Snoopy; 
					$snoopy->rawheaders['DAIWAN-API-TOKEN']= DAIWAN_API_TOKEN;
					$link = "http://lolapi.games-cube.com/UserExtInfo?qquin=".$uinfo["qquin"]."&vaid=".$uinfo["area_id"];
					$snoopy->fetchtext($link);
					$result = $snoopy->results;
					$extraInfo = json_decode($result,1);
					$last = array(
						'code'			=> 0,
						'data'			=> array(
							'avatar'		=>	"http://ddragon.leagueoflegends.com/cdn/6.18.1/img/profileicon/".$uinfo["icon_id"].".png",
							'name'			=> $uinfo["name"],
							'qquin'			=> $uinfo["qquin"],
							'vaid'			=> $uinfo["area_id"],
							"area"			=> $areaArray[$uinfo["area_id"]-1],
							"rank"			=> $rank["rank"],
							"win_point"		=> $uinfo["win_point"],
							"rank_image"	=> $rank["image"],
							"champion_num"	=> $extraInfo["data"][3]["champion_num"],
							"penta_kills"	=> $extraInfo["data"][1]["penta_kills"],
							"god_like_num"	=> $extraInfo["data"][1]["god_like_num"],
							"total_match_mvps"	=> $extraInfo["data"][2]["total_match_mvps"]
						)
					);
					$fileCache->set($file_key, $value=$last, $expire = 365*24*60*60);
					echo json_encode($last);
				}
			}else{
				$last = array("code" => 1);
				echo json_encode($last);
			}
		}
		if($api == 'gameDetail'){
			if( isset($_GET['qquin']) && isset($_GET['vaid']) && isset($_GET['game_id']) ){
				$lolapi->setVaid($_GET['vaid']);
				$lolapi->setQquin($_GET['qquin']);
				$detail = $lolapi->getGameDetail($_GET['game_id']);
				$result = array();
				$result['team'] = array();
				// $detail = json_decode($detail,1);
				$result['start_time'] = $detail['start_time'];
				$result['duration'] = $detail['duration'];
				$result['game_type_value'] = $gameTypeArray[$detail['game_type']];
				foreach ($detail['gamer_records'] as $key => $value) {
					if($value['team'] == 100){
						$team = 0;
					}
					if($value['team'] == 200){
						$team = 1;
					}
					$result['team'][$team][] = array(
						'qquin'	=>				$value['qquin'],
						'name'	=> 				$value['name'],
						'champion_id'	=> 		$value['champion_id'],
						'level'	=> 				$value['level'],
						'item0'	=> 				$value['item0'],
						'item1'	=> 				$value['item1'],
						'item2'	=> 				$value['item2'],
						'item3'	=> 				$value['item3'],
						'item4'	=> 				$value['item4'],
						'item5'	=> 				$value['item5'],
						'item6'	=> 				$value['item6'],
						'champions_killed'	=> 	$value['champions_killed'],
						'num_deaths'	=> 		$value['num_deaths'],
						'assists'	=> 			$value['assists'],
						'total_damage_dealt_to_champions'	=> 			$value['total_damage_dealt_to_champions'],
					);
				}
				$last = array(
					'code'			=> 0,
					'data'			=> $result
				);
				echo json_encode($last);
			}else{
				$last = array("code" => 1);
				echo json_encode($last);
			}
		}
		if($api == 'CombatList'){
			if( isset($_GET['qquin']) && isset($_GET['vaid']) && isset($_GET['p']) ){
				$save_file = true;
				$qquin = $_GET['qquin'];
				$lolapi->setVaid($_GET['vaid']);
				$lolapi->setQquin($_GET['qquin']);
				$file_key = 'CombatList'.$api.$_GET['qquin'].$_GET['vaid'].$_GET['p'];
				$value = $fileCache->get($file_key);
				if($value){
					echo json_encode($value);
					exit();
				}
				$snoopy = new Snoopy; 
				$snoopy->rawheaders['DAIWAN-API-TOKEN']= DAIWAN_API_TOKEN;
				$link = "http://lolapi.games-cube.com/CombatList?qquin=".$_GET["qquin"]."&vaid=".$_GET["vaid"]."&p=".$_GET["p"];
				$snoopy->fetchtext($link);
				$result = $snoopy->results;
				$CombatList = json_decode($result,1);
				$res = $CombatList["data"][0]["battle_list"];
				foreach ($res as $key => $value) {
					$res[$key]['game_type_value'] = $gameTypeArray[$value['game_type']];
					$res[$key]['champion_avatar'] = 'http://cdn.tgp.qq.com/pallas/images/champions_id/'.$value['champion_id'].'.png';
					$res[$key]['game_result'] = $value['win'] == 1?true:false;
					$detail = $lolapi->getGameDetail($value['game_id']);
					if(!isset($detail['gamer_records'])){
						$save_file = false;
						$res[$key]['detail'] = array(
							'kill'		=> 0,
							'assists'	=> 0,
							'death'		=> 0,
						);
					}else{
						$res[$key]['detail'] = array(
							'kill'		=> $detail['gamer_records'][$qquin]['champions_killed'],
							'assists'	=> $detail['gamer_records'][$qquin]['assists'],
							'death'		=> $detail['gamer_records'][$qquin]['num_deaths'],
						);
					}
				}
				if($CombatList['code'] != 0){
					$last = array("code" => 1);
					echo json_encode($last);
				}else{
					$last = array(
						'code'			=> 0,
						'data'			=> $res
					);
					if($save_file){
						$fileCache->set($file_key, $value=$last, $expire = 365*24*60*60);
					}
					echo json_encode($last);
				}
			}else{
				$last = array("code" => 1);
				echo json_encode($last);
			}
		}
	}