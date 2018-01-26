<?php
include_once 'Bet.class.php';
class Game extends WebLoginBase
{
    //验证是否开始投注
    public final function checkBuy()
    {
        $actionNo = "";
        if ($this->settings['switchBuy'] == 0) {
            $actionNo['flag'] = 1;
        }
        echo json_encode($actionNo);
    }
    //{{{ 投注
    public final function postCode(){
		$codes=$_POST['code'];
		$para=$_POST['para'];
		if($this->type) $para['type']=$this->type;
		$amount=0;$mincoin=0;$maxcount=0;$allNum=0;
		$arr4=array('30','29','15','23','27');
		$arr4id=array('9');
		$arr3=array('28','26','25','22','21','19','14','13','11','7');
		$arr3id=array('15','22','23','24','41','196','201','202','219');
		$arr2=array('24','20','18','17','12','10','9','6','5','3');
		$arr2id=array('30','35','36','213','214','208');
        
        $this->getSystemSettings();
        if ($this->settings['switchBuy'] == 0)
            throw new Exception('本平台已经停止购买！');
        if ($this->settings['switchDLBuy'] == 0 && $this->user['type'])
            throw new Exception('代理不能买单！');
        if ($this->settings['switchZDLBuy'] == 0 && ($this->user['parents'] == $this->user['uid']))
            throw new Exception('总代理不能买单！');
        if (count($codes) == 0)
            throw new Exception('请先选择号码再提交投注');
        //检查时间 期数
        if ($para['kjTime'] < $this->time)
            throw new Exception('提交数据出错,请刷新再投');
        $ftime      = $this->getTypeFtime(intval($para['type'])); //封单时间
        $actionTime = $this->getGameActionTime(intval($para['type'])); //当期时间
        $actionNo   = $this->getGameActionNo(intval($para['type'])); //当期期数
        if ($actionTime != $para['kjTime'])
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        if ($actionNo != $para['actionNo'])
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        if ($actionTime - $ftime < $this->time)
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        // 查检每注的赔率是否正常
        $this->getPlayeds();
        foreach ($codes as $code) {
            //检查时间 期数2
            $ftime2      = $this->getTypeFtime(intval($code['type'])); //封单时间2
            $actionTime2 = $this->getGameActionTime(intval($code['type'])); //当期时间2
            $actionNo2   = $this->getGameActionNo(intval($code['type'])); //当期期数2
            if ($actionTime2 != $para['kjTime'])
                throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
            if ($actionNo2 != $para['actionNo'])
                throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
            if ($actionTime - $ftime2 < $this->time)
                throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
            $played = $this->playeds[$code['playedId']];
            //检查开启
            if (!$played['enable'])
                throw new Exception('游戏玩法组已停,请刷新再投(1)');
            //检查ID
            if ($played['groupId'] != $code['playedGroup'])
                throw new Exception('提交数据出错，请重新投注1');
            if ($played['id'] != $code['playedId'])
                throw new Exception('提交数据出错，请重新投注2');
            //检查赔率
            $chkBonus = ($played['bonusProp'] - $played['bonusPropBase']) / $this->settings['fanDianMax'] * $this->user['fanDian'] + $played['bonusPropBase'] - ($played['bonusProp'] - $played['bonusPropBase']) * $code['fanDian'] / $this->settings['fanDianMax']; //实际奖金
            //if($code['bonusProp']>$played['bonusProp']) throw new Exception('提交奖金大于最大奖金，请重新投注');
            if ($code['bonusProp'] < $played['bonusPropBase'])
                throw new Exception('提交奖金小于最小奖金，请重新投注');
            if ($code['bonusProp'] == $played['bonusPropBase'] && $code['bonusProp'] == $played['bonusProp'] && $played['bonusPropBase'] == $played['bonusProp']) {
                if ($code['fanDian'] != 0)
                    throw new Exception('提交返点不正确');
            }
            //if(intval($chkBonus)!=intval($code['bonusProp'])) throw new Exception('提交奖金出错，请重新投注');
            //检查返点
            if (floatval($code['fanDian']) > floatval($this->user['fanDian']) || floatval($code['fanDian']) > floatval($this->settings['fanDianMax']))
                throw new Exception('提交返点出错，请重新投注');
            //检查倍数
            if (intval($code['beiShu']) < 1)
                throw new Exception('倍数只能为大于1正整数');
            //检查位数
            if (in_array($code['playedId'], $arr4id)) {
                //if(!in_array($code['weiShu'],$arr4)) throw new Exception('提交数据出错，请重新投注3');
            }
            if (in_array($code['playedId'], $arr3id)) {
                //if(!in_array($code['weiShu'],$arr3)) throw new Exception('提交数据出错，请重新投注4');
            }
            if (in_array($code['playedId'], $arr2id)) {
                //if(!in_array($code['weiShu'],$arr2)) throw new Exception('提交数据出错，请重新投注5');
            }
            //检查模式
            $mosi = array();
            if ($this->settings['yuanmosi'] == 1)
                array_unshift($mosi, '2.000');
            if ($this->settings['jiaomosi'] == 1)
                array_unshift($mosi, '0.200');
            if ($this->settings['fenmosi'] == 1)
                array_unshift($mosi, '0.020');
            if ($this->settings['limosi'] == 1)
                array_unshift($mosi, '0.002');
            if (!in_array($code['mode'], $mosi))
                throw new Exception('投注模式出错，请重新投注');
            // 检查注数
            if ($code['actionNum'] < 1)
                throw new Exception('注数不能小于1，请重新投注');
            if ($betCountFun = $played['betCountFun']) {
                if ($played['betCountFun'] == 'descar') {
                    if ($code['actionNum'] > Bet::$betCountFun($code['actionData']))
                        throw new Exception('下单失败,您投注号码不符合投注规则，请重新投注(1)');
                } else {
                    //echo $betCountFun;
                    if($code['actionNum']!=Bet::$betCountFun($code['actionData'],$code['actionNum']))
                        throw new Exception($code['actionNum'].'下单失败,您投注号码不符合投注规则，请重新投注(2)'.Bet::$betCountFun($code['actionData'])."/".$code['actionData']);
                }
            }

            //最大注数检查
            $maxcount   = $this->getmaxcount($code['playedId']);
            $playedname = $this->getplayedname($code['playedId']);
            if ($code['actionNum'] > $maxcount)
                throw new Exception('注数超过玩法:' . $playedname . '  最高注数:' . $maxcount . '注,请重新投注!');
            //最低消费金额计算
            $mincoin += $this->getmincoin($code['playedId']);
            //总注数计算
            $allNum += $code['actionNum'];
        }
        $code = current($codes);
        if (isset($para['actionNo']))
            unset($para['actionNo']);
        if (isset($para['kjTime']))
            unset($para['kjTime']);
        $para = array_merge($para, array(
            'actionTime' => $this->time,
            'actionNo' => $actionNo,
            'kjTime' => $actionTime,
            'actionIP' => $this->ip(true),
            'uid' => $this->user['uid'],
            'username' => $this->user['username'],
            'serializeId' => uniqid(),
         		'nickname'=>$this->user['nickname'],
                    'hmEnable'=>$_POST['is_combine']
		));
        $code = array_merge($code, $para);
        if ($zhuihao = $_POST['zhuiHao']) {
            $liqType = 102;
            $codes   = array();
            $info    = '追号投注';
            
            if (isset($para['actionNo']))
                unset($para['actionNo']);
            if (isset($para['kjTime']))
                unset($para['kjTime']);
            
            foreach (explode(';', $zhuihao) as $var) {
                list($code['actionNo'], $code['beiShu'], $code['kjTime']) = explode('|', $var);
                $code['kjTime'] = strtotime($code['kjTime']);
                $code['beiShu'] = abs($code['beiShu']);
                $actionNo       = $this->getGameNo($para['type'], $code['kjTime'] - 1);
                
                $ano = $this->getGameNo($code['type'], $this->time + $tps[$code['type']]['data_ftime']);
                if ($code['actionNo'] != $ano['actionNo']) {
                    list($dt1, $b1) = explode('-', $code['actionNo']); //提交的
                    list($dt2, $b2) = explode('-', $ano['actionNo']); //当前的
                    if ($dt2 < $dt1 || ($dt2 == $dt1 && $b2 < $b1)) {
                    } else {
                        throw new Exception('投注失败1：您追投注的第' . $ano['actionNo'] . '期已经过购买时间！');
                    }
                }
                if (strtotime($actionNo['actionTime']) - $ftime < $this->time)
                    throw new Exception('投注失败2：你追号投注第' . $code['actionNo'] . '已过购买时间');
                $amount += abs($code['actionNum'] * $code['mode'] * $code['beiShu']);
                $codes[] = $code;
            }
        } else {
            $liqType = 101;
            $info    = '投注';
            
            if ($actionNo != $code['actionNo'])
                throw new Exception('投注失败：你投注第' . $code['actionNo'] . '已过购买时间');
            foreach ($codes as $i => $code) {
                $codes[$i] = array_merge($code, $para);
                $amount += abs($code['actionNum'] * $code['mode'] * $code['beiShu']);
            }
        }
        
        //最低消费金额检查
        if ($amount < $mincoin)
            throw new Exception('本次投注方案总共:' . $allNum . '注,最低消费金额:' . $mincoin . '元,请重新投注!');
        // 查询用户可用资金
        $userAmount = $this->getValue("select coin from {$this->prename}members where uid={$this->user['uid']}");
        if ($userAmount < $amount)
            throw new Exception('您的可用资金不足，是否充值？(2)');
        // 开始事物处理
        $this->beginTransaction();
        try {
            foreach ($codes as $code) {
                //throw new Exception('error');
                unset($code['playedName']);
                // 插入投注表
                $code['wjorderId'] = $code['type'] . $code['playedId'] . $this->randomkeys(8 - strlen($code['type'] . $code['playedId']));
                $code['actionNum'] = abs($code['actionNum']);
                $code['mode']      = abs($code['mode']);
                $code['beiShu']    = abs($code['beiShu']);
                $code['amount']    =  abs($code['actionNum'] *$code['mode'] * $code['beiShu']);
                unset($code['dantuo']);
                $this->insertRow($this->prename . 'bets', $code);
                
                // 添加用户资金流动日志
                $this->addCoin(array(
                    'uid' => $this->user['uid'],
                    'type' => $code['type'],
                    'liqType' => $liqType,
                    'info' => $info,
                    'extfield0' => $this->lastInsertId(),
                    'extfield1' => $para['serializeId'],
                    'coin' => -$code['amount']
                ));
            }
            // 返点与积分等开奖时结算
            $sscname = $this->getValue("select shortName from {$this->prename}type where id={$code['type']}");
            $this->commit();
            return $this->user['username'] . '您好：<br>' . '您投注的' . $sscname . '第' . $code['actionNo'] . '期<br>金额为：' . $amount . '元<br>已投注成功！';
        }
        catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    //}}}
    
    //六合彩投注
    public final function postlhcData()
    {
        $codes = $_POST['code'];
        $para  = $_POST['para'];
        if ($this->type)
            $para['type'] = $this->type;
        $amount = 0;
        
        $this->getSystemSettings();
        if ($this->settings['switchBuy'] == 0)
            throw new Exception('游戏玩法已停止投注');
        if (count($codes) == 0)
            throw new Exception('请先买单再提交投注');
        //玩法开启
        if (!$this->getValue("select enable from {$this->prename}played where id=?", intval($para['playedId'])))
            throw new Exception('游戏玩法已停,请刷新再投(2)');
        //检查时间 期数
        $ftime      = $this->getTypeFtime(intval($para['type'])); //封单时间
        $actionTime = $this->getGameActionTime(intval($para['type'])); //当期时间
        $actionNo   = $this->getGameActionNo(intval($para['type'])); //当期期数
        if ($actionTime != $para['kjTime'])
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        if ($actionNo != $para['actionNo'])
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        if ($actionTime - $ftime < $this->time)
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        $code = current($codes);
        if (isset($para['actionNo']))
            unset($para['actionNo']);
        if (isset($para['kjTime']))
            unset($para['kjTime']);
        $para    = array_merge($para, array(
            'actionTime' => $this->time,
            'actionNo' => $actionNo,
            'kjTime' => $actionTime,
            'actionIP' => $this->ip(true),
            'uid' => $this->user['uid'],
            'username' => $this->user['username'],
            'serializeId' => uniqid(),
            'nickname' => $this->user['username']
        ));
        $code    = array_merge($code, $para);
        $liqType = 101;
        $info    = '投注';
        foreach ($codes as $i => $code) {
            //echo $code['bonusProp'].'!='.$this->getLHCRte($code['bonusPropName'],intval($para['playedId']));exit;
            if($code['bonusProp']!=$this->getLHCRte($code['bonusPropName'],intval($para['playedId']))) throw new Exception('奖金数值调用错误'.$code['bonusProp'].'-'.$code['bonusPropName']);
            if (isset($code['bonusPropName']))
                unset($code['bonusPropName']);
            //检查返点
            if ($code['fanDian'] != 0)
                throw new Exception('请勿改包！');
            $code['fanDian'] = 0;
            $code['mode']    = 1.00;
            //检查倍数
            if (intval($code['beiShu']) < 1)
                throw new Exception('倍数只能为大于1正整数');
            //检查金额
            $code['actionAmount'] = $code['actionNum'] * $code['mode'] * $code['beiShu'];
            //if(intval($code['actionNum']*$code['mode']*$code['beiShu'])!=intval($code['actionAmount'])) throw new Exception('提交数据出错，请重新投注');
            //throw new Exception('111');
            $codes[$i]            = array_merge($code, $para);
            $amount += abs($code['actionNum'] * $code['mode'] * $code['beiShu']);
        }
        if(empty($this->user['uid'])){
            throw new Exception('请先登录');
        }
        // 查询用户可用资金
        $userAmount = $this->getValue("select coin from {$this->prename}members where uid={$this->user['uid']}");
        if ($userAmount < $amount)
            throw new Exception('您的可用资金不足，请充值(3)');

        $amount1 = 0;
        // 开始事物处理
        $this->beginTransaction();
        try {
            foreach ($codes as $code) {
                // 插入投注表
                $code['wjorderId'] = $code['type'] . $code['playedId'] . $this->randomkeys(8 - strlen($code['type'] . $code['playedId']));
                $amount = abs($code['actionAmount']);
                //throw new Exception('222');
                $this->insertRow($this->prename . 'bets', $code);
                // 添加用户资金流动日志
                $this->addCoin(array(
                    'uid' => $this->user['uid'],
                    'type' => $code['type'],
                    'liqType' => $liqType,
                    'info' => $info,
                    'extfield0' => $this->lastInsertId(),
                    'extfield1' => $para['serializeId'],
                    'coin' => -$amount
                ));
                $amount1 +=abs($code['actionAmount']);
            }
            // 返点与积分等开奖时结算
            $sscname = $this->getValue("select shortName from {$this->prename}type where id={$code['type']}");
            $this->commit();
            return $this->user['username'] . '您好：<br>' . '您投注的' . $sscname . '第' . $code['actionNo'] . '期<br>金额为：' . $amount1 . '元<br>已投注成功！';
        }
        catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
	
	
	//28dd
    public final function post28ddData(){
        $codes = $_POST['code'];
        $para  = $_POST['para'];
        if ($this->type)
            $para['type'] = $this->type;
        $amount = 0;
        $this->getSystemSettings();
        if ($this->settings['switchBuy'] == 0)
            throw new Exception('游戏玩法已停止投注');
        if (count($codes) == 0)
            throw new Exception('请先买单再提交投注');
        //玩法开启
        // if (!$this->getValue("select enable from {$this->prename}played where id=?", intval($para['playedId'])))
            // throw new Exception('游戏玩法已停,请刷新再投(2)');
		
        //检查时间 期数
        $ftime      = $this->getTypeFtime(intval($para['type'])); //封单时间
        $actionTime = $this->getGameActionTime(intval($para['type'])); //当期时间
        $actionNo   = $this->getGameActionNo(intval($para['type'])); //当期期数
        if ($actionTime != $para['kjTime'])
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        if ($actionNo != $para['actionNo'])
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        if ($actionTime - $ftime < $this->time)
            throw new Exception('投注失败：你投注第' . $para['actionNo'] . '已过购买时间');
        $code = current($codes);
        if (isset($para['actionNo']))
            unset($para['actionNo']);
        if (isset($para['kjTime']))
            unset($para['kjTime']);
        $para    = array_merge($para, array(
            'actionTime' => $this->time,
            'actionNo' => $actionNo,
            'kjTime' => $actionTime,
            'actionIP' => $this->ip(true),
            'uid' => $this->user['uid'],
            'username' => $this->user['username'],
            'serializeId' => uniqid(),
            'nickname' => $this->user['username']
        ));
        $code    = array_merge($code, $para);
        $liqType = 101;
        $info    = '投注';
		$this->getPlayeds();
        foreach ($codes as $i => $code) {
            if($code['bonusProp']!=$this->playeds[intval($code['playedId'])]["bonusProp"]) throw new Exception('奖金数值调用错误');
			//检查返点
            if ($code['fanDian'] != 0)
                throw new Exception('请勿改包！');
            $code['fanDian'] = 0;
            $code['mode']    = 1.00;
            //检查倍数
			
            if (intval($code['beiShu']) < 1)
                throw new Exception('倍数只能为大于1正整数');
            //检查金额
            $code['actionAmount'] = $code['mode'] * $code['beiShu'];
			
            //if(intval($code['actionNum']*$code['mode']*$code['beiShu'])!=intval($code['actionAmount'])) throw new Exception('提交数据出错，请重新投注');
            //throw new Exception('111');
            $codes[$i]            = array_merge($code, $para);
            $amount += abs($code['mode'] * $code['beiShu']);
        };
        // 查询用户可用资金
        $userAmount = $this->getValue("select coin from {$this->prename}members where uid={$this->user['uid']}");
        if ($userAmount < $amount)
            throw new Exception('您的可用资金不足，请充值(3)');
        // 开始事物处理
        $this->beginTransaction();
        try {
            foreach ($codes as $code) {
                // 插入投注表
                $code['wjorderId'] = $code['type'] . $code['playedId'] . $this->randomkeys(8 - strlen($code['type'] . $code['playedId']));
                $code['amount'] = abs($code['mode'] * $code['beiShu']);
                $this->insertRow($this->prename . 'bets', $code);
                // 添加用户资金流动日志
                $this->addCoin(array(
                    'uid' => $this->user['uid'],
                    'type' => $code['type'],
                    'liqType' => $liqType,
                    'info' => $info,
                    'extfield0' => $this->lastInsertId(),
                    'extfield1' => $para['serializeId'],
                    'coin' => -$code['amount']
                ));
            }
            // 返点与积分等开奖时结算
            $sscname = $this->getValue("select shortName from {$this->prename}type where id={$code['type']}");
            $this->commit();
            return $this->user['username'] . '您好：<br>' . '您投注的' . $sscname . '第' . $code['actionNo'] . '期<br>金额为：' . $amount . '元<br>已投注成功！';
        }
        catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
	
	        /**
         * 跟买 未开奖 未过期
         */
       public final function follow_order($id,$beishu){
           
           $sql="select * from {$this->prename}bets where id={$id}";
		$return = $this->getRow($sql);
              $return['beiShu'] = $beishu;
                
//              var_dump($return);
		$amount=0;$mincoin=0;$maxcount=0;$allNum=0;
		$arr4=array('30','29','15','23','27');
		$arr4id=array('9');
		$arr3=array('28','26','25','22','21','19','14','13','11','7');
		$arr3id=array('15','22','23','24','41','196','201','202','219');
		$arr2=array('24','20','18','17','12','10','9','6','5','3');
		$arr2id=array('30','35','36','213','214','208');

		$this->getSystemSettings();
		if($this->settings['switchBuy']==0) throw new Exception('本平台已经停止购买！');
		if($this->settings['switchDLBuy']==0 && $this->user['type'])  throw new Exception('代理不能买单！');
		if($this->settings['switchZDLBuy']==0 && ($this->user['parents']==$this->user['uid']))  throw new Exception('总代理不能买单！');
		
		//检查时间 期数
		if($return['kjTime']<$this->time) throw new Exception('提交数据出错,请刷新再投');
		$ftime=$this->getTypeFtime(intval($return['type']));  //封单时间
		$actionTime=$this->getGameActionTime(intval($return['type']));  //当期时间
		$actionNo=$this->getGameActionNo(intval($return['type']));  //当期期数
		if($actionTime!=$return['kjTime'])  throw new Exception('投注失败：你投注第'.$return['actionNo'].'已过购买时间');
		if($actionNo!=$return['actionNo'])  throw new Exception('投注失败：你投注第'.$return['actionNo'].'已过购买时间');
		if($actionTime-$ftime<$this->time) throw new Exception('投注失败：你投注第'.$return['actionNo'].'已过购买时间');
		// 查检每注的赔率是否正常
		$this->getPlayeds();
		
                	//检查时间 期数2
		    $ftime2=$this->getTypeFtime(intval($return['type']));  //封单时间2
		    $actionTime2=$this->getGameActionTime(intval($return['type']));  //当期时间2
		    $actionNo2=$this->getGameActionNo(intval($return['type']));  //当期期数2
                 if($actionTime2!=$return['kjTime'])  throw new Exception('投注失败：你投注第'.$return['actionNo'].'已过购买时间');
		    if($actionNo2!=$return['actionNo'])  throw new Exception('投注失败：你投注第'.$return['actionNo'].'已过购买时间');
		    if($actionTime-$ftime2<$this->time) throw new Exception('投注失败：你投注第'.$return['actionNo'].'已过购买时间');
			$played=$this->playeds[$return['playedId']];
			//检查开启
			if(!$played['enable']) throw new Exception('游戏玩法组已停,请刷新再投');
            //检查ID
            if($played['groupId']!=$return['playedGroup']) throw new Exception('提交数据出错，请重新投注');
			if($played['id']!=$return['playedId']) throw new Exception('提交数据出错，请重新投注');
			//检查赔率
			$chkBonus=($played['bonusProp']-$played['bonusPropBase'])/$this->settings['fanDianMax']*$this->user['fanDian']+$played['bonusPropBase']-($played['bonusProp']-$played['bonusPropBase'])*$this->user['fanDian']/$this->settings['fanDianMax'];//实际奖金
			if($return['bonusProp']>$played['bonusProp']) throw new Exception('提交奖金大于最大奖金，请重新投注');
			if($return['bonusProp']<$played['bonusPropBase']) throw new Exception('提交奖金小于最小奖金，请重新投注');
			if($return['bonusProp']==$played['bonusPropBase'] && $return['bonusProp']==$played['bonusProp'] && $played['bonusPropBase']==$played['bonusProp']){
				if($code['fanDian']!=0) throw new Exception('提交返点不正确');
			}
//                        var_dump($chkBonus,$return['bonusProp']);
//			if(round($chkBonus)!=round($return['bonusProp'])) throw new Exception('提交奖金出错，请重新投注');
                        $return['bonusProp'] = $chkBonus;
                        
                        $return['fanDian'] = $this->user['fanDian'];
			//检查返点
//			if(floatval($return['fanDian'])>floatval($this->user['fanDian']) || floatval($return['fanDian'])>floatval($this->settings['fanDianMax'])) throw new Exception('提交返点出错，请重新投注');
			//检查倍数
			if(intval($return['beiShu'])<1) throw new Exception('倍数只能为大于1正整数');
			//检查位数
			if(in_array($return['playedId'],$arr4id)){
				if(!in_array($return['weiShu'],$arr4)) throw new Exception('提交数据出错，请重新投注');
			}
			if(in_array($return['playedId'],$arr3id)){
				if(!in_array($return['weiShu'],$arr3)) throw new Exception('提交数据出错，请重新投注');
			}
			if(in_array($return['playedId'],$arr2id)){
				if(!in_array($return['weiShu'],$arr2)) throw new Exception('提交数据出错，请重新投注');
			}
			//检查模式
			$mosi=array();
			if($this->settings['yuanmosi']==1) array_unshift($mosi,'2.000');
			if($this->settings['jiaomosi']==1) array_unshift($mosi,'0.200');
			if($this->settings['fenmosi']==1) array_unshift($mosi,'0.020');
			if($this->settings['limosi']==1) array_unshift($mosi,'0.002');
			if(!in_array($return['mode'],$mosi)) throw new Exception('投注模式出错，请重新投注');
			// 检查注数
			if($return['actionNum']<1) throw new Exception('注数不能小于1，请重新投注');
			if($betCountFun=$played['betCountFun']){
				if($code['actionNum']!=Bet::$betCountFun($code['actionData'])) throw new Exception('下单失败,您投注号码不符合投注规则，请重新投注'.Bet::$betCountFun($code['actionData']));
			}
			//最大注数检查
                           $maxcount=$this->getmaxcount($return['playedId']);
			$playedname=$this->getplayedname($return['playedId']);
                     if($return['actionNum']>$maxcount) throw new Exception('注数超过玩法:'.$playedname.'  最高注数:'.$maxcount.'注,请重新投注!2');
			//最低消费金额计算
			$mincoin+=$this->getmincoin($return['playedId']);
			//总注数计算
			$allNum+=$return['actionNum'];
                        

                $return['uid'] = $this->user['uid'];
                  $return['actionIP'] = $this->ip(true);
                    $return['actionTime'] = $this->time;
                      $return['username'] = $this->user['username'];
                      $return['nickname'] = $this->user['nickname'];
                       $return['serializeId'] = uniqid();
                       $return['hmEnable'] = 0;
//                var_dump($para);
		$code=array_merge($code, $para);
		
			$liqType=101;
			$info='投注';

            if($actionNo!=$return['actionNo'])  throw new Exception('投注失败：你投注第'.$code['actionNo'].'已过购买时间');
			
				$amount+=abs($return['actionNum']*$return['mode']*$return['beiShu']);
			
		

		//最低消费金额检查
		if($amount<$mincoin) throw new Exception('本次投注方案总共:'.$allNum.'注,最低消费金额:'.$mincoin.'元,请重新投注!');
		// 查询用户可用资金
		$userAmount=$this->getValue("select coin from {$this->prename}members where uid={$this->user['uid']}");
		if($userAmount<$amount) throw new Exception('您的可用资金不足，是否充值？');
		// 开始事物处理
		$this->beginTransaction();
		try{
			
                                unset($return['id']);
				// 插入投注表
				$return['wjorderId']=$return['type'].$return['playedId'].$this->randomkeys(8-strlen($return['type'].$return['playedId']));
				$return['actionNum']=abs($return['actionNum']);
				$return['mode']=abs($return['mode']);
				$return['beiShu']=abs($return['beiShu']);
				$amount=abs($return['actionNum']*$return['mode']*$return['beiShu']);
				$this->insertRow($this->prename .'bets', $return);
	
				// 添加用户资金流动日志
				$this->addCoin(array(
					'uid'=>$this->user['uid'],
					'type'=>$code['type'],
					'liqType'=>$liqType,
					'info'=>$info,
					'extfield0'=>$this->lastInsertId(),
					'extfield1'=>$para['serializeId'],
					'coin'=>-$amount,
				));
			
			// 返点与积分等开奖时结算

			$this->commit();
			return '投注成功';
		}catch(Exception $e){
			$this->rollBack();
			throw $e;
		}
        }
	
	
    //}}}
    
    public final function getNo($type)
    {
        $type     = intval($type);
        $actionNo = $this->getGameNo($type);
        if ($type == 1 && $actionNo['actionTime'] == '00:00:00') {
            $actionNo['actionTime'] = strtotime($actionNo['actionTime']) + 24 * 3600;
        } else {
            $actionNo['actionTime'] = strtotime($actionNo['actionTime']);
        }
        echo json_encode($actionNo);
    }
    /**
     * ajax取定单列表
     */
    public final function getOrdered($type = null)
    {
        $type = intval($type);
        if (!$this->type)
            $this->type = $type;
        $this->display('index/inc_game_order_history.php');
    }
    /**
     * {{{ ajax撤单
     */
    public final function deleteCode($id)
    {
        $id = intval($id);
        $this->beginTransaction();
        try {
            $sql = "select * from {$this->prename}bets where id=?";
            if (!$data = $this->getRow($sql, $id))
                throw new Exception('找不到定单。');
            if ($data['isDelete'])
                throw new Exception('这单子已经撤单过了。');
            if ($data['uid'] != $this->user['uid'])
                throw new Exception('这单子不是您的，您不能撤单。'); // 可考虑管理员能给用户撤单情况
            if ($data['kjTime'] <= $this->time)
                throw new Exception('已经开奖，不能撤单');
            if ($data['lotteryNo'])
                throw new Exception('已经开奖，不能撤单');
            
            // 冻结时间后不能撤单
            $this->getTypes();
            $ftime = $this->getTypeFtime($data['type']);
            if ($data['kjTime'] - $ftime < $this->time)
                throw new Exception('这期已经结冻，不能撤单');
            
            $amount = $data['beiShu'] * $data['mode'] * $data['actionNum'];
            $amount = abs($amount);
            // 添加用户资金变更日志
            $this->addCoin(array(
                'uid' => $data['uid'],
                'type' => $data['type'],
                'playedId' => $data['playedId'],
                'liqType' => 7,
                'info' => "撤单",
                'extfield0' => $id,
                'coin' => $amount
            ));
            
            // 更改定单为已经删除状态
            $sql = "update {$this->prename}bets set isDelete=1 where id=?";
            $this->update($sql, $id);
            $this->commit();
        }
        catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    //}}}
    
    /**
     * {{{ ajax批量撤单
     */
    public final function deleteAllCode($id)
    {
        $amount = 0;
        if ($id) {
            $id = str_replace('-', ',', $id);
        } else {
            throw new Exception('请选择要单号。');
        }
        $this->beginTransaction();
        try {
            $sql = "select * from {$this->prename}bets where id in({$id})";
            if (!$datas = $this->getRows($sql))
                throw new Exception('找不到定单。');
            //数据判断
            foreach ($datas as $data) {
                if ($data['isDelete'])
                    throw new Exception('里面有单子已经撤单过了。');
                if ($data['uid'] != $this->user['uid'])
                    throw new Exception('里面有单子不是您的，您不能撤单。');
                if ($data['kjTime'] < $this->time)
                    throw new Exception('里面有单子已经开奖，不能撤单');
                if ($data['lotteryNo'])
                    throw new Exception('已经开奖，不能撤单');
                if ($data['type'] == 34||$data['type'] == 77)
                    throw new Exception('对不起，六合彩不能撤单');
                // 冻结时间后不能撤单
                $this->getTypes();
                if ($data['kjTime'] - $this->types[$data['type']]['data_ftime'] < $this->time)
                    throw new Exception('里面有单子已经结冻，不能撤单');
            }
            //数据处理
            foreach ($datas as $data) {
                $amount = abs($data['beiShu']) * abs($data['mode']) * abs($data['actionNum']);
                // 添加用户资金变更日志
                $this->addCoin(array(
                    'uid' => $data['uid'],
                    'type' => $data['type'],
                    'playedId' => $data['playedId'],
                    'liqType' => 7,
                    'info' => "撤单",
                    'extfield0' => $data['id'],
                    'coin' => $amount
                ));
            }
            // 更改定单为已经删除状态
            $sql = "update {$this->prename}bets set isDelete=1 where id in({$id})";
            $this->update($sql);
            $this->commit();
        }
        catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    //}}}
    
    public function calcCount($codeList, $codeLen = 1)
    {
        if (!$codeList)
            return 0;
        $len = 0;
        foreach (explode('|', $codeList) as $codes) {
            $len += $this->_calcCount($codes, $codeLen);
        }
        return $len;
    }
    
    private function _calcCount($codeList, $codeLen = 1)
    {
        if (!$codeList)
            return 0;
        $len = 1;
        foreach (explode(',', $codeList) as $code) {
            $len *= strlen($code) / $codeLen;
        }
        return $len;
    }
}