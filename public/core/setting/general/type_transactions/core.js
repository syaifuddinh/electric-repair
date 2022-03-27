var typeTransactions = angular.module('typeTransactions', ['ui.router', 'fieldTypes'], () => {})

typeTransactions.service('typeTransactionsService', function($http, $rootScope) {
    var api = {}
    var url = {}

    url.show = (id) => baseUrl + '/api/v4/setting/type_transaction/' + id
    this.url = url
    
    api.show = function(id, fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(url.show(id)).then(function(resp) {
            fn(resp.data.data)
        }, function(){
        });
    }

    this.api = api
})