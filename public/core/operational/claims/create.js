operationalClaims.controller('operationalClaimsCreate', function() {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/operational/claims/view/create.html',
        controller: function ($scope, $http, $attrs, $rootScope, operatinalClaimsService) {
            
        }
    }
});