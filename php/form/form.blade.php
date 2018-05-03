@php
    $formOptions = '';
    foreach($form['form'] as $key=>$value){
        $formOptions.="$key='$value''";
    }
@endphp
<div id="{{$form['form']['table'].'add'}}" title="添加" style="display: none;">
    <form class="form-horizontal" {{$formOptions}} id="add-input">
    </form>
</div>

<div id="{{$form['form']['table'].'edit'}}" title="编辑" style="display: none;">
    <form class="form-horizontal" {{$formOptions}} id="edit-input">
    </form>
</div>

<div id="tips" title="提示" style="display: none;">
    <p class="message"></p>
</div>
<script>
    /**
     * 设置预览图
     **/
    function file_change(file_obj) {
        let file = $(file_obj).get(0).files[0];

        let img_pre_view_obj = $(file_obj).parent().children('img');
        img_pre_view_obj.attr('src','');
        //如果是图片则显示预览
        if (!is_img(file.name))
            return;
        if (window.FileReader) {
            let fr = new FileReader();
            fr.onloadend = function (e) {
                img_pre_view_obj.attr('src',e.target.result);
            };
            fr.readAsDataURL(file);
        }
    }
    /**
     * 编辑
     * @param id
     */
    function edit(id) {
        $("#{{$form['form']['table'].'edit'}}").dialog({
            open: function () {
                $.ajax({
                    url: '{{$form['action']['edit']['getUrl']}}',
                    method: 'post',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': '{{csrf_token()}}',
                    },
                    data: {
                        id: id,
                    },
                    success: function (data) {
                        if (data.code == 1) {
                            setData(data.data)
                        } else {
                            alert(data.msg)
                        }
                    }
                })
            },
            width: 600,
            buttons: [
                {
                    text: '保存修改',
                    icon: "ui-icon-heart",
                    click: function () {
                        let formData = checkData('edit');
                        formData.append("id",id);
                        $.ajax({
                            url: '{{$form['action']['edit']['editUrl']}}',
                            method: 'post',
                            dataType: 'json',
                            processData : false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': '{{csrf_token()}}',
                            },
                            data: formData,
                            success: function (data) {
                                tip(data);
                            }
                        })
                    }
                }
            ]
        })
    }

    //设置数据
    function setData(data) {
        let fields = JSON.parse('<?=json_encode($form['fields'])?>');
        let id_pre = "#{{$form['form']['table']}}";
        for(let field in fields){
            if (!fields.hasOwnProperty(field)) continue;
            let item = fields[field];
            if (item.hasOwnProperty('is_edit') && !item.is_edit) continue;

            if (item.type === 'radio'){
                $(id_pre + 'edit input[value=' + data[field] + "]").prop("checked", true);
            }else if (item.type === 'checkbox'){
                for(let i in data[field]){
                    let val = data[field][i];
                    $(id_pre + 'edit input[value=' + val + ']').prop("checked", true);
                }
            }else if (item.type === 'file'){
                //如果是图片则显示预览
                let url = data[field];
                if (is_img(url)) {
                    $(id_pre + field + '_edit_pre_view').attr('src', url);
                }else {
                    $(id_pre + field + '_edit_tip').html('已经选择文件，可选择其他文件替换原文件')
                }
            }else {
                $(id_pre + field + '_edit').val(data[field]);
            }
        }
    }
    /**
     * 添加
     */
    function add() {
        $("#{{$form['form']['table'].'add'}}").dialog({
            open: function () {
            },
            width: '{{$form['dialog']['width'] or 600}}',
            height:'{{$form['dialog']['height'] or 'auto'}}',
            buttons: [
                {
                    text: '添加',
                    icon: "ui-icon-heart",
                    click: function () {
                        let formData = checkData('add');
                        if(!formData){
                            return false;
                        }
                        //let formData = new FormData();
                        //验证数据
                        $.ajax({
                            url: '{{$form['action']['add']['addUrl']}}',
                            method: 'post',
                            processData : false,
                            contentType: false,
                            dataType: 'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{csrf_token()}}',
                            },
                            data: formData,
                            success: function (data) {
                                tip(data);
                            }
                        })
                    }
                }
            ]
        });
    }
    //数据检验，并返回formData
    function checkData(__action){
        let formData = new FormData();
        let fields = JSON.parse('<?=json_encode($form['fields'])?>');
        let _action = '';
        if (__action === 'add'){
            _action = 'is_add'
        }else if(__action === 'edit'){
            _action = 'is_edit'
        }
        let ks = Object.keys(fields);
        let id_pre = "#{{$form['form']['table']}}";
        for(let field in ks) {
            field = ks[field];
            let item = fields[field];
            if (item.hasOwnProperty(_action) && !item[_action]) continue;
            let val;
            if (item.type === 'radio') {
                val = $(id_pre + __action + " input[type='radio']:checked").val();
                formData.append(field, val);
            } else if (item.type === 'checkbox') {
                val = '';
                $(id_pre + __action + " input[type='checkbox']:checked").each(function () {
                    val += $(this).val() + ',';
                });
                if (val) {
                    val = val.substr(0, val.length - 1);
                }
                formData.append(field, val);
            } else if (item.type === 'file') {
                val = $(id_pre + field + '_' + __action)[0].files[0];
                if(!val){
                    val = 'no_update';
                }
                formData.append(field, val);
            } else {
                val = $(id_pre + field + '_' + __action).val();
                formData.append(field, val);
            }

            if (!item.hasOwnProperty('rule')) continue;
            let rule = item.rule;
            if (rule) {
                let rules = rule.split('|');
                for (let rule in rules) {
                    let pass = true;
                    if (rules[rule] === 'required') {
                        if (!val) {
                            pass = false;
                        }
                    } else if (rules[rule] === '') {

                    } else {

                    }
                    if (!pass) {
                        alert('请填写 ' + item.label + ' 信息');
                        return false;
                    }
                }
            }
        }
        return formData;
    }

    /**
     * 根据id删除
     * @param id
     * @returns {boolean}
     */
    function del(id) {
        if(!id){
            return false;
        }
        if (!confirm('确定删除？')){
            return false;
        }
        $.ajax({
            url:'{{$form['action']['del']['delUrl']}}',
            type:'POST',
            headers:{
                'X-CSRF-TOKEN':'{{csrf_token()}}'
            },
            data:{
                id:id,
            },
            dataType:'json',
            success:function (data) {
                tip(data);
            }
        })
    }

    //提示函数
    function tip(data,tip) {
        $('#tips').dialog();
        $('#tips .message').html(tip || data.msg);
        if (data.code == 1) {
            setTimeout(function () {
                window.location = window.location.href;
            }, 1000);
        }
    }

    //判读是否为图片
    function is_img(val,exts) {
        let ext = String(val).split('.').pop().toLowerCase();
        if (!exts){
            exts = ['jpg','jpeg','png','gif'];
        }
        if ( -1 !==exts.indexOf(ext)){
            return true;
        }
        return false;
    }
    //设置表单的input控件
    function set_form_input(_action) {
        let __action = '';
        let suf = '';
        if (_action === 'add'){
            __action = 'is_add';
            suf = '_add';
        }else if(_action ==='edit'){
            __action = 'is_edit';
            suf = '_edit';
        }
        let fields = JSON.parse('<?=json_encode($form['fields'])?>');
        let html = '';
        let table = '{{$form['form']['table']}}';
        for(let field in fields){
            if (!fields.hasOwnProperty(field)) continue;

            let item = fields[field];
            if (item.hasOwnProperty(__action) && !item[__action]) continue;
            html += '<div class="form-group">';
            html += '<label for="' + table + field + '"';
            html += 'class="col-sm-2 control-label">' + item.label;
            html += '</label>';
            html += '<div class="col-sm-10">';
            if (item.type === 'textarea'){
                html += '<textarea name="'+table+field+'" ';
                html += 'id="'+table+field+suf + '" ';
                html += 'cols=50 ';
                html += 'class="form-control" ';
                html += 'rows="'+item.rows+'" ';
                html += 'placeholder="'+item.label+'"></textarea>';
            }else if (item.type === 'checkbox'){
                for (let val in item.value){
                    html += '<label class="checkbox-inline">';
                    html += '<input type="checkbox" ';
                    html += 'name="'+field+suf+'" ';
                    html += item.value[val].checked?'checked ':'';
                    html += 'value="'+item.value[val].value+'">' + item.value[val].label + '</label>';
                }
            }else if (item.type === 'radio'){
                for (let val in item.value){
                    html += '<label class="radio-inline">';
                    html += '<input type="radio"';
                    html += 'name="'+field+suf + '" ';
                    html += 'value="'+item.value[val].value+'">' + item.value[val].label + '</label>';
                }
            }   //文件类型可预览图片
            else if(item.type === 'file'){
                html += '<div class="col-sm-10">';
                html += '<input type="file" ';
                html += 'onchange="file_change(this)" ';
                html += 'id="'+table+field+suf + '" ';
                html += 'name="'+table+field+'">';
                //非图片的提示信息
                html += '<h4 id="' + table + field + suf + '_tip"></h4>';
                html += '<img src="" alt="" class="img-responsive" style="max-width: 600px; max-height: 200px;" ';
                html += 'id="'+table+field+suf + '_pre_view"></div>';
            } else {
                let val = item.value?item.value:'';
                html += '<input name="'+table+field+'" ';
                html += 'id="'+table+field+suf+'" ';
                html += 'type="'+item.type+'" ';
                html += 'value="'+val+'" ';
                html += 'class="form-control">';
            }
            html += '</div></div>';
            $('#'+_action+'-input').html(html);
        }
    }
    set_form_input('add');
    set_form_input('edit');
</script>