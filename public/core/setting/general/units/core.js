var units = angular.module('units', ['ui.router', 'fieldTypes'], () => {})

units.service('unitsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.index = () => baseUrl + '/setting/unit'
    url.store = () => baseUrl + '/setting/unit'
    url.update = (id) => baseUrl + '/setting/unit/' + id
    url.delete = (id) => baseUrl + '/setting/unit/' + id
    url.show = (id) => baseUrl + '/setting/unit/' + id
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

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.post(url.store(), payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp.data)
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
            fn(resp.data)
        }, function(){
        });
    }
    
    api.delete = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.delete(url.delete(id)).then(function(resp) {
            toastr.success(resp.data.message)
            fn(resp.data)
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

    this.api = api
})