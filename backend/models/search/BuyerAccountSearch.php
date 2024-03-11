<?php
namespace backend\models\search;

use common\models\BuyerAccount;
use Yii;

class BuyerAccountSearch extends BuyerAccount
{

    public $start_become_member_time;
    public $end_become_member_time;
    public $start_amount = '';
    public $end_amount = '';
    public $start_ext_no = '';
    public $end_ext_no = '';
    public $member = '';

    public function rules()
    {
        return [
            [['platform','member'], 'integer'],
            [['start_amount', 'end_amount'], 'number'],
            [['buyer_id','ext_no','amazon_account','start_ext_no','end_ext_no'], 'string'],
            [['start_become_member_time', 'end_become_member_time'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->platform)) {
            $where['platform'] = $this->platform;
        }

        if (!empty($this->buyer_id)) {
            $where['buyer_id'] = $this->buyer_id;
        }

        if (!empty($this->ext_no)) {
            $where['ext_no'] = $this->ext_no;
        }

        if (!empty($this->start_ext_no) || $this->start_ext_no !== '') {
            $where['and'][] = ['>=', 'ext_no', $this->start_ext_no];
        }
        if (!empty($this->end_ext_no) || $this->end_ext_no !== '') {
            $where['and'][] = ['<=', 'ext_no', $this->end_ext_no];
        }

        if ($this->member !== '') {
            $where['member'] = $this->member;
        }

        if (!empty($this->amazon_account)) {
            $where['amazon_account'] = $this->amazon_account;
        }

        if (!empty($this->start_amount) || $this->start_amount !== '') {
            $where['and'][] = ['>=', 'amount', $this->start_amount];
        }
        if (!empty($this->end_amount) || $this->end_amount !== '') {
            $where['and'][] = ['<=', 'amount', $this->end_amount];
        }

        //时间
        if (!empty($this->start_become_member_time)) {
            $where['and'][] = ['>=', 'become_member_time', strtotime($this->start_become_member_time)];
        }
        if (!empty($this->end_become_member_time)) {
            $where['and'][] = ['<', 'become_member_time', strtotime($this->end_become_member_time) + 86400];
        }

        return $where;
    }

}