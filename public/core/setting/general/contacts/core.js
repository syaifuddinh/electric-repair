var contacts = angular.module('contacts', ['ui.router', 'fieldTypes'], () => {})

contacts.service('contactsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.indexPegawai =  () => baseUrl + '/contact/contact/pegawai'
    url.indexCustomer =  () => baseUrl + '/contact/contact/customer'
    url.indexSales =  () => baseUrl + '/contact/contact/sales'
    url.indexDriver =  () => baseUrl + '/contact/contact/driver'
    url.indexVendor = () => baseUrl + '/contact/contact/vendor'
    url.indexSupplier = () => baseUrl + '/contact/contact/supplier'
    url.index = () => baseUrl + '/contact/contact'
    url.show = (id) => baseUrl + '/contact/contact/' + id
    url.store = () => baseUrl + '/contact/contact'
    url.datatable = () => baseUrl + '/api/contact/company_datatable'
    this.url = url

    api.indexPegawai = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.indexPegawai() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
            api.indexPegawai(fn)
        });
    }

    api.indexSupplier = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.indexSupplier() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
            api.indexSupplier(fn)
        });
    }

    api.indexCustomer = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.indexCustomer() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
            api.indexCustomer(fn)
        });
    }

    api.indexDriver = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.indexDriver() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
            api.indexCustomer(fn)
        });
    }

    api.indexSales = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.indexSales() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
            api.indexCustomer(fn)
        });
    }

    api.indexVendor = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.indexVendor() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
        });
    }

    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get( url.index() ).then(function(resp) {
            var data = resp.data;
            fn(data)
        }, function(){
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
        $http.put(baseUrl+'/setting/company', payload).then(function(resp) {
            toastr.success(data.data.message)
            fn(data)
        }, function(){
        });
    }
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data)
        }, function(){
            api.show(id, fn)
        });
    }

    this.api = api
})