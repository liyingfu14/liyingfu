/**
 * Created by Administrator on 2018/5/5.
 */
//setup.js
//管理员管理侧边导航栏点击动画
$(function () {
    $('#setsidebar>ul>li').click(function () {
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());
        if ($(this).index() == 1) {
            $('#manager').show();
            $('#setnavbar').show().children('li').eq(1).show();
        }
    });
});

//管理员管理body导航条
$(function () {
    //帖子管理->帖子管理
    $("#manager>ul>li").click(function () {
        //console.log($(this).index());
        $(this).addClass('bline').siblings().removeClass('bline');
        if ($(this).index() == 0) {
            $('#mmanager').show();
            $('#addmanager').hide();
        }
        else {
            $('#mmanager').hide();
            $('#addmanager').show();
        }
    });
});

//编辑管理员------开始
$(".edit_btn").click(function () {
    $("#tilt").show();
    $("#close").click(function () {
        $("#tilt").hide();
    });
});
$(".delete").click(function () {
    alert("确认删除吗？");
});
$(".defriend").click(function () {
    alert("确认拉黑吗？");
});

//function getData(attrs){
//    let data = {} ;
//    for( let key in attrs){
//        if($.type(key) !== 'undefined' && key !==null){
//            let val = attrs[key] ;
//            if(/^(#|\.)/.test(val)){
//                data[key] = $(attrs[key]).val();
//            }
//        }
//    }
//    return data;
//}

//管理员管理ajax获取调用数据
//$(function () {
//    'use strict';
//    let domain = window.location.protocol + '//' + window.location.host + '/';
//    let api = domain + '/admin/user/search';
//    $("#search").click(function () {
//        console.log(api);
//        let data = getData();
//        //console.log(data);
//        //data.token = window.localStorage.getItem('admin-token');
//        //alert(window.localStorage.getItem('admin-token'));
//        $.ajax({
//            type: "POST",
//            url: api,
//            headers: {
//                'admin-token': window.localStorage.getItem('admin-token')
//            },
//            dataType: "json",
//            data: data,
//            success: function (result) {
//                //获取后台JSON
//                var obj = result.data;
//                //console.log(obj);
//                if (result.code == 200) {
//                    let update = $("#content").html() ? true : false;
//                    $.each(obj, function (k, v) {
//                        //console.log(v);
//                        let _class = k + '_user';
//                        var html = "";
//                        html += "<tr>";
//                        html += "<td>" + "<img src='http://tq.com/static/admin/img/gamelist_icon1.png'>" + "</td>"
//                        html += "<td class='" + _class + '_id' + "'>" + v.id + "</td>"
//                        html += "<td class='" + _class + '_role' + "'>" + v.role + "</td>"
//                        html += "<td class='" + _class + '_username' + "'>" + v.username + "</td>"
//                        html += "<td class='" + _class + '_real_name' + "'>" + v.real_name + "</td>"
//                        html += "<td class='" + _class + '_ip' + "'>" + '127.0.0.1' + "</td>"
//                        html += "<td class='" + _class + '_last_time' + "'>" + v.last_time + "</td>"
//                        html += "<td class='" + _class + '_mobile' + "'>" + v.mobile + "</td>"
//                        html += "<td class='" + _class + '_qq' + "'>" + v.qq + "</td>"
//                        html += "<td class='" + _class + '_user_state' + "'>" + v.user_state + "</td>"
//                        html += "<td>" + "<button class='" + 'edit_btn ' + _class + '_edit' + "'>编辑</button>"
//                        html += "<button class='" + 'delete_btn ' + _class + '_edit' + "'>删除</button>"
//                        html += "<button class='" + 'defriend_btn ' + _class + '_edit' + "'>拉黑</button>"
//                        html += "</td>"
//                        html += "</tr>";
//                        if (k === 0 && update) {
//                            $("#content").html("");
//                        }
//                        $("#content").html($('#content').html() + html);
//                    });
//
//                    //编辑管理员------开始
//                    $(".edit_btn").click(function () {
//                        'use strict';
//                        let domain = window.location.protocol + '//' + window.location.host + '/';
//                        let api = domain + '/admin/user/edit';
//                        let uid = $(this).attr('class');
//
//                        let _class = uid.split(' ');
//                        console.log(_class);
//                        if (_class.length >= 2) {
//                            uid = _class[1];
//                        }
//                        //console.log(edit_uid);
//                        //console.log(typeof(edit_uid));
//                        if (uid != null) {
//                            uid = '.' + uid.replace('_edit', '_id');
//                            //console.log(uid);
//                            let uid_val = $(uid).html();
//                            //console.log(uid_val);
//                            //console.log(typeof(uid));
//                            $("#edit_uid").val(uid_val);
//                            //console.log($("#edit_uid").val());
//                        }
//                        let edit = getedit();
//                        //console.log(edit);
//                        $.ajax({
//                            type: "POST",
//                            url: api,
//                            headers: {
//                                'admin-token': window.localStorage.getItem('admin-token')
//                            },
//                            dataType: "json",
//                            data: edit,
//                            success: function (result) {
//                                //获取后台JSON
//                                var obj = result.data[0];
//                                console.log(obj);
//                                if (result.code == 200) {
//                                    //alert("aaa");
//                                    $("#edit_username").val(obj.username);
//                                    $("#edit_name").val(obj.real_name);
//                                    $("#edit_mobile").val(obj.mobile);
//                                    $("#edit_qq").val(obj.qq);
//                                    //$("#edit_roleid").val(obj.role_id);
//                                    $("#edit_roleid").find("option[value = '" + obj.role_id + "']").attr("selected", "selected");
//                                    //console.log($("#edit_roleid").val());
//                                } else {
//                                    //alert("已经存在");
//                                    alert(result.msg);
//                                }
//                            },
//                            error: function (result) {
//                                //console.log(result);
//                                alert("网络异常");
//                                console.log(result);
//                            },
//                            complete: function (result) {
//                                console.log(result, 'finished');
//                            }
//                        });
//                        function getedit() {
//                            let edit = {
//                                uid: $("#edit_uid").val()
//                            };
//                            return edit;
//                        }
//
//                        $("#tilt").show();
//                        $("#close").click(function () {
//                            $("#tilt").hide();
//                        });
//
//                        //编辑管理员保存------开始
//                        $("#edit_save").click(function () {
//                            'use strict';
//                            let domain = window.location.protocol + '//' + window.location.host + '/';
//                            let api = domain + '/admin/user/updata';
//                            let data = getData();
//                            //console.log(data);
//                            $.ajax({
//                                type: "POST",
//                                url: api,
//                                headers: {
//                                    'admin-token': window.localStorage.getItem('admin-token')
//                                },
//                                dataType: "json",
//                                data: data,
//                                success: function (result) {
//                                    //获取后台JSON
//                                    var obj = result.data;
//                                    console.log(obj);
//                                    if (result.code == 200) {
//                                        alert(result.msg);
//                                        //alert("aaa");
//                                    } else {
//                                        //alert("已经存在");
//                                        alert(result.msg);
//                                    }
//
//                                },
//                                error: function (result) {
//                                    //console.log(result);
//                                    alert("没有找到");
//                                },
//                                complete: function (result) {
//                                    console.log(result, 'finished');
//                                }
//                            });
//                            function getData() {
//                                let data = {
//                                    uid: $("#edit_uid").val(),
//                                    user: $("#edit_username").val(),
//                                    pwd: $("#edit_password").val(),
//                                    role: $("#edit_roleid").val(),
//                                    name: $("#edit_name").val(),
//                                    mobile: $("#edit_mobile").val(),
//                                    qq: $("#edit_qq").val(),
//                                };
//                                return data;
//                            }
//                        });
//                        //编辑管理员保存------end
//                    });
//                    //编辑管理员------end
//
//                    //删除管理员------开始
//                    $(".delete_btn").click(function () {
//                        //alert("确定删除吗？");
//                        'use strict';
//                        let domain = window.location.protocol + '//' + window.location.host + '/';
//                        let api = domain + '/admin/user/del';
//                        let data = {uid: getuid(this)};
//                        console.log(data);
//                        $.ajax({
//                            type: "POST",
//                            url: api,
//                            headers: {
//                                'admin-token': window.localStorage.getItem('admin-token')
//                            },
//                            dataType: "json",
//                            data: data,
//                            success: function (result) {
//                                //获取后台JSON
//                                var obj = result.data;
//                                console.log(obj);
//                                if (result.code == 200) {
//                                    alert(result.msg);
//                                } else {
//                                    //alert("已经存在");
//                                    alert(result.msg);
//                                }
//
//                            },
//                            error: function (result) {
//                                //console.log(result);
//                                alert("没有找到");
//                            },
//                            complete: function (result) {
//                                console.log(result, 'finished');
//                            }
//                        });
//                        function getData(id) {
//                            console.log(id);
//                            let data = {
//                                uid: $(id).html()
//                            };
//                            return data;
//                        }
//                    });
//
//                    $(".defriend_btn").click(function () {
//                        //alert("确定拉黑吗？");
//                        'use strict';
//                        let domain = window.location.protocol + '//' + window.location.host + '/';
//                        let api = domain + '/admin/user/blacklist';
//                        let data = {uid: getuid(this)};
//                        console.log(data);
//                        $.ajax({
//                            type: "POST",
//                            url: api,
//                            headers: {
//                                'admin-token': window.localStorage.getItem('admin-token')
//                            },
//                            dataType: "json",
//                            data: data,
//                            success: function (result) {
//                                //获取后台JSON
//                                var obj = result.data;
//                                console.log(obj);
//                                if (result.code == 200) {
//                                    alert(result.msg);
//                                } else {
//                                    //alert("已经存在");
//                                    alert(result.msg);
//                                }
//
//                            },
//                            error: function (result) {
//                                //console.log(result);
//                                alert("没有找到");
//                            },
//                            complete: function (result) {
//                                console.log(result, 'finished');
//                            }
//                        });
//                        function getData(id) {
//                            console.log(id);
//                            let data = {
//                                uid: $(id).html()
//                            };
//                            return data;
//                        }
//                    });
//
//                } else {
//                    alert("data.msg");
//                }
//
//            },
//            error: function (result) {
//                //console.log(result);
//                alter("没有找到");
//            },
//            complete: function (result) {
//                console.log(result, 'finished');
//            }
//        });
//    });
//
//    function getData() {
//        let data = {
//            key: $('#username').val(),
//            rid: $('.roleid').val()
//        };
//        return data;
//    }
//});
//
//
////添加管理员ajax获取调用数据
//$(function () {
//    'use strict';
//    let domain = window.location.protocol + '//' + window.location.host + '/';
//    let api = domain + '/admin/user/addUser';
//    $("#add").click(function () {
//        console.log(api);
//        let data = getData();
//        //console.log(data);
//        //data.token = window.localStorage.getItem('admin-token');
//        //alert(window.localStorage.getItem('admin-token'));
//        $.ajax({
//            type: "POST",
//            url: api,
//            headers: {
//                'admin-token': window.localStorage.getItem('admin-token')
//            },
//            dataType: "json",
//            data: data,
//            success: function (result) {
//                //获取后台JSON
//                var obj = result.data;
//                console.log(obj);
//                if (result.code == 200) {
//                    alert(result.msg);
//                } else {
//                    //alert("已经存在");
//                    alert(result.msg);
//                }
//            },
//            error: function (result) {
//                //console.log(result);
//                alert("没有找到");
//            },
//            complete: function (result) {
//                console.log(result, 'finished');
//            }
//        });
//        function getData() {
//            let data = {
//                user: $('#user_name').val(),
//                pwd: $('#password').val(),
//                name: $('#name').val(),
//                mobile: $('#mobile').val(),
//                role: $('.roleid').val()
//            };
//            return data;
//        }
//    });
//});

//编辑管理员ajax获取调用数据
//$(function() {
//    'use strict';
//    let domain = window.location.protocol + '//' + window.location.host + '/';
//    let api = domain + '/admin/user/edit/uid/:uid?';
//    $("#").click(function () {
//            console.log(api);
//            let data = getData();
//            //console.log(data);
//            //data.token = window.localStorage.getItem('admin-token');
//            //alert(window.localStorage.getItem('admin-token'));
//            $.ajax({
//                type: "POST",
//                url: api,
//                headers: {
//                    'admin-token': window.localStorage.getItem('admin-token')
//                },
//                dataType: "json",
//                data: data,
//                success: function (result) {
//                    //获取后台JSON
//                    var obj = result.data;
//                    console.log(obj);
//                    if (result.code == 200) {
//                        alert("添加成功");
//                    }else {
//                        alert("已经存在");
//                        console.log(result.msg);
//                    }
//
//                },
//                error: function (result) {
//                    //console.log(result);
//                    alter("没有找到");
//                },
//                complete: function (result) {
//                    console.log(result, 'finished');
//                }
//            });
//        });
//
//        function getData(){
//            let data = {
//                user: $('#user_name').val(),
//                pwd: $('#password').val(),
//                name: $('#name').val(),
//                mobile: $('#mobile').val(),
//                qq: $('#mobile').val(),
//                role: 1
//            };
//            return data;
//        }
//    });





