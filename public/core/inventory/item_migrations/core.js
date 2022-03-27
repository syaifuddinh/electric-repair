var itemMigrations = angular.module('itemMigrations', ['ui.router', 'branchs'], () => {})

itemMigrations.service('itemMigrationsService', function($http, $rootScope, $filter) {
    var api = {}
    var url = {}
    url.show = (id) => baseUrl + '/operational_warehouse/mutasi_transfer/' + id,
    url.update = (id) => baseUrl + '/operational_warehouse/mutasi_transfer/' + id,
    url.store = () => baseUrl + '/operational_warehouse/mutasi_transfer',
    url.showDetail = (id) => baseUrl + '/operational_warehouse/mutasi_transfer/' + id,
    this.url = url

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            if(resp.data.item) {
                resp.data.item.date_request = $filter('minDate')(resp.data.item.date_request)
            }
            fn(resp.data.item)
        }, function(){
        });
    }    

    api.showDetail = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showDetail(id)).then(function(resp) {
            fn(resp.data.detail)
        }, function(){
        });
    }    

    api.store = function(payload, fn) {
        if(!fn)
            fn = (dt) => {}
        $rootScope.disBtn=true;
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
        $rootScope.disBtn=true;
        $http.put(url.update(id), payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
        }, function(error){
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