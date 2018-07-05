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

    $("#strategy").click(function(){
        if($(this).html() == "添加到攻略"){
            $(this).html("添加到帖子");
            $("#user_name").hide();
            $("#author_name").show();
        }else {
            $(this).html("添加到攻略");
            $("#user_name").show();
            $("#author_name").hide();
        }

    });
});

// $(function(){
//         // ��ʼ��Web Uploader
//         var uploader = WebUploader.create({

//             // ѡ���ļ����Ƿ��Զ��ϴ���
//             auto: true,

//             // swf�ļ�·��
//             swf: './webuploader/Uploader.swf',

//             // �ļ����շ���ˡ�
//             server: 'http://upload.4cgame.com/index.php/api/upload/post',

//             // ѡ���ļ��İ�ť����ѡ��
//             // �ڲ����ݵ�ǰ�����Ǵ�����������inputԪ�أ�Ҳ������flash.
//             pick: '#notefile',

//             // ֻ����ѡ��ͼƬ�ļ���
//             accept: {
//                 title: 'Images',
//                 extensions: 'gif,jpg,jpeg,bmp,png',
//                 mimeTypes: 'image/*'
//             }
//         });

//     // �����ļ���ӽ�����ʱ��
//         uploader.on( 'fileQueued', function( file ) {
//             var $li = $(
//                 '<div id="' + file.id + '" class="file-item thumbnail">' +
//                 '<img>' +
//                 //'<div class="info">' + file.name + '</div>' +
//                 '</div>'
//                 ),
//                 $img = $li.find('img');


//             // $listΪ����jQueryʵ��
//             var $list = $("#notelist");
//             $list.append( $li );

//             // ��������ͼ
//             // ���Ϊ��ͼƬ�ļ������Բ��õ��ô˷�����
//             // thumbnailWidth x thumbnailHeight Ϊ 100 x 100
//             uploader.makeThumb( file, function( error, src ) {
//                 if ( error ) {
//                     $img.replaceWith('<span>����Ԥ��</span>');
//                     return;
//                 }

//                 $img.attr( 'src', src );
//             }, 261, 111 );
//         });


//     // �ļ��ϴ������д���������ʵʱ��ʾ��
//         uploader.on( 'uploadProgress', function( file, percentage ) {
//             var $li = $( '#'+file.id ),
//                 $percent = $li.find('.progress span');

//             // �����ظ�����
//             if ( !$percent.length ) {
//                 $percent = $('<p class="progress"><span></span></p>')
//                     .appendTo( $li )
//                     .find('span');
//             }

//             $percent.css( 'width', percentage * 100 + '%' );
//         });

//     // �ļ��ϴ��ɹ�����item��ӳɹ�class, ����ʽ����ϴ��ɹ���
//         uploader.on( 'uploadSuccess', function( file ) {
//             $( '#'+file.id ).addClass('upload-state-done');
//         });

//     // �ļ��ϴ�ʧ�ܣ���ʾ�ϴ�����
//         uploader.on( 'uploadError', function( file ) {
//             var $li = $( '#'+file.id );
//             //$error = $li.find('div.error');

//             // �����ظ�����
//             if ( !$error.length ) {
//                 $error = $('<div class="error"></div>').appendTo( $li );
//             }

//             $error.text('�ϴ�ʧ��');
//         });

//     // ����ϴ����ˣ��ɹ�����ʧ�ܣ���ɾ����������
//         uploader.on( 'uploadComplete', function( file ) {
//             $( '#'+file.id ).find('.progress').remove();
//         });

//         //$("#fileList").delegate('.close_btn',"click",function(){
//         //    $(this).parent().remove();
//         //});
// });

