'use strict';

$(function() {
    let domain = window.location.protocol + '//' + window.location.host + '/';
    let api = domain + 'admin/user/login';

    $("#login").click(function () {
        console.log(api);
        let data = getData();
        //console.log(data);
        //data.token = window.localStorage.getItem('admin-token');
        //alert(window.localStorage.getItem('admin-token'));
        $.ajax({
            type: "POST",
            url: api,
            headers: {
                'admin-token': window.localStorage.getItem('admin-token')
            },
            dataType: "json",
            data: data,
            success: function (data) {
                var obj = data.data;
                if (data.code === 200) {
                    let localtion = domain + obj.url;
                    $.cookie('admin-token', obj.token);
                    window.localStorage.setItem('admin-token', obj.token);
                    window.location = localtion;
                }
                if (data.msg) {
                    $("#error").html(data.msg);
                    $("#error").show();
                    setTimeout(function () {
                        $("#error").html('');
                        $("#error").hide();
                    }, 3000);
                }

                //alert(data.msg);
            },
            error: function (data) {
                console.log(data);
            },
            complete: function (data) {
                console.log(data, 'finished');
            }
        });
    });


    function getData(){
        let data = {
            user: $('#username').val(),
            pwd: $('#password').val()
        };
        return data;
    }
});
