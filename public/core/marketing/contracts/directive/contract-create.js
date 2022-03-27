contracts.directive('contractCreate', function () {
    return {
        restrict: 'E',
        scope: {
            is_sales_contract : "=isSalesContract",
            index_route : "=indexRoute",
            id : "=id",
            store_url : "=storeUrl",
            customer_id : "=customerId",
            sales_id : "=salesId",
            description_inquery : "=descriptionInquery",
            customer_stage_id : "=customerStageId",
            no_inquery : "=noInquery"
        },
        require:'ngModel',
        templateUrl: '/core/marketing/contracts/view/contract-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, contractsService) {
            $('.ibox-content').addClass('sk-loading');

            $scope.$watch('customer_id', function(){
                $scope.formData.customer_id = $scope.customer_id;
            })

            $scope.$watch('no_inquery', function(){
                $scope.formData.no_inquery = $scope.no_inquery;
            })

            $scope.$watch('sales_id', function(){
                $scope.formData.sales_id = $scope.sales_id;
            })

            $scope.$watch('customer_stage_id', function(){
                $scope.formData.customer_stage_id = $scope.customer_stage_id;
            })

            $scope.$watch('description_inquery', function(){
                $scope.formData.description_inquery = $scope.description_inquery;
            })

            $scope.formData={
                company_id:compId,
                bill_type:1,
                date_inquery:dateNow
            }

            $scope.show = function() {
                if($scope.id) {
                    $http.get(baseUrl+'/marketing/inquery/' + $scope.id).then(function(data) {
                        $scope.data=data.data;
                        var dt=data.data.item;
                        //alert(dt.company_id);
                        $scope.formData={
                            name:dt.name,
                            company_id:dt.company_id,
                            bill_type:dt.bill_type,
                            send_type:dt.send_type,
                            customer_id:dt.customer_id,
                            sales_id:dt.sales_id,
                            customer_stage_id:dt.customer_stage_id,
                            no_inquery:dt.no_inquery,
                            price_full_inquery:dt.price_full_inquery,
                            description_inquery:dt.description_inquery,
                            imposition:dt.imposition,
                            piece_id:dt.piece_id,
                            date_inquery:$filter('minDate')(dt.date_inquery),
                        }
                        $('.ibox-content').removeClass('sk-loading');
                    }, function(err) {
                        toastr.error(err.data.message,"Maaf!");
                        $state.go("marketing.inquery");
                    });
                }
            }
            $scope.show()

            $scope.imposition=[
                {id:1,name:"Kubikasi"},
                {id:2,name:"Tonase"},
                {id:3,name:"Item"},
                {id:4,name:"Borongan"},
            ];

            $scope.send_type=[
                {id:1,name:"Sekali"},
                {id:2,name:"Per Hari"},
                {id:3,name:"Per Minggu"},
                {id:4,name:"Per Bulan"},
                {id:5,name:"Tidak Tentu"},
            ];

            $scope.formData.send_type = 5

            $scope.bill_type=[
                {id:1,name:"Per Pengiriman"},
                {id:2,name:"Borongan"},
            ];

            contract_datatable = $('#contract_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX:false,
                ajax : {
                    headers : {'Authorization' : 'Bearer '+authUser.api_token},
                    url : baseUrl+'/api/marketing/contract_datatable',
                    dataSrc: function(d) {
                        $('.ibox-content').removeClass('sk-loading');
                        return d.data;
                    }
                },
                columns:[
                    {
                        data:null,
                        orderable:false,
                        searchable:false,
                        className:'text-center',
                        render : resp => '<a ng-click="selectContract($event.currentTarget)"><i class="fa fa-check"></i></a>'
                    },
                    {data:"no_contract",name:"no_contract"},
                    {data:"name",name:"name"},
                    {data:"customer.name",name:"customer.name"},
                    {data:"sales.name",name:"sales.name"},
                ],
                createdRow: function(row, data, dataIndex) {
                    $compile(angular.element(row).contents())($scope);
                }
            });

            $scope.showContract = function() {
                $('#contractModal').modal()
            }

            $scope.selectContract = function(e) {
                var tr = $(e).parents('tr')
                var data = contract_datatable.row(tr).data()
                $scope.template_contract_name = data.name + '(' + data.no_contract + ')'
                $scope.formData.template_contract_id = data.id
                $('#contractModal').modal('hide')
            }

            $scope.removeContract = function() {
                $scope.template_contract_name = null
                $scope.formData.template_contract_id = null
            }

            $scope.changeBillType=function() {
                $scope.formData.price_full_inquery=0;
                $scope.formData.imposition=null;
                $scope.changeImposition();
            }

            $scope.changeImposition=function() {
                delete $scope.formData.piece_id
            }

            $http.get(baseUrl+'/marketing/inquery/create').then(function(data) {
                $scope.data=data.data;
                if (data.data.negotiation) {
                    $scope.formData.customer_stage_id=data.data.negotiation.id;
                }
                $('.ibox-content').removeClass('sk-loading');
            });

            $scope.disBtn=false;

            $scope.back = function() {
                if($scope.index_route) {
                    $state.go($scope.index_route);
                } else {
                    $state.go('marketing.inquery');
                }
            }

            $scope.submitForm=function() {
                var url = baseUrl+'/marketing/inquery';
                var payload = $scope.formData
                var method

                if($scope.store_url) {
                    url = $scope.store_url
                }

                if($scope.id) {
                    method = "put";
                    url += "/" + $scope.id
                } else {
                    method = "post";
                }


                $scope.disBtn=true;
                
                $http[method](url, payload).then(function(resp) {
                    $scope.disBtn = false;
                    toastr.success(resp.data.message)
                    $scope.back()
                }, function(error) {
                    $scope.disBtn = false;
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