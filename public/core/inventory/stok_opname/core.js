var stokOpname = angular.module('stokOpname', ['ui.router', 'branchs'], () => {})

stokOpname.service('stokOpnameService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.approve = (id) => baseUrl + '/api/v4/operational_warehouse/stok_opname/' + id  + '/approve'
    url.index = () => baseUrl + '/api/v4/operational_warehouse/stok_opname'
    url.showDetail = (id) => baseUrl + '/api/v4/operational_warehouse/stok_opname/' + id,
    url.show = (id) => baseUrl + '/api/v4/operational_warehouse/stok_opname/' + id,
    url.store = () => baseUrl + '/api/v4/operational_warehouse/stok_opname'
    url.update = (id) => baseUrl + '/api/v4/operational_warehouse/stok_opname/' + id,
    url.destroy = (id) => baseUrl + '/api/v4/operational_warehouse/stok_opname/' + id,
    url.datatable = () => baseUrl + '/api/operational_warehouse/stok_opname_datatable'
    this.url = url

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
        $rootScope.disBtn=true;
        $http.put(url.update(id), payload).then(function(resp) {
            $rootScope.disBtn=false;
            toastr.success(resp.data.message)
            fn(resp)
        }, function(){
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
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
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

    api.index = function(payload = {}, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.index()).then(function(resp) {
            fn(resp.data.data)
        }, function(){
            api.index(fn)
        });
    }

    this.api = api
})