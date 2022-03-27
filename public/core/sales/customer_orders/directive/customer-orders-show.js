customerOrders.directive('customerOrdersShow', function () {
    return {
        restrict: 'E',
        scope: {
            ngDisabled : '=ngDisabled',
            index_route : '=indexRoute'
        },
        require:'ngModel',
        templateUrl: '/core/sales/customer_orders/view/customer-orders-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $timeout, $stateParams, $state, $compile, customerOrdersService) {
            $rootScope.pageTitle=$rootScope.solog.label.general.detail
            $scope.formData = {}
            $scope.formData.total_price = 0;
            $scope.disBtn = true;

            $scope.showData = () => {
                customerOrdersService.api.show($stateParams.id, function(dt) {
                    $scope.formData = dt
                    $scope.formData.btn_code = dt.code

                    $scope.showDataDetail()
                    $scope.showFile()
                    $scope.disBtn=false;
                })
            }
            
            $scope.showDataDetail = () => {
                customerOrdersService.api.showDetail($stateParams.id, function(dt){
                    $scope.formData.detail = dt
                    $scope.sumDetails()
                })
            }
            $scope.showFile = () => {
                customerOrdersService.api.showFile($stateParams.id, function(dt){
                    $scope.formData.file = dt
                })
            }

            $scope.show = function() {
                $scope.showData()
            }
            $scope.show()

            $scope.back = () => {
                if($scope.index_route) {
                    $state.go($scope.index_route);
                } else {
                    $state.go("sales_order.customer_order")
                }
            }

            $scope.modalUpload = () => {
                $('#modalUpload').modal('show');
            }

            $scope.sumDetails = function(){
                return $scope.formData.total_price =  $scope.formData.detail.reduce( function(a, b){
                    return a + (b['qty'] * b['price']);
                }, 0);
            };

            $scope.appendItemWarehouse = function(v) {
                $scope.detailData = {}
                $scope.detailData.id = Math.round(Math.random() * 9999999)
                $scope.detailData.code = v.code
                $scope.detailData.item_id = v.id
                $scope.detailData.unit_id = v.piece_id
                $scope.detailData.rack_id = v.rack_id
                $scope.detailData.warehouse_receipt_detail_id = v.warehouse_receipt_detail_id
                $scope.detailData.name = v.name
                $scope.detailData.qty = 1
                $scope.detailData.stock = v.qty
                $scope.detailData.price = v.harga_jual
                $scope.detailData.unit = v.piece_name
                $scope.detailData.description = null
                $scope.formData.detail.push($scope.detailData)
            }

            $scope.$on('getItemWarehouse', function(e, v){
                $scope.appendItemWarehouse(v)
                $scope.sumDetails()
            })

            $scope.$on('getItemWarehouses', function(e, items){
                var i
                for(i in items) {
                  $scope.appendItemWarehouse(items[i])
                }
                $scope.sumDetails()
            })

            $scope.deletes = function(id) {
                $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id) 
            }

            $scope.deleteFile = function(id) {
                $scope.disBtn=true;
                customerOrdersService.api.deleteFile(id, function(dt){
                    $scope.show()
                })
            }

            $scope.rejectBtn = () => {
                var is_confirm = confirm($rootScope.solog.label.general.are_you_sure)
                if(is_confirm){
                    customerOrdersService.api.reject($stateParams.id, function(dt){
                        if(dt.data.data){
                            $scope.show()
                        }
                    })
                }
            }

            $scope.approveBtn = () => {
                var is_confirm = confirm($rootScope.solog.label.general.are_you_sure)
                if(is_confirm){
                    customerOrdersService.api.approve($stateParams.id, function(dt){
                        if(dt.data.data){
                            $scope.show()
                        }
                    })
                }
            }

            $scope.submitForm=function() {
                if($stateParams.id) {
                    customerOrdersService.api.update($scope.formData, $stateParams.id, function(){
                        $scope.show()
                    })
                }
            }

            
            $scope.uploadSubmit=function() {
                $scope.disBtn=true;
                $.ajax({
                type: "post",
                url: baseUrl+'/sales/customer_order/'+$stateParams.id+'/upload_file?_token='+csrfToken,
                contentType: false,
                cache: false,
                processData: false,
                data: new FormData($('#uploadForm')[0]),
                success: function(data){
                    $scope.$apply(function() {
                        $scope.disBtn=false;
                    });
                    $('#modalUpload').modal('hide');
                    toastr.success("Data Berhasil Disimpan");
                    $timeout(function() {
                        $state.reload();
                    },1000)
                },
                error: function(xhr, response, status) {
                    $scope.$apply(function() {
                        $scope.disBtn=false;
                    });

                    if (xhr.status==422) {
                        var msgs="";
                        $.each(xhr.responseJSON.errors, function(i, val) {
                            msgs+=val+'<br>';
                        });
                        toastr.warning(msgs,"Validation Error!");
                    } else {
                        toastr.error(xhr.responseJSON.message,"Error has Found!");
                    }
                }
                });
            }
        }
    }
});