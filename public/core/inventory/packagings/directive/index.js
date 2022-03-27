contracts.directive('contractTable', function () {
    return {
        restrict: 'E',
        scope: false,
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/marketing/contracts/view/contract-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, contractsService) {
            if(!$scope.formData) {
                $scope.formData = {}
            }

            var columnDefs = [
                {title : $rootScope.solog.label.general.code},
                {title : $rootScope.solog.label.contract.name },
                {title : $rootScope.solog.label.quotation.code },
                {title : $rootScope.solog.label.contract.code },
                {title : $rootScope.solog.label.general.date },
                {title : $rootScope.solog.label.general.date_end },
                {title : $rootScope.solog.label.general.customer },
                {title : $rootScope.solog.label.general.sales },
                {title : $rootScope.solog.label.general.period },
                {title : $rootScope.solog.label.general.status },
            ]

            var columns = [
                {data:"name",name:"name"},
                {data:"code",name:"code"},
                {data:"no_contract",name:"no_contract"},
                {
                    data:null,
                    searchable:false,
                    render:resp => $filter('fullDate')(resp.date_start_contract)
                },
                {
                    data:null,
                    searchable:false,
                    render:resp => $filter('fullDate')(resp.date_end_contract)
                },
                {data:"customer.name",name:"customer.name"},
                {data:"sales.name",name:"sales.name"},
                {data:"send_type_name",name:"send_type"},
                {data:"active_name",name:"id"}
            ]

            if(!$attrs['hideAction']) {
                columnDefs.push({title : ''})
                columns.push({data:"action",name:"created_at",className:"text-center"})
            }

           var options = {
                order:[[3,'desc']],
                lengthMenu:[[10,25,50,100],[10,25,50,100]],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : contractsService.url.datatable(),
                  data : e => Object.assign(e, $scope.formData),
                  dataSrc: function(d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                  }
                },
                dom: 'Blfrtip',
                buttons: [
                  {
                    'extend' : 'excel',
                    'enabled' : true,
                    'action' : newExportAction,
                    'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
                    'className' : 'btn btn-default btn-sm',
                    'filename' : $rootScope.solog.label.contract.title + ' - '+new Date(),
                  },
                ],
                columnDefs : columnDefs,
                columns:columns
              }
            $scope.options = options
            $scope.$on('reloadContract', function(e, v) {
                $scope.$broadcast('reload', 0)
            })

            $compile($('thead'))($scope)
            $timeout(function(){
                $scope.options.datatable.buttons().container().appendTo( '#export_button' )
            }, 500)

            $scope.createdRow = function () {
            }
        }
    }
});