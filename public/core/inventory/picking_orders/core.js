var pickingOrders = angular.module('pickingOrders', ['ui.router', 'branchs'], () => {})

pickingOrders.service('pickingOrdersService', function($http, $rootScope) {
    var api = {}
    var url = {}
    url.approve = (id) => baseUrl + '/api/v4/operational_warehouse/picking/' + id  + '/approve'
    url.index = () => baseUrl + '/api/v4/operational_warehouse/picking'
    url.showDetail = (id) => baseUrl + '/api/v4/operational_warehouse/picking/' + id,
    url.show = (id) => baseUrl + '/api/v4/operational_warehouse/picking/' + id,
    url.store = () => baseUrl + '/api/v4/operational_warehouse/picking'
    url.update = (id) => baseUrl + '/api/v4/operational_warehouse/picking/' + id,
    url.destroy = (id) => baseUrl + '/api/v4/operational_warehouse/picking/' + id,
    url.datatable = () => baseUrl + '/api/operational_warehouse/picking_datatable'
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

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.item)
        }, function(){
        });
    }    

    api.destroy = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.delete(url.destroy(id)).then(function(resp) {
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