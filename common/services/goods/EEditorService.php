<?php

namespace common\services\goods;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\PlatformInformation;

class EEditorService
{

    public static $select_maps = [
        1 => 'one_images',
        2 => 'two_images',
        3 => 'thr_images',
        4 => 'video',
        5 => 'lists',
        6 => 'table',
        7 => 'left_images',
        8 => 'right_images',
        9 => 'text',
    ];

    /**
     * 处理编辑内容
     * @param $editor
     * @return string
     */
    public static function dealEditor($editor)
    {
        if (empty($editor)) {
            return '';
        }
        $editor = str_replace(["\n","\t","\r"], '\n', $editor);
        $data = json_decode($editor,true);
//        var_dump($data);exit();
        $list = [];
        foreach ($data as $k => $v) {
            $list[$k]['select'] = self::$select_maps[$v['select']];
            $info = [];
            if (in_array($v['select'],[1,2,3,4,7,8])) {
                if (!is_array($v['href_img'])) {
                    $info['video'] = $v['href_img'];
                } else {
                    foreach ($v['href_img'] as $key => $value) {
                        $info[$key]['image'] = $value['href_img'];
                        $info[$key]['title'] = $v['contents'][$key]['title'];
                        $info[$key]['content'] = $v['contents'][$key]['content'];
                    }
                }
            } elseif (in_array($v['select'] ,[5,9])) {
                foreach ($v['contents'] as $key => $value) {
                    $info[$key]['title'] = $v['contents'][$key]['title'];
                    $info[$key]['content'] = $v['contents'][$key]['content'];
                }
            } elseif ($v['select'] == 6) {
                $info['title'] = $v['list_num'];
                $info['head'] = $v['href_img'];
                $info['body'] = $v['contents'];
            }

            $list[$k]['info'] = $info;
        }

        return json_encode($list,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 恢复编辑器格式
     * @param $editor_value
     * @return string
     */
    public static function restoreEditor($editor_value)
    {
        if (empty($editor_value) || $editor_value == '[]') {
            return '';
        }
        $editors = json_decode($editor_value,true);
        $editor = [];
        foreach ($editors as $k => $v) {
            $editor[$k]['select'] = array_search($v['select'], self::$select_maps);
            $select = $editor[$k]['select'];
            if (in_array($select,[1,2,3,5,7,8,9])) {
                foreach ($v['info'] as $key => $value) {
                    if (in_array($select,[1,2,3,7,8])) {
                        $editor[$k]['href_img'][] = [
                            'href_img' => $value['image'],
                            'is_hide' => empty($value['image']) ? 2 : 1
                        ];
                    }
                    $editor[$k]['contents'][] = [
                        'title' => $value['title'],
                        'content' => $value['content']
                    ];
                }
            } elseif ($select == 4) {
                $editor[$k]['href_img'] = $v['info']['video'];
                $editor[$k]['contents'] = '';
            } elseif ($select == 6) {
                $editor[$k]['list_num'] = $v['info']['title'];
                $editor[$k]['href_img'] = $v['info']['head'];
                $editor[$k]['contents'] = $v['info']['body'];
            }
            if (in_array($select,[1,2,3,4,5,7,8,9])) {
                $editor[$k]['list_num'] = $select == 5 ? count($v['info']) : 0;
            }
        }
        return $editor;
    }

    /**
     * 转化平台json
     * @param $goods_no
     * @param $platform_type
     * @return string
     */
    public static function platformEditorJson($goods_no, $platform_type)
    {
        if (!in_array($platform_type ,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO])) {
            return '';
        }
        $information = PlatformInformation::find()->where(['goods_no' => $goods_no,'platform_type' => $platform_type])->asArray()->one();
        $editor = $information['editor_value'];
        if (empty($editor)) {
            return '';
        }
        return self::platformJson($editor,$platform_type);
    }

    /**
     * 平台json格式
     * @param $editor
     * @param $platform_type
     * @return string
     */
    public static function platformJson($editor,$platform_type)
    {
        $editor = json_decode($editor, true);
        $list = [];
        if($platform_type == Base::PLATFORM_OZON) {
            $list['version'] = 0.3;
            $arr = [];
            foreach ($editor as $v) {
                $arr[] = self::ozonItemJson($v);
            }
            $list['content'] = $arr;
            return json_encode($list,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if($platform_type == Base::PLATFORM_ALLEGRO) {
            $arr = [];
            foreach ($editor as $v) {
                $item = self::allegroItemJson($v);
                if(!empty($item)) {
                    $arr[] = $item;
                }
            }
            $list['sections'] = $arr;
            return json_encode($list,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return '';
    }

    /**
     * Allegro json格式
     * @param $data
     * @return array|boolean
     */
    public static function allegroItemJson($data)
    {
        $select = array_search($data['select'], self::$select_maps);
        if (in_array($select, [3, 4, 5, 6])) {
            return false;
        }
        
        $item = [];
        switch ($select) {
            case 1:
            case 2;
                for ($i = 0; $i < $select; $i++) {
                    $image = $data['info'][$i]['image'];
                    $item[] = [
                        'type' => 'IMAGE',
                        'url' => $image,
                    ];
                }
                break;
            case 7:
                $info = current($data['info']);
                $image = $info['image'];
                $item[] = [
                    'type' => 'IMAGE',
                    'url' => $image,
                ];
                $item[] = [
                    'type' => 'TEXT',
                    'url' => $info['content'],
                ];
            case 8:
                $info = current($data['info']);
                $image = $info['image'];
                $item[] = [
                    'type' => 'TEXT',
                    'url' => $info['content'],
                ];
                $item[] = [
                    'type' => 'IMAGE',
                    'url' => $image,
                ];
            case 9:
                $info = current($data['info']);
                $item[] = [
                    'type' => 'TEXT',
                    'url' => $info['content'],
                ];
                break;
        }
        return [
            'item' => $item
        ];
    }

    /**
     * Ozonjson格式
     * @param $data
     * @return array
     */
    public static function ozonItemJson($data)
    {
        $list = [];
        $select = array_search($data['select'],self::$select_maps);
        /*        if ($select == 1) {
                    $list['widgetName'] = 'raShowcase';
                    $list['type'] = 'roll';
                    $list['blocks'][] = [
                        'imgLink' => '',
                        'img' => [
                            'src' => $data['info']['image'],
                            'srcMobile' => $data['info']['image'],
                            'alt' => '',
                            'position' => 'width_full',
                            'positionMobile' => 'width_full'
                        ],
                    ];
                }*/
        if (in_array($select,[1,2,3,7,8])) {
            $list['widgetName'] = 'raShowcase';
            $reverse = null;
            $i_cut = $select;
            if ($select == 1) {
                $list['type'] = 'billboard';
            } elseif ($select == 2) {
                $list['type'] = 'tileXL';
            } elseif ($select == 3) {
                $list['type'] = 'tileL';
            } elseif ($select == 7 || $select == 8) {
                $list['type'] = 'chess';
                $i_cut = 1;
                $reverse = $select == 8?true:false;
            }
            $arr = [];
            for ($i = 0; $i < $i_cut; $i ++) {
                //$image = str_replace(['image.chenweihao.cn'],['img.chenweihao.cn'],$data['info'][$i]['image']);
                $title = explode("\n", $data['info'][$i]['title']);
                $content = explode("\n", $data['info'][$i]['content']);
                $image = $data['info'][$i]['image'].'?t='.time();
                $model['img'] = [
                    'src' => $image,
                    'srcMobile' => $image,
                    'alt' => '',
                    'position' => 'to_the_edge',
                    'positionMobile' => 'to_the_edge'
                ];
                $model['imgLink'] = '';
                $model['title'] = [
                    'content' => $title,
                    'size' => 'size4',
                    'align' => 'left',
                    'color' => 'color1'
                ];
                $model['text'] = [
                    'size' => 'size2',
                    'align' => 'left',
                    'color' => 'color1',
                    'content' => $content,
                ];
                if(!is_null($reverse)){
                    $model['reverse'] = $reverse;
                }
                $arr[] = $model;
            }
            $list['blocks'] = $arr;
        }
        if ($select == 9) {
            $title = explode("\n", $data['info'][0]['title']);
            $content = explode("\n", $data['info'][0]['content']);
            $list['widgetName'] = 'raTextBlock';
            $list['theme'] = 'default';
            $list['padding'] = 'type2';
            $list['gapSize'] = 'm';
            $model['title'] = [
                'content' => $title,
                'align' => 'left',
                'size' => 'size5',
                'color' => 'color1'
            ];
            $model['text'] = [
                'size' => 'size2',
                'align' => 'left',
                'color' => 'color1',
                'content' => $content,
            ];
        }
        if ($select == 4) {
            $list['widgetName'] = 'raVideo';
            $list['type'] = 'embedded';
            $list['width'] = '300';
            $list['height'] = '168.5';
            $type = substr($data['info']['video'], strrpos($data['info']['video'], '.')+1);
            $list['source'][] = ['type' => 'video/'.$type, 'src' => $data['info']['video']];
        }
        if ($select == 5) {
            $list['widgetName'] = 'list';
            $list['theme'] = 'bullet';
            $arr = [];
            for ($i = 0; $i < count($data['info']); $i ++) {
                $content = explode("\n",$data['info'][$i]['content']);
                $title = explode("\n", $data['info'][$i]['title']);
                $model['text'] = [
                    'size' => 'size2',
                    'align' => 'left',
                    'color' => 'color1',
                    'content' => $content,
                ];
                $model['title'] = [
                    'content' => $title,
                    'size' => 'size4',
                    'align' => 'left',
                    'color' => 'color1'
                ];
                $arr[] = $model;
            }
            $list['blocks'] = $arr;
        } elseif ($select == 6) {
            $title = explode("\n", $data['info']['title']);
            $list['widgetName'] = 'raTable';
            $list['title'] = [
                'content' => $title,
                'size' => 'size4',
                'align' => 'left',
                'color' => 'color1'
            ];
            $hear_arr = [];
            $body_arr = [];

            foreach ($data['info']['head'] as $k => $v) {
                $title = explode("\n", $v['title']);
                $hear_arr[$k]['img'] = [
                    'src' => $v['href_img'],
                    'srcMobile' => $v['href_img'],
                    'position' => 'to_the_edge',
                    'positionMobile' => 'to_the_edge',
                    'alt' => ''
                ];
                $hear_arr[$k]['text'] = $title;
                $hear_arr[$k]['contentAlign'] = "left";
            }

            foreach ($data['info']['body'] as $k => $v) {
                foreach ($v['text'] as $key => $value) {
                    $title = explode("\n", $value['text']);
                    $body_arr[$k][] = $title;
                }
            }

            $list['table']['head'] = $hear_arr;
            $list['table']['body'] = $body_arr;
        }
        return $list;
    }

    /**
     * 解析平台json格式
     * @param $content
     * @param $platform_type
     * @return string
     */
    public static function reversePlatformJson($content,$platform_type)
    {
        $content = json_decode($content, true);
        $list = [];
        if($platform_type == Base::PLATFORM_ALLEGRO) {
            if(empty($content['sections'])) {
                return '';
            }
            $content = $content['sections'];
            $arr = [];
            foreach ($content as $v) {
                $item = self::reverseAllegroItemJson($v);
                if(!empty($item)) {
                    $arr[] = $item;
                }
            }
            $list['sections'] = $arr;
            return json_encode($list,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return '';
    }

    /**
     * Allegro json格式
     * @param $data
     * @return array|boolean
     */
    public static function reverseAllegroItemJson($data)
    {
        $data = $data['items'];
        $item = [];
        if (count($data) == 1) {
            $info = current($data);
            if ($info['type'] == 'IMAGE') {//单图
                $item['select'] = 'one_images';
                $item['info'][] = [
                    'image' => $info['url'],
                    'title' => '',
                    'content' => ''
                ];
            } else if ($info['type'] == 'TEXT') {//文本
                $item['select'] = 'text';
                $item['info'][] = [
                    'title' => '',
                    'content' => CommonUtil::dealContent($info['content'])
                ];
            }
        }

        if (count($data) == 2) {
            if ($data[0]['type'] == 'IMAGE') {
                if ($data[1]['type'] == 'IMAGE') {//2张图片
                    $item['select'] = 'two_images';
                    $item['info'][] = [
                        'image' => $data[0]['url'],
                        'title' => '',
                        'content' => ''
                    ];
                    $item['info'][] = [
                        'image' => $data[1]['url'],
                        'title' => '',
                        'content' => ''
                    ];
                }
                if ($data[1]['type'] == 'TEXT') {//左边图片
                    $item['select'] = 'left_images';
                    $item['info'][] = [
                        'image' => $data[0]['url'],
                        'title' => '',
                        'content' => CommonUtil::dealContent($data[1]['content'])
                    ];
                }
            }

            if ($data[0]['type'] == 'TEXT') {
                if ($data[1]['type'] == 'IMAGE') {//右边图片
                    $item['select'] = 'right_images';
                    $item['info'][] = [
                        'image' => $data[1]['url'],
                        'title' => '',
                        'content' => CommonUtil::dealContent($data[0]['content'])
                    ];
                }
            }
        }
        return $item;
    }

}