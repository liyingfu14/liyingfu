/**
 * Created by Administrator on 2018/5/12.
 */
//advcenter.js
//�������----->����ֲ�ͼ
$(function(){
    // ��ʼ��Web Uploader
    var uploader = WebUploader.create({

        // ѡ���ļ����Ƿ��Զ��ϴ���
        auto: true,

        // swf�ļ�·��
        swf: './webuploader/Uploader.swf',

        // �ļ����շ���ˡ�
        server: 'http://upload.4cgame.com/index.php/api/upload/post',

        // ѡ���ļ��İ�ť����ѡ��
        // �ڲ����ݵ�ǰ�����Ǵ�����������inputԪ�أ�Ҳ������flash.
        pick: '#filePicker',

        // ֻ����ѡ��ͼƬ�ļ���
        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/*'
        }
    });

// �����ļ���ӽ�����ʱ��
    uploader.on( 'fileQueued', function( file ) {
        var $li = $(
                '<div id="' + file.id + '" class="file-item thumbnail">' +
                '<img>' + '<img src="img/close_icon.png" class="imgbox_btn"/>' +
                //'<div class="info">' + file.name + '</div>' +
                '</div>'
            ),
            $img = $li.find('img:first');


        // $listΪ����jQueryʵ��
        var $list = $("#fileList");
        $list.append( $li );

        // ��������ͼ
        // ���Ϊ��ͼƬ�ļ������Բ��õ��ô˷�����
        // thumbnailWidth x thumbnailHeight Ϊ 100 x 100
        uploader.makeThumb( file, function( error, src ) {
            if ( error ) {
                $img.replaceWith('<span>����Ԥ��</span>');
                return;
            }

            $img.attr( 'src', src );
        }, 261, 111 );
    });


// �ļ��ϴ������д���������ʵʱ��ʾ��
    uploader.on( 'uploadProgress', function( file, percentage ) {
        var $li = $( '#'+file.id ),
            $percent = $li.find('.progress span');

        // �����ظ�����
        if ( !$percent.length ) {
            $percent = $('<p class="progress"><span></span></p>')
                .appendTo( $li )
                .find('span');
        }

        $percent.css( 'width', percentage * 100 + '%' );
    });

// �ļ��ϴ��ɹ�����item��ӳɹ�class, ����ʽ����ϴ��ɹ���
    uploader.on( 'uploadSuccess', function( file ) {
        $( '#'+file.id ).addClass('upload-state-done');
    });

// �ļ��ϴ�ʧ�ܣ���ʾ�ϴ�����
    uploader.on( 'uploadError', function( file ) {
        var $li = $( '#'+file.id );
        //$error = $li.find('div.error');

        // �����ظ�����
        if ( !$error.length ) {
            $error = $('<div class="error"></div>').appendTo( $li );
        }

        $error.text('�ϴ�ʧ��');
    });

// ����ϴ����ˣ��ɹ�����ʧ�ܣ���ɾ����������
    uploader.on( 'uploadComplete', function( file ) {
        $( '#'+file.id ).find('.progress').remove();
    });

    $.each(uploadBtnArray, function(index, el){
        el.refresh();
    });

    $("#fileList").delegate('.imgbox_btn',"click",function(){
        $(this).parent().remove();
    });
});