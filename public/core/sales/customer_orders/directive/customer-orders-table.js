customerOrders.directive('customerOrdersTable', function () {
    return {
        restrict: 'E',
        scope: {
            'manifest_id' :'=manifestId',
            'hide_type' :'=hideType',
            'code_column_name' :'=codeColumnName',
            'addRoute' : '=addRoute',
            'detail_route' : '=detailRoute',
            'source' :'=source'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/sales/customer_orders/view/customer-order-table.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, customerOrdersService) {

            $('.ibox-content').addClass('sk-loading');

            $scope.formData = {};
            if($scope.source) {
                $scope.formData.source = $scope.source
            }

            var columnDefs = [
                {title : $rootScope.solog.label.customer_order.code},
                {title : $rootScope.solog.label.general.customer },
                {title : $rootScope.solog.label.contract.code },
                {title : $rootScope.solog.label.general.date },
                {title : $rootScope.solog.label.general.status },
            ]

            var columns = [
                {data:"code",name:"customer_orders.code"},
                {data:"customer_name",name:"contacts.name"},
                {data:"no_contract",name:"quotations.no_contract"},
                {data:"date",name:"customer_orders.date"},
                {data:"status",name:"customer_order_statuses.name"},
            ]

            if(!$attrs['hideAction']) {
                columnDefs.push({title : ''})
                columns.push({
                    data:null,
                    orderable:false,
                    searchable:false,
                    className:"text-center",
                    render:function(e) {
                        let html = "<a ng-show=\"$root.roleList.includes('sales.customer_order.detail')\" ng-click='show("+e.id+")'><span class='fa fa-folder-o'></span></a>&nbsp;"+
                        "<a ng-show=\"$root.roleList.includes('sales.customer_order.delete')\" ng-click='deletes("+e.id+")'><span class='fa fa-trash'></span></a>"

                    return html
                  }})
            }


           var options = {
                order:[[3,'desc'], [0,'desc']],
                lengthMenu:[[10,25,50,100],[10,25,50,100]],
                ajax: {
                  headers : {'Authorization' : 'Bearer '+authUser.api_token},
                  url : customerOrdersService.url.datatable(),
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
                    'filename' : $rootScope.solog.label.customer_order.title + ' - '+new Date(),
                  },
                ],
                columnDefs : columnDefs,
                columns:columns
            }

            $scope.options = options

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

            $scope.add = function() {
                if($scope.addRoute) {
                    $state.go($scope.addRoute)
                } else {
                    $state.go('sales_order.customer_order.create')
                }
            }

            $scope.show = function(id) {
                if($scope.detail_route) {
                    $state.go($scope.detail_route, {'id' : id})
                } else {
                    $rootScope.insertBuffer()
                    $state.go('sales_order.customer_order.show', {'id' : id})
                }
            }

            $scope.searchData = function() {
                $scope.$broadcast('reload', 0)
            }
            $scope.resetFilter = function() {
                $scope.formData = {};
                $scope.$broadcast('reload', 0)
            }


            $scope.deletes=function(ids) {
                var cfs=confirm("Apakah Anda Yakin?");
                if (cfs) {
                    customerOrdersService.api.destroy(ids, function(resp){
                        $scope.$broadcast('reload', 0)
                    })
                }
            }


        }
    }
});