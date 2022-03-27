contracts.directive('contractTable', function () {
    return {
        restrict: 'E',
        scope: {
            is_sales_contract : "=isSalesContract",
            detail_route : "=detailRoute",
            select_mode : "=selectMode",
            customer_id : "=customerId"
        },
        require:'ngModel',
        templateUrl: '/core/marketing/contracts/view/contract-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, contractsService) {
            if(!$scope.formData) {
                $scope.formData = {}
            }

            if($attrs.isActive == 1) {
                $scope.formData.status = 1
            }

            var columnDefs = [
                {title : $rootScope.solog.label.contract.name },
                {title : $rootScope.solog.label.quotation.code },
                {title : $rootScope.solog.label.contract.code },
                {title : $rootScope.solog.label.general.date },
                {title : $rootScope.solog.label.general.date_end },
                {title : $rootScope.solog.label.general.customer },
                {title : $rootScope.solog.label.general.sales },
                {title : $rootScope.solog.label.general.periode_pengiriman },
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
                columns.push({
                    data:null,
                    name:"created_at",
                    className:"text-center",
                    render : function(item) {
                        var html = ''
                        html = "<a ng-click=\"show(" + item.id + ")\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

                        return html
                    }
                })
            }

           var options = {
                order:[[1,'desc']],
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

              if($attrs.hideExport) {
                    delete options.dom
              }
            $scope.options = options

            $scope.show = function(id) {
                if($scope.detail_route) {
                    $state.go($scope.detail_route, {'id' : id})
                } else {
                    $state.go('marketing.contract.show', {'id' : id})
                }
            }

            $scope.search = function () {
                if($scope.customer_id) {
                    $scope.formData.customer_id = $scope.customer_id
                }

                if($scope.is_sales_contract !== null && $scope.is_sales_contract !== undefined) {
                    $scope.formData.is_sales_contract = 1
                }
                $scope.$broadcast('reload', 0)
            }
            $scope.search()
            $scope.$on('reloadContract', function(e, v) {
                $scope.search()
            })

            $compile($('thead'))($scope)
            $timeout(function(){
                $scope.options.datatable.buttons().container().appendTo( '#export_button' )
            }, 500)

            $scope.showData = function(id) {
                contractsService.api.show(id, (dt) => {
                    $scope.$emit("chooseContract", dt)
                })
            }

            $scope.createdRow = function (row, data) {
                if($scope.select_mode) {
                    $(row).find('td').attr("ng-click", "showData(" + data.id + ")")
                }
            }

            $scope.showData()

            if($scope.tableOnCreated) {
                $scope.createdRow = $scope.tableOnCreated
            }
        }
    }
});