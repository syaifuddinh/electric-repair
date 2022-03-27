var packagings = angular.module('packagings', ['ui.router', 'branchs'], () => {})

packagings.service('packagingsService', function($http, $rootScope, $filter) {
    var api = {}
    var url = {}
    url.approve = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id  + '/approve'
    url.index = () => baseUrl + '/api/v4/operational_warehouse/packaging'
    url.showDetail = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id,
    url.show = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id,
    url.showNewItem = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id + '/new_item',
    url.showOldItem = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id + '/old_item',
    url.approve = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id + '/approve',
    url.store = () => baseUrl + '/api/v4/operational_warehouse/packaging'
    url.update = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id,
    url.destroy = (id) => baseUrl + '/api/v4/operational_warehouse/packaging/' + id,
    url.datatable = () => baseUrl + '/api/operational_warehouse/packaging_datatable'
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

    api.destroy = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $rootScope.disBtn=true;
        $http.delete(url.destroy(id)).then(function(resp) {
            toastr.success(resp.data.message)
            $rootScope.disBtn=false;
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
            if(resp.data.data.date) {
                resp.data.data.date = $filter('minDate')(resp.data.data.date)
            }
            fn(resp.data.data)
        }, function(){
        });
    }

    api.approve = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.put(url.approve(id)).then(function(resp) {
            $rootScope.disBtn = false;
            toastr.success('Data successfully approved')
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

    api.showNewItem = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showNewItem(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    api.showOldItem = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.showOldItem(id)).then(function(resp) {
            fn(resp.data.data)
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