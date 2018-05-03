<?php

class ImgController extends Controller
{
    protected $form = [];

    public function __construct()
    {
        $tag = Tag::select('id', 'name')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->name,
                    'value' => $item->id,
                ];
            })->toArray();
        //主要同过该数组定义表单的属性来生成表单
        $this->form = [
            //表单form信息
            'form' => [
                'action' => '/admin/img/img/add',
                'method' => 'post',
                'table' => 'img_'   //指定表名作为多个表单域的标识
            ],
            //弹出窗
            'dialog' => [
                'width' => 800,
                'height' => 650
            ],
            //字段信息
            'fields' => [
                //字段名称=>配置
                'name' => [
                    'label' => '名称',            //表单显示的label
                    'type' => 'textarea',       //表单类型
                    'placeholder' => '名称',
                    'show' => true,        //是否在后台列表显示，默认true
                    'edit' => true,        //是否可编辑，默认true
                    'is_add' => true,         //是否可添加，默认true
                    'rows' => 1,
                    'rule' => 'required'    //验证规则
                ],
                'desc' => [
                    'label' => '描述',
                    'type' => 'textarea',
                    'placeholder' => '描述',
                    'show' => true,
                    'edit' => true,
                    'rows' => 2,
                    //'rule' => 'required'
                ],
                'order' => [
                    'label' => '排序',
                    'type' => 'number',
                    'placeholder' => '排序',
                    'show' => true,
                    'value' => 1,
                    'rule' => 'required'
                ],
                'status' => [
                    'label' => '状态',
                    'type' => 'radio',
                    'is_add' => false,
                    'rule' => 'required',
                    'value' => [
                        [
                            'label' => '启用',
                            'value' => 1,
                        ],
                        [
                            'label' => '禁用',
                            'value' => 0
                        ]
                    ]
                ],
                'tag' => [
                    'label' => '标签',
                    'type' => 'checkbox',
                    'rule' => 'required',
                    'value' => $tag
                ],
                'img_url' => [
                    'label' => '选择图片',
                    'type' => 'file',
                    'rule' => 'required',
                    'is_edit' => false,
                ]
            ],
            //操作调用
            'action' => [
                'add' => [
                    'label' => '新增图片',
                    'function' => 'add',
                    'addUrl' => '/admin/img/img/add',
                    'class' => 'btn-primary'
                ],
                'edit' => [
                    'label' => '编辑',
                    'function' => 'edit',
                    'params' => 'id',
                    'editUrl' => '/admin/img/img/add',
                    'getUrl' => '/admin/img/img/get',
                    'class' => 'btn-primary'
                ],
                'del' => [
                    'label' => '删除',
                    'function' => 'del',
                    'params' => 'id',
                    'delUrl' => '/admin/img/img/del',
                    'class' => 'btn-danger'
                ]
            ]
        ];
    }

    public function list()
    {
        $form = $this->form;
        $list = Img::orderBy('order','DESC')
            ->with('tags')
            ->paginate(25);
        $data = $list->map(function ($item) {
            $tags = '';
            foreach ($item['tags'] as $tag) {
                $tags .= $tag['name'] . ', ';
            }
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'desc' => $item['desc'],
                'order' => $item['order'],
                'status' => $item['status'] ? '启用' : '禁用',
                'img_url' => asset('storage/' . $item['img_url']),
                'tags' => $tags
            ];
        });
        return view('img.img.list', compact('list', 'urls', 'form', 'data'));
    }

}