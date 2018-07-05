<<<<<<< HEAD
/**
 * Created by Administrator on 2018/4/29.
 */
//index.js

//侧边导航栏点击动画
$(function(){
        //$('#tq-head .ull>li>a').click(function(){
        //    console.log(1);
        //    //$(this).addClass('bg').siblings().removeClass('bg');
        //    $(this).addClass("bg").parent("li").siblings().find("a").removeClass("bg");
        //});
    //游戏管理侧边导航栏点击动画
    $('#sidebar>ul>li').click(function(){
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());

        //游戏管理侧边导航栏点击动画
        if($(this).index()==1){
            $('#addgame').show();
            $('#gamelist').hide();

            $('#lunbotumanage').show();
            $('#addlunbotu').hide();

            //$('#publishnews').show();
            //$('#newslist').hide();

            $('#navbar').show().children('li').eq(1).show();
            $('#navbar').show().children('li').eq(2).hide();
        }
        else{
            $('#addgame').hide();
            $('#gamelist').show();

            $('#lunbotumanage').hide();
            $('#addlunbotu').show();

            //$('#publishnews').hide();
            //$('#newslist').show();

            $('#navbar').show().children('li').eq(1).hide();
            $('#navbar').show().children('li').eq(2).show();
        }


    });

});

//数据中心侧边导航栏点击动画
$(function(){
    $('#datasidebar>ul>li').click(function(){
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());
        if($(this).index()==1){
            $('#usermanage').show();
            $('#gamelist').hide();
            $('#rechargedata').hide();
            $('#consumedata').hide();
            $('#datanavbar').show().children('li').eq(1).show();
            $('#datanavbar').show().children('li').eq(2).hide();
            $('#datanavbar').show().children('li').eq(3).hide();
            $('#datanavbar').show().children('li').eq(4).hide();
        }
        else if($(this).index()==2){
            $('#usermanage').hide();
            $('#gamelist').show();
            $('#rechargedata').hide();
            $('#consumedata').hide();
            $('#datanavbar').show().children('li').eq(1).hide();
            $('#datanavbar').show().children('li').eq(2).show();
            $('#datanavbar').show().children('li').eq(3).hide();
            $('#datanavbar').show().children('li').eq(4).hide();
        }
        else if($(this).index()==3){
            $('#usermanage').hide();
            $('#gamelist').hide();
            $('#rechargedata').show();
            $('#consumedata').hide();
            $('#datanavbar').show().children('li').eq(1).hide();
            $('#datanavbar').show().children('li').eq(2).hide();
            $('#datanavbar').show().children('li').eq(3).show();
            $('#datanavbar').show().children('li').eq(4).hide();
        }
        else{
            $('#usermanage').hide();
            $('#gamelist').hide();
            $('#rechargedata').hide();
            $('#consumedata').show();
            $('#datanavbar').show().children('li').eq(1).hide();
            $('#datanavbar').show().children('li').eq(2).hide();
            $('#datanavbar').show().children('li').eq(3).hide();
            $('#datanavbar').show().children('li').eq(4).show();
        }
    });
});

//数据中心body导航条
$(function(){
    //数据中心->礼包管理
    $("#gamelist>ul>li").click(function(){
        //console.log($(this).index());
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#giftlist').show();
            $('#addgift').hide();
        }
        else{
            $('#giftlist').hide();
            $('#addgift').show();
        }
    });

    //数据中心->充值数据
    $('#rechargedata>ul>li').click(function(){
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#srecharge').show();
            $('#ccoinmanage').hide();
            $('#ccoinprovide').hide();
        }
        else if($(this).index()==1){
            $('#srecharge').hide();
            $('#ccoinmanage').show();
            $('#ccoinprovide').hide();
        }
        else if($(this).index()==2){
            $('#srecharge').hide();
            $('#ccoinmanage').hide();
            $('#ccoinprovide').show();
        }
    });
});

=======
/**
 * Created by Administrator on 2018/4/29.
 */
//index.js

//侧边导航栏点击动画
$(function(){
        //$('#tq-head .ull>li>a').click(function(){
        //    console.log(1);
        //    //$(this).addClass('bg').siblings().removeClass('bg');
        //    $(this).addClass("bg").parent("li").siblings().find("a").removeClass("bg");
        //});
    //游戏管理侧边导航栏点击动画
    $('#sidebar>ul>li').click(function(){
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());

        //游戏管理侧边导航栏点击动画
        if($(this).index()==1){
            $('#addgame').show();
            $('#gamelist').hide();

            $('#lunbotumanage').show();
            $('#addlunbotu').hide();

            //$('#publishnews').show();
            //$('#newslist').hide();

            $('#navbar').show().children('li').eq(1).show();
            $('#navbar').show().children('li').eq(2).hide();
        }
        else{
            $('#addgame').hide();
            $('#gamelist').show();

            $('#lunbotumanage').hide();
            $('#addlunbotu').show();

            //$('#publishnews').hide();
            //$('#newslist').show();

            $('#navbar').show().children('li').eq(1).hide();
            $('#navbar').show().children('li').eq(2).show();
        }


    });

});

//数据中心侧边导航栏点击动画
$(function(){
    $('#datasidebar>ul>li').click(function(){
        $(this).addClass('active').siblings().removeClass('active');
        //console.log($(this).index());
        if($(this).index()==1){
            $('#usermanage').show();
            $('#gamelist').hide();
            $('#rechargedata').hide();
            $('#consumedata').hide();
            $('#datanavbar').show().children('li').eq(1).show();
            $('#datanavbar').show().children('li').eq(2).hide();
            $('#datanavbar').show().children('li').eq(3).hide();
            $('#datanavbar').show().children('li').eq(4).hide();
        }
        else if($(this).index()==2){
            $('#usermanage').hide();
            $('#gamelist').show();
            $('#rechargedata').hide();
            $('#consumedata').hide();
            $('#datanavbar').show().children('li').eq(1).hide();
            $('#datanavbar').show().children('li').eq(2).show();
            $('#datanavbar').show().children('li').eq(3).hide();
            $('#datanavbar').show().children('li').eq(4).hide();
        }
        else if($(this).index()==3){
            $('#usermanage').hide();
            $('#gamelist').hide();
            $('#rechargedata').show();
            $('#consumedata').hide();
            $('#datanavbar').show().children('li').eq(1).hide();
            $('#datanavbar').show().children('li').eq(2).hide();
            $('#datanavbar').show().children('li').eq(3).show();
            $('#datanavbar').show().children('li').eq(4).hide();
        }
        else{
            $('#usermanage').hide();
            $('#gamelist').hide();
            $('#rechargedata').hide();
            $('#consumedata').show();
            $('#datanavbar').show().children('li').eq(1).hide();
            $('#datanavbar').show().children('li').eq(2).hide();
            $('#datanavbar').show().children('li').eq(3).hide();
            $('#datanavbar').show().children('li').eq(4).show();
        }
    });
});

//数据中心body导航条
$(function(){
    //数据中心->礼包管理
    $("#gamelist>ul>li").click(function(){
        //console.log($(this).index());
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#giftlist').show();
            $('#addgift').hide();
        }
        else{
            $('#giftlist').hide();
            $('#addgift').show();
        }
    });

    //数据中心->充值数据
    $('#rechargedata>ul>li').click(function(){
        $(this).addClass('bline').siblings().removeClass('bline');
        if($(this).index()==0){
            $('#srecharge').show();
            $('#ccoinmanage').hide();
            $('#ccoinprovide').hide();
        }
        else if($(this).index()==1){
            $('#srecharge').hide();
            $('#ccoinmanage').show();
            $('#ccoinprovide').hide();
        }
        else if($(this).index()==2){
            $('#srecharge').hide();
            $('#ccoinmanage').hide();
            $('#ccoinprovide').show();
        }
    });
});

>>>>>>> origin/qjb
