email.controller('email', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, emailService) {
    $rootScope.pageTitle="General Setting | Email Server";
    $scope.formData={}
    $scope.shipment_chip = []

    emailService.api.indexShipmentChip(function(dt){
        $scope.shipment_chip = dt
    })

    emailService.api.show(function(dt){
        $scope.formData = dt
    })

    $rootScope.disBtn = false
    $scope.submitForm=function() {
        emailService.api.store($scope.formData)
    }

    $scope.fillShipmentBody = function(myValue) {
        myValue = "{{ $ " + myValue + " }}"
        var myField = $("[ng-model='formData.shipment_body']")[0]
        //MOZILLA and others
        if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            $scope.formData.shipment_body = $scope.formData.shipment_body.substring(0, startPos)
                + myValue
                + $scope.formData.shipment_body.substring(endPos, $scope.formData.shipment_body.length);
        } else {
            $scope.formData.shipment_body += myValue;
        }
    }
});