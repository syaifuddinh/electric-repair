operationalClaims.controller('operationalClaimsEdit', function() {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/operational/claims/view/edit.html',
        controller: function ($scope, $http, $attrs, $rootScope, operationalClaimsService) {
            
        }
    }
});