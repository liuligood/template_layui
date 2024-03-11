<?php

namespace backend\controllers;

use common\models\Order;

use common\services\goods\GoodsService;
use common\services\ShopService;
use Yii;
use common\base\BaseController;
use yii\db\Expression;
use yii\web\Response;


class OrderCountsShowController extends BaseController
{
    /**
     * @routeName day主页
     * @routeDescription day主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $datetime = time();
        $day = $this->findtime($datetime);
        if($req->isPost){
            $o = 0;
            $name = strtotime($req->post('name'));
            $sname = strtotime($req->post('sname'));
            $names = strtotime("+1 day",$name);
            $ci = ($name-$sname)/86400+1;
            $type = $req->post('type');
            $id = $req->post('id');
            $where = [];
            if(!empty($name)){
                $where['and'][] = ['>=', 'date', $sname];
                $where['and'][] = ['<=', 'date', $names];
            }
            if(!empty($id)){
                $where['shop_id'] = $id;
            }
            if(!empty($type)){
                $where['source'] = $type;
            }
            $day = [];
            $counts = Order::dealWhere($where)->select(new Expression("FROM_UNIXTIME(date,'%Y-%m-%d') date,count(*) cut"))->groupBy(new Expression("FROM_UNIXTIME(date,'%Y-%m-%d')"))->asArray()->all();
            for($i =0;$i<$ci;$i++){
                $time = date('Y-m-d',strtotime("+$i day",$sname));
                if($counts){
                    if($counts[$o]['date']==$time){
                        array_push($day,$counts[$o]['cut']);
                        if($o<count($counts)-1){$o +=1;}
                    }else{
                        array_push($day,'0');
                    }}else{
                    array_push($day,'0');
                }
            }
            $days = json_encode($day);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_SUCCESS, "传值成功", $days);
        }
        return $this->render('index',['days'=>$day,'datetime'=>$datetime]);

    }
    /**
 * @routeName mouth主页
 * @routeDescription mouth主页
 */
    public function actionMouthIndex()
    {
        $datetime = time();
        $mouths = $this->findmouth($datetime);
        $req = Yii::$app->request;
        if($req->isPost){
            $o = 0;
            $i = 1;
            $name = strtotime($req->post('name'));
            $sname = strtotime($req->post('sname'));
            $names = strtotime("+1 month",$name);
            $type = $req->post('type');
            $id = $req->post('id');
            $where = [];
            if(!empty($name)){
                $where['and'][] = ['>=', 'date', $sname];
                $where['and'][] = ['<', 'date', $names];
            }
            if(!empty($id)){
                $where['shop_id'] = $id;
            }
            if(!empty($type)){
                $where['source'] = $type;
            }
            $day = [];
            $counts = Order::dealWhere($where)->select(new Expression("FROM_UNIXTIME(date,'%Y-%m') date,count(*) cut"))->groupBy(new Expression("FROM_UNIXTIME(date,'%Y-%m')"))->asArray()->all();
            for($time =$sname;$time<$names;$time =strtotime("+$i month",$time)){
                $times = date('Y-m',$time);
                if($counts){
                    if($counts[$o]['date']==$times){
                        array_push($day,$counts[$o]['cut']);
                        if($o<count($counts)-1){$o +=1;}
                    }else{
                        array_push($day,'0');
                    }}else{
                    array_push($day,'0');
                }
            }
            $days = json_encode($day);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_SUCCESS, "传值成功", $days);
        }
        return $this->render('mindex',['mouths'=>$mouths,'datetime'=>$datetime]);
    }

    public function findtime($nowtime){
        $nowtime = strtotime('+1 day',$nowtime);
        $day = [];
        $o = 0;
        $lastday = $nowtime-86400*30;
        $where['and'][] = ['>=', 'date', $lastday];
        $where['and'][] = ['<', 'date', $nowtime];
        $counts = Order::dealWhere($where)->select(new Expression("FROM_UNIXTIME(date,'%Y-%m-%d') date,count(*) cut"))->groupBy(new Expression("FROM_UNIXTIME(date,'%Y-%m-%d')"))->asArray()->all();
        for($i =0;$i<30;$i++){
            $time = date('Y-m-d',strtotime("+$i day",$lastday));
            if($counts){
            if($counts[$o]['date']==$time){
                array_push($day,$counts[$o]['cut']);
                if($o<count($counts)-1){$o +=1;}
            }else{
                array_push($day,'0');
            }}else{
                array_push($day,'0');
            }
        }
        $days = json_encode($day);
        return $days;
    }
    public function findmouth($nowtime){
        $mouth = [];
        $where = [];
        $o = 0;
        $lastime = strtotime("-1 year",$nowtime);
        $mouthone = strtotime(date('Y-m',$lastime));
        $mouthlast = strtotime(date('Y-m',$nowtime));
        $mouthlasts = strtotime("+1 month",$mouthlast);
        $where['and'][] = ['>=', 'date', $mouthone];
        $where['and'][] = ['<', 'date', $mouthlasts];
        $counts = Order::dealWhere($where)->select(new Expression("FROM_UNIXTIME(date,'%Y-%m') date,count(*) cut"))->groupBy(new Expression("FROM_UNIXTIME(date,'%Y-%m')"))->asArray()->all();
        for($i =0;$i<=12;$i++){
            $time = date('Y-m',strtotime("+$i month",$lastime));
            if ($counts){
            if($counts[$o]['date']==$time){
                array_push($mouth,$counts[$o]['cut']);
                if($o<count($counts)-1){$o +=1;}
            }else{
                array_push($mouth,'0');
            }}else{
                array_push($mouth,'0');
            }
        }
        $mouth = json_encode($mouth);
        return $mouth;
    }

}