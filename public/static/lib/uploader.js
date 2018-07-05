;(function (root, $) {
    'use strict';   //严格模式
    // 检测 依赖
    if (typeof  root === 'undefined' || typeof  $ === 'undefined') {
        console.error('require window  and JQuery');
        return false;
    }

    /**
     *上传 封装 对象 构造方法
     * @param config
     * @constructor
     */
    function Uploader(config) {
        if (typeof config !== 'undefined')
            this.config = config;
        else
            this.config = {};
        if ('undefined' !== typeof  WebUploader) {
            this.core = WebUploader;
        }
        if ('undefined' === typeof this.core && 'undefined' !== typeof $.WebUploader) {
            this.core = $.WebUploader;
        }
        if ('undefined' === typeof this.core && 'undefined' !== typeof root.WebUploader) {
            this.core = root.WebUploader;
        }
        if ('undefined' === typeof(this.core) || null === this.core) {
            console.error('require WebUploader !');
            throw('error require WebUploader not exits');
        }
        this.config.def = {
            method: 'POST',
            auto_upload: true,
            pick: '.filePicker',
            swf: './webuploader/Uploader.swf',
            accept: {
                title: 'Images',
                extensions: 'gif,jpg,jpeg,bmp,png',
                mimeTypes: 'image/*'
            },
            server: 'http://upload.4cgame.com/index.php/api/upload/post',
            X_App: '4cgame_admin_app',
            ContentType: 'application/x-www-form-urlencoded;charset=utf-8',
            AccessMethod: 'POST',
            Origin: 'http://apitest.4cgame.com',
            fileList: '#fileList',
            itemList: '',
            duplicate : true,
            events: ['fileQueued', 'uploadSuccess', 'uploadComplete', 'uploadError', 'uploadProgress', 'fileRemove']
        };
        this.$instance = null;
    }

    // 以下 原型链 追加
    /***
     * 是否 开启自动上传 [在 获取 上传对象前 调用]
     * @param val
     */
    Uploader.prototype.auto = function (val) {
        if ('boolean' === $.type(val))
            this.config.def.auto_upload = val;
    };

    /**
     * 设置 | 获取 当前配置 上传 服务器 url
     * @param url
     * @param name
     */
    Uploader.prototype.apiRequest = function (url, name) {
        if ('undefined' === typeof  url)
            return this.config.server;
        if ('undefined' === typeof name)
            this.config.server = url;
        if ('undefined' === typeof  this.config._servers)
            this.config._servers = {};
        if ('undefined' !== typeof name)
            this.config._servers[name] = url;
    };

    /**
     * 获取 预览 item
     * @returns {*}
     */
    Uploader.prototype.getItemList = function () {
        return this.config.itemList ? this.config.itemList : this.config.def.itemList;
    };

    /**
     * 获取 文件 容器 input
     * @returns {*}
     */
    Uploader.prototype.getfileList = function () {
        return this.config.fileList ? this.config.fileList : this.config.def.fileList;
    };

    /**
     * 获取 文件 上传 按钮 pick
     * @returns {string}
     */
    Uploader.prototype.getPick = function () {
        return this.config.pick ? this.config.pick : this.config.def.pick;
    };

    /**
     * 获取 上传 对象
     * @param url
     * @param method
     * @param fileItem
     */
    Uploader.prototype.getInstance = function (url, method, fileItem) {
        let core = this.core;
        if ('undefined' === $.type(url)) {
            url = this.getServerApi();
        }
        if ('undefined' === $.type(method)) {
            method = this.config.method ? this.config.method : this.config.def.method;
        }

        if ('undefined' === $.type(fileItem) || !(/^(#|\.).+/.test(fileItem))) {
            fileItem = this.getPick();
        }
        // 每个对象 只维护 一个 webuploader 对象 [单例]
        if ('undefined' === $.type(this.$instance) || null === this.$instance) {
            this.$instance = core.create({
                // 选完文件后，是否自动上传。
                auto: true,

                method: method,

                // swf文件路径
                swf: this.config.swf ? this.config.swf : this.config.def.swf,

                // 文件接收服务端。
                server: url,

                // 文件容器
                pick: fileItem,

                // 只允许选择图片文件。
                accept: this.config.accept ? this.config.accept : this.config.def.accept,

                duplicate : $.type(this.config.duplicate) !== 'undefined' ? this.config.duplicate : this.config.def.duplicate 
            });
            // 初始 默认 事件
            this.initDefaultEvent(this.getEvents());
        }
        if ('undefined' !== $.type(this.config.X_Token)) {
            this.setApiHeader();
        }
        return this.$instance;
    };

    /**
     * 获取 开启事件
     * @returns {*}
     */
    Uploader.prototype.getEvents = function () {
        return this.config.events ? this.config.events : this.config.def.events;
    };

    /**
     * 默认 事件 设置
     * @param $events
     */
    Uploader.prototype.initDefaultEvent = function ($events) {

        let uploadProgress = false;
        let uploadSuccess = false;
        let uploadError = false;
        let uploadComplete = false;
        let fileRemove = false;
        let fileQueued = false;
        let that = this;

        $.each($events, function (index, val) {
            if ('fileRemove' === val) {
                fileRemove = true;
            }
            if ('uploadComplete' === val) {
                uploadComplete = true;
            }
            if ('uploadError' === val) {
                uploadError = true;
            }
            if ('uploadSuccess' === val) {
                uploadSuccess = true;
            }
            if ('uploadProgress' === val) {
                uploadProgress = true;
            }
            if ('fileQueued' === val) {
                fileQueued = true;
            }
        });

        if (fileQueued) {
            this.$instance.on('fileQueued', function (file) {
                let $li = $(
                    '<div' + ' id="' + file.id + '" class="file-item thumbnail">' +
                    '<img>' + '<img src="/static/admin/img/advcenter_icon5.png" class="close_btn" data-id="' + file.id + '"/>' +
                    '</div>'
                    ),
                    $img = $li.find('img:first');
                let pick = that.getfileList();
                // $list为容器jQuery实例
                let $list = $(pick + ">.flag");
                $list.before($li);

                // 创建缩略图
                // 如果为非图片文件，可以不用调用此方法。
                // thumbnailWidth x thumbnailHeight 为 100 x 100
                that.$instance.makeThumb(file, function (error, src) {
                    if (error) {
                        $img.replaceWith('<span>不能预览</span>');
                        return;
                    }
                    $img.attr('src', src);
                }, 78, 112);
            });

        }


        // 上传进度条
        if (uploadProgress) {
            this.$instance.on('uploadProgress', function (file, percentage) {
                let $li = $('#' + file.id),
                    $percent = $li.find('.progress span');

                // 避免重复创建
                if (!$percent.length) {
                    $percent = $('<p class="progress"><span></span></p>')
                        .appendTo($li)
                        .find('span');
                }
                $percent.css('width', percentage * 100 + '%');
            });
        }


        // 文件上传成功，给item添加成功class, 用样式标记上传成功。
        if (uploadSuccess) {
            this.$instance.on('uploadSuccess', function (file, response) {
                let success_data = JSON.parse(response._raw); //上传图片的路径
                if (success_data.code !== 200 && 'undefined' !== $.type(success_data.msg)) {
                    alert(success_data.msg + ',请重新上传');
                } else {
                    let img_url = success_data.data.file;
                    let item = that.getItemList();
                    let $item = $(item);
                    let urls = $item.val();
                    if ('string' === $.type(urls) && 'undefined' !== $.type(urls) && urls.length > 0)
                        img_url = ',' + img_url;
                    $item.val(urls + img_url);
                }
                $('#' + file.id).addClass('upload-state-done');
            });
        }


        // 文件上传失败，显示上传出错。
        if (uploadError) {
            this.$instance.on('uploadError', function (file, result) {
                let $li = $('#' + file.id);
                // let $error = null;
                // 避免重复创建
                if ('undefined' !== $.type(result))
                    $('<div class="error">' + result.toString() + '</div>').appendTo($li);
                /* $error.text('上传失败');*/
            });
        }


        // 完成上传完了，成功或者失败，先删除进度条。
        if (uploadComplete) {
            this.$instance.on('uploadComplete', function (file) {
                $('#' + file.id).find('.progress').remove();
            });
        }


        // 移除 事件
        if (fileRemove) {
            let list = this.config.fileList ? this.config.fileList : this.config.def.fileList;
            if ('undefined' !== $.type(list) && (/^(#|\.).+/.test(list))) {
                $(list).delegate('.close_btn', "click", function () {
                    let $it = $(this);
                    $it.parent().remove();
                    that.removeValById($it.data('id'));
                });
            }
        }
    };

    /**
     * 通过 id | id_class 移除 数据
     * @param id
     * @returns {boolean}
     */
    Uploader.prototype.removeValById = function (id) {
        let item = this.getItemList(); // 获取对应 存储 input
        let $item = $(item);// jq 对象
        let file_id = id; // id 第几个
        if (!$.isNumeric(id)) {
            file_id = parseInt(file_id.split('_')[2]);
        }
        let files = $item.val();
        let new_files = '';
        if ('undefined' !== $.type(files) && 'string' === $.type(files) && files.length > 0) {
            files = files.split(',');
            let len = files.length;
            if (file_id > len) {
                return false;
            }
            for (let i = 0, len = files.length; i < len; i++) {
                if (i === file_id) {
                    continue;
                }
                if (new_files.length > 0) {
                    new_files = new_files + ',' + files[i];
                }
                else {
                    new_files = files[i];
                }
            }
            $item.val(new_files);
        }
        return true;
    };

    /**
     * 设置 头部 身份验证  token
     * @param Token
     * @returns {Uploader}
     */
    Uploader.prototype.setToken = function (Token) {
        this.config.X_Token = Token;
        return this;
    };

    /**
     * 设置 上传 令牌
     * @param data
     * @returns {Uploader}
     */
    Uploader.prototype.dataToken = function (data) {
        this.config.dataToken = data;
        return this;
    };

    /**
     * 设置 上传 服务器 相关头部信息 [跨域设置]
     */
    Uploader.prototype.setApiHeader = function () {
        let that = this;
        return this.$instance.on('uploadBeforeSend', function (obj, data, headers) {
            data.token = that.config.dataToken;
            headers = $.extend(headers, {
                "x-token": that.config.X_Token,
                "x-app": that.config.def.X_App
            });
            return headers;
        });
    };

    /**
     * 获取 上传 服务器 api url [ 在获取 上传 对象 前 ]
     * @returns {string}
     */
    Uploader.prototype.getServerApi = function () {
        let url = this.apiRequest();
        return url ? url : this.config.def.server;
    };

    // 对象 挂载 [ 扩展 ]
    $.uploader = Uploader;
    root.$uploader = Uploader;

})(window, jQuery);