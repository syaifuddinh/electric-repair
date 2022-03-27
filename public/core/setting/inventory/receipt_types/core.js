var receiptTypes = angular.module('receiptTypes', ['ui.router', 'branchs'], () => {})

receiptTypes.service('receiptTypesService', function($http, $rootScope, $timeout) {
    var api = {}
    var url = {}
    url.index = () => baseUrl + '/api/v4/inventory/receipt_type'
    url.show = (id) => baseUrl + '/api/v4/inventory/receipt_type/' + id
    url.showBySlug = (slug) => baseUrl + '/api/v4/inventory/receipt_type/slug/' + slug
    this.url = url

    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        
        $http.get(url.index()).then(function(resp) {
            fn(resp.data.data)
        }, function(error) {
            $timeout(function(){
                api.index(fn)
            }, 3000)
        });
    }

    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(error) {
        });
    }

    api.showBySlug = function(slug, fn) {
        if(!fn)
            fn = (dt) => {}
        
        $http.get(url.showBySlug(slug)).then(function(resp) {
            fn(resp.data.data)
        }, function(error) {
        });
    }

    this.api = api
})