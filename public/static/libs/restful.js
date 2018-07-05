'use strict';

var JQuery;
(function(root,$){
    let restful  = {};
    restful.params = function(api,params){
        if(typeof api === 'undefined'){
            return false;
        }
        if(typeof params !== 'object' && params === null){
            return false;
        }
        this.json(params,function(key,value){

        },api);

    };

    restful.json = function(data,handler,scope){
        if($.type(data) === 'undefined' || data === null){
            return false;
        }
        if(typeof handler === 'function'){
            if(typeof scope !=='undefined'){
                handler.bind(this,scope);
            }
        }
        if($.type(data) === 'array'){
            for(let i = 0 , len = data.length ; i < len ;i++){
                let item = data[i];
                if($.type(item) === 'array'){

                    continue ;
                }
                if($.type(item) === 'object'){
                    handler();
                    continue ;
                }
                handler(i,item);
            }
        }
        if($.type(data) === 'object'){

        }

    };
    $.type()
    $.restful = restful;
    return root.restful = restful;
})(window,typeof JQuery === 'undefined' ? {}: JQuery);