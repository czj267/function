<?php

//通过配置数组生成菜单，使用adminLte
$menuConfig = [
    'menu' => [
        [
            'name' => '电影',
            'icon' => 'fa-dashboard',
            'url' => '#',
            'active'=>'movie',
            'sub_menu' => [
                [
                    'name' => '电影分类',
                    'icon' => 'fa-circle-o',
                    'url' => '#',     //只有最后一级的菜单的url是有效的
                    'active'=>'movie_cate',
                    'sub_menu' => [
                        [
                            'name' => '所有分类',
                            'icon' => 'fa-circle-o',
                            'url' => '/admin/movie/movie_cate/all',
                            'active'=>'all',
                        ]
                    ]
                ],
                [
                    'name' => '电视剧分类',
                    'icon' => 'fa-circle-o',
                    'url' => '#',     //只有最后一级的菜单的url是有效的
                    'active'=>'',
                    'sub_menu' => [
                        [
                            'name' => '所有分类',
                            'icon' => 'fa-circle-o',
                            'url' => '/admin/movie/tv/class/all',
                            'active'=>'',
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Multilevel',
            'icon' => 'fa-share',
            'url' => '#',
            'active'=>'',
            'sub_menu' => [
                [
                    'name' => ' Level One',
                    'icon' => 'fa-circle-o',
                    'url' => '#',
                    'active'=>'',
                ],
                [
                    'name' => ' Level One',
                    'icon' => 'fa-circle-o',
                    'url' => '#',
                    'active'=>'',
                    'sub_menu' => [
                        [
                            'name' => ' Level Two',
                            'icon' => 'fa-circle-o',
                            'url' => '#',
                            'active'=>'',
                        ],
                        [
                            'name' => ' Level Two',
                            'icon' => 'fa-circle-o',
                            'url' => '#',
                            'active'=>'',
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => '文档',
            'icon' => 'fa-book',
            'url' => 'https://adminlte.io/docs',
            'active'=>'',
        ]
    ],
    'label' => [
        [
            'name' => '重要的',
            'icon' => 'fa-circle-o text-red',
            'url' => '#',
            'active'=>'',
        ],
        [
            'name' => '警告',
            'icon' => 'fa-circle-o text-yellow',
            'url' => '#',
            'active'=>'',
        ],
        [
            'name' => '提示',
            'icon' => 'fa-circle-o text-aqua',
            'url' => '#',
            'active'=>'',
        ]
    ]
];



/**
     * 根据菜单配置生成菜单
     * @param $menus
     * @return string
     */
    function menus($menus)
    {
        //有子菜单
        $m = <<<m
    <a href="#">
        <i class="fa %s"></i>
        <span>%s</span>
        <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
        </span>
    </a>
m;
        //无子菜单
        $mm = <<<mm
    <li class="%s">
        <a href="%s">
            <i class="fa %s"></i>%s
        </a>
    </li>
mm;
        $str = '';
        foreach ($menus as $menu) {
            if (isset($menu['sub_menu'])) {
                $str .= '<li class="treeview '.$menu['active'].'">';
                $str .= sprintf($m, $menu['icon'], $menu['name']);
                $str .= '<ul class="treeview-menu '.$menu['active'].'">';
                $str .= menus($menu['sub_menu']);
                $str .= '</li></ul>';
            } else {
                $str .= sprintf($mm,$menu['active'], $menu['url'], $menu['icon'], $menu['name']);
            }
        }
        return $str;
    }


?>

<!--通过js能够自动高亮显示当前菜单项-->

<ul class="sidebar-menu" data-widget="tree">
                <li class="header">主菜单</li>
                <?php $menus = config('menu')?>
                <?=menus($menus['menu'])?>
                <li class="header">标签</li>
                <?=menus($menus['label'])?>
            </ul>
