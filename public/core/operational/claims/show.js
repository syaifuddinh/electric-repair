operationalClaims.controller('operationalClaimsShow', function() {
    return {
        restrict: 'E',
        scope: {
            formData : '='
        },
        templateUrl: '/core/operational/claims/view/show.html',
        controller: function ($scope, $http, $attrs, $rootScope, operationalClaimsService) {
            
        }
    }
});