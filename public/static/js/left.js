/**
 * Created by Administrator on 2018/5/26.
 */
//left.js   ²à±ßµ¼º½À¸µã»÷¶¯»­
$(function(){
   $('#notesidebar>ul>li').click(function(){
       console.log(this);
     $(this).addClass('active').siblings().removeClass('active');
   });
});