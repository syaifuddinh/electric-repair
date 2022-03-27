customerOrders.directive('customerOrdersCreate', function () {
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
        templateUrl: '/core/sales/customer_orders/view/customer-order-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $filter, $state, $stateParams, $timeout, $compile, jobOrdersService, customerOrdersService, additionalFieldsService, unitsService) {
            $rootScope.disBtn = false
            $rootScope.pageTitle=$rootScope.solog.label.general.add + ' ' + $rootScope.solog.label.customer_order.title
            $('.ibox-content').addClass('sk-loading');
            $scope.formData = {};
            $scope.formData.date_transaction=dateNow
            $scope.formData.detail=[]
            $scope.files=[]
            $scope.detailData={}
            $scope.detailData.qty=1
            $scope.detailData.stock=0

            $scope.showDetail = function() {
                if($stateParams.id) {
                    customerOrdersService.api.showDetail($stateParams.id, function(dt){
                        dt = dt.map(function(v){
                            v.code = v.item_code
                            v.name = v.item_name

                            return v
                        })
                        $scope.formData.detail = dt
                    })
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    customerOrdersService.api.show($stateParams.id, function(dt){
                        dt.date_transaction = $filter('minDate')(dt.date_transaction)
                        $scope.formData = dt
                        $scope.showDetail()
                    })
                }
            }
            $scope.show()

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
            })

            $scope.$on('getItemWarehouses', function(e, items){
                var i
                for(i in items) {
                  $scope.appendItemWarehouse(items[i])
                }
            })

            $scope.deletes = function(id) {
                $scope.formData.detail = $scope.formData.detail.filter(x => x.id != id) 
            }

            file_upload = $('#file_upload').dropzone({
                init: function () {
                    this.on("addedfile", function (file) {
                        $scope.files.push(file);
                        console.log($scope.files);     
                    });
        
                    this.on("removedfile", function (file) {
                        for (x in $scope.files) {
                            if ($scope.files[x].upload.uuid == file.upload.uuid) {
                                $scope.files.splice(x, 1);
                            }
                        }
                        console.log($scope.files);
        
                    });
                }
            });

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route)
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $state.go('sales_order.customer_order');
                    }
                }
            }

            $scope.submitForm=function() {
                var fd = new FormData();
                for (x in $scope.formData) {
                    if (x != 'detail') {
                        fd.append(x, $scope.formData[x]);
                    } else {
                        fd.append('detail', JSON.stringify($scope.formData.detail));
                    }
                }
  
                for (x in $scope.files) {
                    fd.append('files[]', $scope.files[x]);
                }
                $('.submitButton').attr('disabled', 'disabled');
                $.ajax({
                    url: customerOrdersService.url.store() + '?_token=' + csrfToken,
                    contentType: false,
                    processData: false,
                    type: 'POST',
                    data: fd,
                    beforeSend: function (request) {
                        request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
                    },
                    success: function (data) {
                        toastr.success("Data Berhasil Disimpan!");
                        $scope.back()
                        $('.submitButton').removeAttr('disabled');
                    },
                    error: function (xhr) {
                        var err = xhr.responseJSON
                        $('.submitButton').removeAttr('disabled');
                        if (xhr.status==422) {
                            var det="";
                            angular.forEach(err.errors,function(val,i) {
                                det+="- "+val+"<br>";
                            });
                            toastr.warning(det,err.message);
                        } else {
                            toastr.error(err.message, "Error Has Found !");
                        }
                    }
                });
            }
        }
    }
})