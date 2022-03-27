var fieldTypes = angular.module('fieldTypes', [], () => {})

fieldTypes.service('fieldTypesService', function($http) {
    var api = {}
    api.index = function(fn) {
        if(!fn)
            fn = (dt) => {}
        $http.get(baseUrl+'/setting/field_type').then(function(resp) {
            var data = resp.data.data;
            fn(data)
        }, function(){
        });
    }

    this.api = api
})