<<<<<<< HEAD
/**
 * Created by Administrator on 2018/5/5.
 */
//notemanage.js
//帖子管理侧边导航栏点击动画
$(function(){
    $('#notesidebar>ul>li').click(function(){
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());
        if($(this).index()==1){
            $('#notemanage').show();
            $('#newnote').hide();
            $('#wjck').hide();
            $('#notenavbar').show().children('li').eq(1).show();
            $('#notenavbar').show().children('li').eq(2).hide();
            $('#notenavbar').show().children('li').eq(3).hide();
        }
        else if($(this).index()==2){
            $('#notemanage').hide();
            $('#newnote').show();
            $('#wjck').hide();
            $('#notenavbar').show().children('li').eq(1).hide();
            $('#notenavbar').show().children('li').eq(2).show();
            $('#notenavbar').show().children('li').eq(3).hide();
        }
        else if($(this).index()==3){
            $('#notemanage').hide();
            $('#newnote').hide();
            $('#wjck').show();
            $('#notenavbar').show().children('li').eq(1).hide();
            $('#notenavbar').show().children('li').eq(2).hide();
            $('#notenavbar').show().children('li').eq(3).show();
        }
    });
});

//帖子管理body导航条
$(function(){
    //帖子管理->帖子管理
    $("#notemanage>ul>li").click(function(){
        //console.log($(this).index());
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#notelist').show();
            $('#notecomment').hide();
        }
        else{
            $('#notelist').hide();
            $('#notecomment').show();
        }
    });

    //帖子管理->违禁词库
    $('#wjck>ul>li').click(function(){
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#lexiconlist').show();
            $('#newbatch').hide();
        }
        else if($(this).index()==1){
            $('#lexiconlist').hide();
            $('#newbatch').show();
        }
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
});
=======
/**
 * Created by Administrator on 2018/5/5.
 */
//notemanage.js
//帖子管理侧边导航栏点击动画
$(function(){
    $('#notesidebar>ul>li').click(function(){
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());
        if($(this).index()==1){
            $('#notemanage').show();
            $('#newnote').hide();
            $('#wjck').hide();
            $('#notenavbar').show().children('li').eq(1).show();
            $('#notenavbar').show().children('li').eq(2).hide();
            $('#notenavbar').show().children('li').eq(3).hide();
        }
        else if($(this).index()==2){
            $('#notemanage').hide();
            $('#newnote').show();
            $('#wjck').hide();
            $('#notenavbar').show().children('li').eq(1).hide();
            $('#notenavbar').show().children('li').eq(2).show();
            $('#notenavbar').show().children('li').eq(3).hide();
        }
        else if($(this).index()==3){
            $('#notemanage').hide();
            $('#newnote').hide();
            $('#wjck').show();
            $('#notenavbar').show().children('li').eq(1).hide();
            $('#notenavbar').show().children('li').eq(2).hide();
            $('#notenavbar').show().children('li').eq(3).show();
        }
    });
});

//帖子管理body导航条
$(function(){
    //帖子管理->帖子管理
    $("#notemanage>ul>li").click(function(){
        //console.log($(this).index());
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#notelist').show();
            $('#notecomment').hide();
        }
        else{
            $('#notelist').hide();
            $('#notecomment').show();
        }
    });

    //帖子管理->违禁词库
    $('#wjck>ul>li').click(function(){
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#lexiconlist').show();
            $('#newbatch').hide();
        }
        else if($(this).index()==1){
            $('#lexiconlist').hide();
            $('#newbatch').show();
        }
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
});
>>>>>>> origin/qjb
