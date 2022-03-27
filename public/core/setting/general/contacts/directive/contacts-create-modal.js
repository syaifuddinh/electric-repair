contacts.directive('contactsCreateModal', function () {
    return {
        restrict: 'E',
        scope: {
            'is_pegawai' :'=isPegawai',
            'is_pelanggan' :'=isPelanggan',
            'is_driver' :'=isDriver',
            'is_vendor' :'=isVendor',
            'index_route' :'=indexRoute',
            'hide_type' :'=hideType',
            'jo_customer_id' : '=joCustomerId'
        },
        transclude:true,
        templateUrl: '/core/setting/general/contacts/view/contacts-create-modal.html',
        link: function (scope, el, attr, ngModel) {
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, itemsService) {
            $scope.slugId = 'contact_' + Math.round(Math.random() * 999999999) + '_modal'

            $scope.openModal=function() {        
                var modal = $('#' + $scope.slugId)
                if($('#' + $scope.slugId).length == 0){
                    $('#contact_modal').attr('id', $scope.slugId)
                    modal = $('#' + $scope.slugId)
                }
                modal.modal('show')
            }
        }
    }
});