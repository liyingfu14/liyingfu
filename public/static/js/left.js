/**
 * Created by Administrator on 2018/5/26.
 */
//left.js   ��ߵ������������
$(function(){
   $('#notesidebar>ul>li').click(function(){
       console.log(this);
     $(this).addClass('active').siblings().removeClass('active');
   });
});