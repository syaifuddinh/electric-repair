var vendorPrices = angular.module('vendorPrices', ['ui.router'], () => {})

vendorPrices.config(($stateProvider, $urlRouterProvider) => {
})

vendorPrices.service('vendorPricesService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/marketing/vendor_price'
    url.show = (id) => baseUrl + '/marketing/vendor_price/' + id
    url.datatable = () => baseUrl + '/api/marketing/vendor_price_datatable'
    this.url = url

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

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data)
        }, function(){
        });
    }

    this.api = api
})