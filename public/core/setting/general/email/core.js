var email = angular.module('email', ['ui.router', 'fieldTypes'], () => {})

email.config(($stateProvider, $urlRouterProvider) => {
    $stateProvider.state('setting.general.email_server',{url:'/email_server',data:{label:'Email Server'},views:{'':{templateUrl:'core/setting/general/email/view/index.html',controller:'email'}}})
})

email.service('emailService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.store = () => baseUrl + '/setting/email'
    url.show = () => baseUrl + '/setting/email'
    url.indexShipmentChip = () => baseUrl + '/setting/email/shipment_chip'
    this.url = url

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
    
    api.show = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show()).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    
    api.indexShipmentChip = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.indexShipmentChip()).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }


    this.api = api
})