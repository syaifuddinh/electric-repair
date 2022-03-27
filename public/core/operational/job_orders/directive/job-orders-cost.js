jobOrders.directive('jobOrdersCost', function () {
    return {
        restrict: 'E',
        scope: {
            jobOrderId : '=',
            hideAddButton: '='
        },
        transclude:true,
        templateUrl: '/core/operational/job_orders/view/job-orders-cost.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $state, $timeout, jobOrdersService) {
            $scope.cost_detail = []
            $scope.cost_type_f={}

            $scope.status=[
                {id:1,name:'Belum Diajukan'},
                {id:2,name:'Diajukan Keuangan'},
                {id:3,name:'Disetujui Keuangan'},
                {id:4,name:'Ditolak'},
                {id:5,name:'Diposting'},
                {id:6,name:'Revisi'},
                {id:7,name:'Diajukan Atasan'},
                {id:8,name:'Disetujui'}
            ]

            console.log($scope.hideAddButton)

            $scope.show = function() {
                if($scope.jobOrderId) {
                    jobOrdersService.api.showCost($scope.jobOrderId, function(list){
                        $scope.cost_detail = list
                    })
                }
            }

            $scope.$watch('jobOrderId', function (){
                $scope.show()
            })

            $scope.searchVendorPrice = function() {
                if($scope.costData.vendor_id && $scope.costData.cost_type_id) {
                    var data = {
                        cost_type_id : $scope.costData.cost_type_id,
                        vendor_id : $scope.costData.vendor_id
                    }
                    $http.get(baseUrl+'/marketing/vendor_price/job_order/search?' + $.param(data)).then(function(data) {
                        $scope.costData.price=data.data;
                        $scope.calcCTTotalPrice()
                    });
                }
            }
            
            $scope.addCost=function() {
                $scope.costData={};
                $scope.costData.is_edit=false;
                $scope.costData.type=1
                $scope.costData.cost_type_id = 144;
                $scope.costData.price=0
                $scope.costData.qty=1
                $scope.costData.total_price=0
                $scope.titleCost = 'Add Cost'
                $('#modalCost').modal('show');
            }
            
            $scope.changeCT=function(id) {
                $http.get(baseUrl+'/setting/cost_type/'+id).then(function(data) {
                    $scope.cost_type_f=data.data.item;

                    $scope.costData.vendor_id=$scope.cost_type_f.vendor_id;
                    $scope.costData.qty=$scope.cost_type_f.qty;
                    $scope.searchVendorPrice()
                });
            }
            $scope.calcCTTotalPrice=function(){
                $scope.costData.total_price=$scope.costData.qty*$scope.costData.price
            }

            $scope.editCost=function(id) {
                $scope.costData={};
                $scope.costData.is_edit=true;
                $scope.costData.id=id;
                $scope.titleCost = 'Edit Cost'
                $http.get(baseUrl+'/operational/job_order/edit_cost/'+id).then(function(data) {
                    var dt=data.data;
                    // $scope.costData.cost_type=dt.cost_type_id;
                    $scope.costData.cost_type_id=parseInt(dt.cost_type_id);
                    $scope.costData.vendor_id=parseInt(dt.vendor_id);
                    $scope.costData.qty=dt.qty;
                    $scope.costData.price=dt.price;
                    $scope.costData.total_price=dt.total_price;
                    $scope.costData.description=dt.description;
                    $scope.costData.type=dt.type;
                    $scope.cost_type_f.is_insurance=dt.is_insurance;
                    $('#modalCost').modal('show');
                });
            }

            $scope.ajukanAtasan=function(id) {
                $rootScope.disBtn=true;
                $http.post(baseUrl+'/operational/job_order/ajukan_atasan',{id:id}).then(function(data) {
                    $scope.show()
                    toastr.success("Biaya Telah Diajukan !");
                    $rootScope.disBtn=false;
                }, function(error) {
                    $rootScope.disBtn=false;
                    if (error.status==422) {
                        var det="";
                        angular.forEach(error.data.errors,function(val,i) {
                            det+="- "+val+"<br>";
                        });
                        toastr.warning(det,error.data.message);
                    } else {
                        toastr.error(error.data.message,"Error Has Found !");
                    }
                });
            }

            $scope.deleteCost=function(id) {
                var cofs=confirm("Apakah anda yakin ?");
                if (!cofs) {
                    return null;
                }
                $http.delete(baseUrl+'/operational/job_order/delete_cost/'+id).then(function(data) {
                    toastr.success("Biaya Order telah dihapus !");
                    $scope.show();
                })
            }

            $scope.approveAtasan=function(id) {
                var cofs=confirm("Apakah anda yakin ?");
                if (!cofs) {
                    return null;
                }
                $rootScope.disBtn=true;
                $http.post(baseUrl+'/operational/job_order/approve_atasan',{id:id}).then(function(data) {
                    $scope.show()
                    toastr.success("Biaya Telah Disetujui !");
                    $rootScope.disBtn=false;
                }, function(error) {
                    $rootScope.disBtn=false;
                    if (error.status==422) {
                        var det="";
                        angular.forEach(error.data.errors,function(val,i) {
                            det+="- "+val+"<br>";
                        });
                        toastr.warning(det,error.data.message);
                    } else {
                        toastr.error(error.data.message,"Error Has Found !");
                    }
                });
            }

            $scope.rejectAtasan=function(id) {
                var cofs=confirm("Apakah anda yakin?");
                if (cofs) {
                    $rootScope.disBtn=true;
                    $http.post(baseUrl+'/operational/job_order/reject_atasan',{id:id}).then(function(data) {
                        $scope.show()
                        toastr.success("Biaya Telah Ditolak !");
                        $rootScope.disBtn=false;
                    }, function(error) {
                        $rootScope.disBtn=false;
                        if (error.status==422) {
                            var det="";
                            angular.forEach(error.data.errors,function(val,i) {
                                det+="- "+val+"<br>";
                            });
                            toastr.warning(det,error.data.message);
                        } else {
                            toastr.error(error.data.message,"Error Has Found !");
                        }
                    });
                }
            }

            $scope.cost_journal=function() {
                $rootScope.disBtn=true;
                $http.post(baseUrl+'/operational/job_order/cost_journal',{id:$stateParams.id}).then(function(data) {
                    $scope.show()
                    toastr.success("Biaya telah dijurnal !");
                    $rootScope.disBtn=false;
                }, function(error) {
                    $rootScope.disBtn=false;
                    if (error.status==422) {
                        var det="";
                        angular.forEach(error.data.errors,function(val,i) {
                            det+="- "+val+"<br>";
                        });
                        toastr.warning(det,error.data.message);
                    } else {
                        toastr.error(error.data.message,"Error Has Found !");
                    }
                });
            }

            $scope.cancel_posting=function(id) {
                let cof = confirm("Apakah Anda Yakin ?")
                if (cof) {
                    $http.post(`${baseUrl}/operational/job_order/cancel_cost_journal/${id}`).then(function(e) {
                        toastr.success("Biaya batal di posting.");
                        $scope.show()
                    })
                }
            }

            $scope.submitCost=function() {
                $rootScope.disBtn=true;
                $http.post(baseUrl+'/operational/job_order/add_cost/'+$scope.jobOrderId,$scope.costData).then(function(data) {
                    $('#modalCost').modal('hide');
                    $scope.show()
                    toastr.success("Biaya berhasil disimpan!");
                    $rootScope.disBtn=false;
                }, function(error) {
                    $rootScope.disBtn=false;
                    if (error.status==422) {
                        var det="";
                        angular.forEach(error.data.errors,function(val,i) {
                            det+="- "+val+"<br>";
                        });
                        toastr.warning(det,error.data.message);
                    } else {
                        toastr.error(error.data.message,"Error Has Found !");
                    }
                });
            }
        }
    }
});