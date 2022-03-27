var routes = angular.module('routes', ['ui.router', 'fieldTypes'], () => {})

routes.service('routesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.create = () => baseUrl + '/api/v4/setting/route/create'
    url.index = () => baseUrl + '/api/v4/setting/route'
    url.store = () => baseUrl + '/api/v4/setting/route'
    url.update = (id) => baseUrl + '/api/v4/setting/route/' + id + '?_method=PUT'
    url.show = (id) => baseUrl + '/api/v4/setting/route/' + id
    url.destroy = (id) => baseUrl + '/api/v4/setting/route/' + id
    url.store_cost = () => baseUrl + '/api/v4/setting/route/store_cost'
    url.edit_cost = (id) => baseUrl + '/api/v4/setting/route/store_cost/' + id
    url.show_cost = (id) => baseUrl + '/api/v4/setting/route/cost/' + id
    url.delete_cost = (id) => baseUrl + '/api/v4/setting/route/delete_cost/' + id
    url.store_detail_cost = (id) => baseUrl + '/api/v4/setting/route/store_detail_cost/' + id
    url.delete_detail_cost = (id) => baseUrl + '/api/v4/setting/route/delete_detail_cost/' + id
    url.datatable = () => baseUrl + '/api/v4/setting/route_datatable'
    this.url = url

    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.index() ).then(function(resp) {
            var data = resp.data.data;
            fn(data)
        }, function(){
            api.index(fn)
        });
    }

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
        }, function(error) {
            $rootScope.disBtn=false;
            if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                    det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
            } else {
                toastr.error(error.data.message,"Error Has Found !");
            }
        });
    }

    api.update = function(payload, id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.put(url.update(id), payload).then(function(resp) {
            toastr.success(resp.data.message)
            $rootScope.disBtn=false;
            fn(resp)
        }, function(){
            $rootScope.disBtn=false;
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp)
        }, function(){
        });
    }

    this.api = api
})