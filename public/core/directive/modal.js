solog.directive('sologModal', function () {
    return {
        restrict: 'E',
        transclude:true,
        scope : {
            title : '=title',
            width_percent : '=widthPercent',
            onSubmit : '&'
        },
        templateUrl: '/core/base/modal.html',
        link: function (scope, el, attr, ngModel) {

            if(attr.widthPercent) {
                $(el).find('.modal-dialog').css('width', attr.widthPercent + '% ')
            }
            setTimeout(function () {

                if(attr.id) {
                    var id = attr.id
                    $('#' + id).removeAttr('id')
                    $(el).find('.modal').attr('id', id)
                }
            }, 400)
        },
        controller: function ($scope, $http, $attrs, $rootScope, $timeout) {
            $scope.hide_save = false
            if($attrs['hideSave']) {
                $scope.hide_save = true
            }
            if($attrs['hideFooter']) {
                $scope.hide_footer = true
            }
        }
    }
});