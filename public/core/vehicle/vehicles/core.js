var vehicles = angular.module('vehicles', ['ui.router', 'branchs'], () => {})

vehicles.service('vehiclesService', function($http, $rootScope, $filter) {
    var api = {}
    var url = {}
    url.index = () => baseUrl + '/vehicle/vehicle'
    url.datatable = () => baseUrl + '/api/vehicle/packaging_datatable'
    this.url = url

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