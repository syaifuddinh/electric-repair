solog.directive('sologDatatable', function () {
    return {
        restrict: 'E',
        scope: true,
        templateUrl: '/core/base/datatable.html',
        link: function (scope, el, attr, ngModel) {
            $(el).removeAttr('id')
            $(el).find('table').attr('id', attr['id'])
            scope.el = el
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $timeout) {
                var options = $scope.options

                $scope.initDatatable = function() {
                    options.scrollX = false
                    options.serverSide = true
                    options.processing = true
                    options.lengthMenu = [[10,25,50,100,-1],[10,25,50,100,'All']]
                    options.columnDefs = options.columnDefs.map((c, i) => {
                        c.targets = i
                        return c
                    })
                    options.createdRow = function (row, data, dataIndex) {
                        if($scope.createdRow)
                            $scope.createdRow(row, data, dataIndex)
                        $compile(angular.element(row).contents())($scope);
                    }
                    var oTable = $scope.el.find('#' + $attrs.id).DataTable(options)
                    options.datatable = oTable
                }

                $timeout(function () {
                    $scope.initDatatable()
                }, 300)

                $scope.$on('destroy', function() {
                    options.datatable.destroy()
                })
                
                $scope.$on('init', function() {
                    $scope.initDatatable()
                })

                $scope.$on('reload', function() {
                    options.datatable.ajax.reload()
                })

        }

    }
});