/**
 * Created by Administrator on 2018/5/23.
 */
//datacenter.js
$(function(){
   $('.pagination>li>span').click(function(){
      $(this).addClass("on").parent('li').siblings().children('span').removeClass('on');
   });
    $('.pagination>li>a').click(function(){
      $(this).addClass("on").parent('li').siblings().children('a').removeClass('on');
   });
});