var voyageSchedules = angular.module('voyageSchedules', ['ui.router'], () => {})

voyageSchedules.config(($stateProvider, $urlRouterProvider) => {
})

voyageSchedules.service('voyageSchedulesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/operational/container'
    url.datatable = () => baseUrl + '/api/operational/container_datatable'
    this.url = url

    api.indexGroup = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/operational/container/group').then(function(resp) {
            var data = resp.data.data;
            fn(data)
        }, function(){
        });
    }

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
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
        $http.put(baseUrl+'/operational/container', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(baseUrl+'/operational/container', payload).then(function(resp) {
            fn(data)
        }, function(){
        });
    }

    this.api = api
})