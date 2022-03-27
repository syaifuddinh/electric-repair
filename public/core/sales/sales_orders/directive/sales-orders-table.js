salesOrders.directive('salesOrdersTable', function () {
    return {
        restrict: 'E',
        scope: {
            customerId : '=',
            forInvoicing : '=',
            is_pallet : '=isPallet',
            detail_route : '=detailRoute'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/sales/sales_orders/view/sales-order-table.html',
        link: function($scope, element, attrs){
        },
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, salesOrdersService) {
            if(!$scope.formData) {
                $scope.formData = {}
            }
            $scope.formData.customer_id = $scope.customerId
            $scope.formData.start_date = $scope.startDate
            $scope.formData.end_date = $scope.endDate
            if($scope.forInvoicing){
                $scope.formData.for_invoicing = $scope.forInvoicing
            }

            var columnDefs = [
                {title : $rootScope.solog.label.sales_order.code},
                {title : $rootScope.solog.label.general.customer },
                {title : $rootScope.solog.label.general.date },
                {title : $rootScope.solog.label.general.status },
            ]

            var columns = [
                {data:"code",name:"sales_orders.code"},
                {data:"customer_name",name:"contacts.name"},
                {data:"shipment_date",name:"job_orders.shipment_date"},
                {data:"status",name:"sales_order_statuses.name"},
            ]

            if(!$attrs['hideAction']) {
                columnDefs.push({title : ''})
                columns.push({
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:"text-center",
                    render:function(e) {
                        var html = "";

                        if($rootScope.roleList.includes('sales.sales_order.detail')) {
                            html += `
                              <a ng-click="show(${e.id})"><span class="fa fa-folder-o"></span></a>&nbsp;
                            `;
                        }

                        if($rootScope.roleList.includes('sales.sales_order.delete')) {
                            html += `
                              <a ng-click="deletes(${e.id})"><span class="fa fa-trash"></span></a>
                            `
                        }
                        return html
                    }
                })
            }


           var options = {
                order:[[2,'desc'], [0,'desc']],
                lengthMenu:[[10,25,50,100],[10,25,50,100]],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : salesOrdersService.url.datatable(),
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
                    'filename' : $rootScope.solog.label.sales_order.title + ' - '+new Date(),
                  },
                ],
                columnDefs : columnDefs,
                columns:columns
            }

            if($attrs['inputMode']) {
                $scope.createdRow = function(row, data) {
                    var first = $(row).find('td:first-child');
                    var a, b
                    a = $('<a ng-click="chooseItem(' + data.id + ')">' + first.text() + '</a>')
                    first.empty()
                    first.append(a)
                    $compile(angular.element(row).contents())($scope)
                }
            }


            $scope.options = options
            $scope.$on('reloadSalesOrder', function(e, v) {
                $scope.formData.customer_id = v.customer_id
                $scope.$broadcast('reload', 0)
            })

            $scope.resetFilter = function(){
                $scope.formData.start_date = "";
                $scope.formData.end_date = "";
                $scope.filterSalesOrder()
            }

            $scope.isFilter = false;

            $scope.filterSalesOrder = function(){
                $scope.searchData()
            }

            $scope.searchData = () => {
                if($scope.is_pallet) {
                    $scope.formData.is_pallet = $scope.is_pallet
                }
                $scope.$broadcast('reload', 0)
            }

            $scope.searchData()

            $compile($('thead'))($scope)
            $timeout(function(){
                $scope.options.datatable.buttons().container().appendTo( '#export_button' )
            }, 500)

            $scope.show = (id) => {
                if($scope.detail_route) {
                    $state.go($scope.detail_route, { id : id})
                } else {
                    $state.go('sales_order.sales_order.show', { id : id})
                }
            }

            $scope.chooseItem = function(id) {
                salesOrdersService.api.show(id, function(dt){
                    $scope.$emit('chooseSalesOrder', dt)
                })
            }

            $scope.deletes = function(id) {
                var is_confirm = confirm($rootScope.solog.label.general.are_you_sure)
                if(is_confirm) {
                    salesOrdersService.api.destroy(id, function(){
                       $scope.options.datatable.ajax.reload() 
                    })
                }
            }
        }
    }
});