manifests.directive('manifestsSalesOrderLookup', function () {
    return {
        restrict: 'E',
        scope: {
            'manifest_id' :'=manifestId'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/manifests/view/manifests-sales-order-lookup.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, manifestsService, additionalFieldsService) {

            $scope.items = []
            $scope.formData = {}
            $scope.formData.except = []


            sales_detail_datatable = $('#sales_detail_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX : false,
                order: [],
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/sales/sales_order/detail/datatable',
                    data : (r) => Object.assign(r, $scope.formData)
                },
                columns:[
                    {
                        data:null, 
                        orderable:false,
                        searchable:false,
                        className : 'text-center',
                        render : function(resp) {
                            var html = '<a ng-click="delete(' + resp.id + ')"><i class="fa fa-trash-o"></i></a>'

                            return html
                        }
                    },
                    {data:"sales_order_code", name:"sales_order_code"},
                    {data:"customer_name", name:"customer_name"},
                    {data:"item_name", name:"item_name"},
                    {data:"leftover", searchable:false, orderable:false, className : 'text-right'},
                    {
                        data:null, 
                        searchable:false, 
                        orderable:false,
                        render:function(resp) {
                            var index = $scope.items.findIndex(x => x.id == resp.id)
                            var qty = 0
                            if(index == -1) {
                                if(!$rootScope.settings.shipment.is_zero_when_update_item) {
                                    qty = resp.leftover
                                }
                                $scope.items.push({
                                    id : resp.id,
                                    pickup : qty
                                })
                                index = $scope.items.findIndex(x => x.id == resp.id)
                            } else {
                                if(!$rootScope.settings.shipment.is_zero_when_update_item) {
                                    qty = resp.leftover
                                }
                                $scope.items[index].pickup = qty                                
                            }
                            var html = '<input type="text" only-num class="form-control" ng-model="items[' + index + '].pickup" />'
                            return html
                        }
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    if($rootScope.roleList.includes('setting.general_setting.vessel.edit')) {
                        $(row).find('td').attr('ng-click', 'edit(' + data.id + ')')
                        $(row).find('td:last-child').removeAttr('ng-click')
                    } else {
                        $(oTable.table().node()).removeClass('table-hover')
                    }
                    $compile(angular.element(row).contents())($scope);
                }

            });

            $scope.reload = function() {
                sales_detail_datatable.ajax.reload()
            }

            $scope.show = function() {
                $('#salesModal').modal()
                $scope.formData.except = []
                $scope.reload()
            }

            $scope.delete = (id) => {
                $scope.formData.except.push(id)
                $scope.reload()
            }

            $scope.submitForm = function() {
                var params = {}
                params.detail = $scope.items
                params.detail = params.detail.filter(x => x.pickup && x.pickup > 0)
                manifestsService.api.add_item(params, $scope.manifest_id, function(){
                    $('#salesModal').modal('hide')
                    $scope.$emit('manifestDetailStored', 1)
                })
            }
        }
    }
});