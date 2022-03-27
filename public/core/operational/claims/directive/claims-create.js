operationalClaims.directive('operationalClaimsCreate', function () {
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
        templateUrl: '/core/operational/claims/view/claims-create.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, operationalClaimsService) {

            $rootScope.pageTitle = "Klaim";
            $scope.isFilter = false;
            $scope.formData = {}
            $scope.formData.company_id = compId
            $scope.formData.claim_type = 1
            $scope.formData.date_transaction=dateNow
            $scope.formData.detail = []
            $scope.data = {}
            $scope.detail = {}

            $scope.pengenaan = [
                {id : 1, name : 'Driver'},
                {id : 2, name : 'Perusahaan Vendor'},
                {id : 3, name : 'Internal'}
            ]

            $scope.claim_subjects = [
                {id : 1, name : 'Job Order'},
                {id : 2, name : 'Sales Order'}
            ]

            $scope.changePengenaan = function() {
                $scope.formData.driver_id = null
                $scope.formData.vendor_id = null
            }

            $scope.showClaimCategories = function() {
                $http.get(baseUrl + '/operational/claim_categories').then(function(data) {
                    var claim_categories = data.data;
                    $scope.claim_categories = claim_categories
                    $scope.data.claim_categories = $rootScope.chunk(claim_categories, 4)
                    console.log($scope.data.claim_categories)
                    $scope.resetDetail()
                }, function() {
                    $scope.showClaimCategories()
                });
            }
            $scope.showClaimCategories()

            var job_order_datatable = $('#job_order_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                initComplete: null,
                ajax: {
                  headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                  },
                  url: baseUrl + '/api/operational/job_order_datatable',
                  data: function(d) {
                    d.customer_id = $scope.formData.customer_id;
                  },
                  dataSrc: function(d) {
                    return d.data;
                  }
                },
                columns: [
                  {
                    data:null,
                    searchable:false,
                    orderable:false,
                    className : 'text-center',
                    render : function(resp) {
                        return '<button class="btn btn-sm btn-primary" ng-click="selectJobOrder($event.currentTarget)">Pilih</button>'
                    }
                  },
                  { data: "code", name: "jo.code" },
                  { data: null, name: 'jo.shipment_date', render: e => $filter('fullDate')(e.shipment_date) },
                  { data: "sender_name", name: "sender.name", },
                  { data: "receiver_name", name: "receiver.name", },
                  { data: "trayek", name: "r.name" }
                ],
                columnDefs: [{
                  targets: 0,
                  width: '5px'
                }],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
            });

            var job_order_detail_datatable = $('#job_order_detail_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                initComplete: null,
                ajax: {
                    headers: {
                        'Authorization': 'Bearer ' + authUser.api_token
                    },
                    url: baseUrl + '/api/operational/job_order_detail_datatable',
                    data: function(d) {
                        d.job_order_id = $scope.formData.job_order_id;
                    },
                    dataSrc: function(d) {
                        return d.data;
                    }
                },
                columns: [
                    {   
                        data:null, searchable:false, orderable:false, className : 'text-center',
                        render : function(resp) {
                            return '<button class="btn btn-sm btn-primary" ng-click="selectJobOrderDetail($event.currentTarget)">Pilih</button>'
                        }
                    },
                    { data: "commodity_name", name: "commodities.name" },
                    { data: null, searchable:false, name: 'job_order_details.qty', className : 'text-right', render: e => $filter('number')(e.qty) },
                    { data: null, searchable:false, name: 'job_order_details.price', className : 'text-right', render: e => $filter('number')(e.price) },
                    { data: null, searchable:false, name: 'job_order_details.total_price', className : 'text-right', render: e => $filter('number')(e.total_price) },
                    { data: "description", name: "job_order_details.description" }
                ],
                columnDefs: [{
                    targets: 0,
                    width: '5px'
                }],
                createdRow: function(row, data, dataIndex) {
                    $compile(angular.element(row).contents())($scope);
                }
            });

            var sales_order_datatable = $('#sales_order_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                initComplete: null,
                ajax: {
                  headers: {
                    'Authorization': 'Bearer ' + authUser.api_token
                  },
                  url: baseUrl + '/api/sales/sales_order_datatable',
                  data: function(d) {
                    d.customer_id = $scope.formData.customer_id;
                  },
                  dataSrc: function(d) {
                    return d.data;
                  }
                },
                columns: [
                  {
                    data:null,
                    searchable:false,
                    orderable:false,
                    className : 'text-center',
                    render : function(resp) {
                        return '<button class="btn btn-sm btn-primary" ng-click="selectSalesOrder($event.currentTarget)">Pilih</button>'
                    }
                  },
                  { data: "code", name: "code" },
                  { data: null, name: 'shipment_date', render: e => $filter('fullDate')(e.shipment_date) },
                  { data: "customer_name", name: "customer_name", }
                ],
                columnDefs: [{
                  targets: 0,
                  width: '5px'
                }],
                createdRow: function(row, data, dataIndex) {
                  $compile(angular.element(row).contents())($scope);
                }
            });

            var sales_order_detail_datatable = $('#sales_order_detail_datatable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: false,
                initComplete: null,
                ajax: {
                    headers: {
                        'Authorization': 'Bearer ' + authUser.api_token
                    },
                    url: baseUrl + '/api/sales/sales_order_detail_datatable',
                    data: function(d) {
                        d.sales_order_id = $scope.formData.sales_order_id;
                    },
                    dataSrc: function(d) {
                        return d.data;
                    }
                },
                columns: [
                    {   
                        data:null, searchable:false, orderable:false, className : 'text-center',
                        render : function(resp) {
                            return '<button class="btn btn-sm btn-primary" ng-click="selectSalesOrderDetail($event.currentTarget)">Pilih</button>'
                        }
                    },
                    { data: "commodity_name", name: "commodities.name" },
                    { data: null, searchable:false, name: 'job_order_details.qty', className : 'text-right', render: e => $filter('number')(e.qty) },
                    { data: null, searchable:false, name: 'job_order_details.price', className : 'text-right', render: e => $filter('number')(e.price) },
                    { data: null, searchable:false, name: 'job_order_details.total_price', className : 'text-right', render: e => $filter('number')(e.total_price) },
                    { data: "description", name: "job_order_details.description" }
                ],
                columnDefs: [{
                    targets: 0,
                    width: '5px'
                }],
                createdRow: function(row, data, dataIndex) {
                    $compile(angular.element(row).contents())($scope);
                }
            });

            $scope.openJobOrder = function() {
                if(!$scope.formData.customer_id) {
                        toastr.error('Silahkan pilih customer terlebih dahulu')
                } else {
                    job_order_datatable.ajax.reload()
                    $('#modalJO').modal('show')
                }
            }

            $scope.openJobOrderDetail = function() {
                if(!$scope.formData.job_order_id) {
                     toastr.error('Silahkan pilih job order terlebih dahulu')
                } else {
                    job_order_detail_datatable.ajax.reload()
                    $('#modalJobOrderDetail').modal('show')
                }
            }

            $scope.selectJobOrder = function(e) {
                var tr = $(e).parents('tr')
                var dt = job_order_datatable.row(tr).data()
                $scope.detail.job_order_detail_id = null
                $scope.formData.job_order_id = dt.id
                $scope.formData.job_order_code = dt.code
                $scope.formData.claim_type = 1
                $scope.formData.driver_id = parseInt(dt.driver_id)
                $scope.resetJobOrderDetail()
                $('#modalJO').modal('hide')
            }

            $scope.selectJobOrderDetail = function(e) {
                var tr = $(e).parents('tr')
                var dt = job_order_detail_datatable.row(tr).data()
                console.log(dt)
                $scope.detail.job_order_detail_id = dt.id
                $scope.detail.commodity_id = dt.commodity_id
                $scope.detail.qty = dt.qty
                $scope.detail.claim_qty = 0
                $scope.findCommodity()
                $('#modalJobOrderDetail').modal('hide')
            }

            $scope.resetJobOrder = function() {
                $scope.formData.job_order_id = null
            }

            $scope.resetJobOrderDetail = function() {
                if($scope.detail.type == 1) {
                   $scope.detail.commodity_id = null
                   $scope.detail.job_order_detail_id = null
                   $scope.detail.commodity_name = null
                }
            }

            $scope.openSalesOrder = function() {
                if(!$scope.formData.customer_id) {
                        toastr.error('Silahkan pilih customer terlebih dahulu')
                } else {
                    sales_order_datatable.ajax.reload()
                    $('#modalSO').modal('show')
                }
            }

            $scope.openSalesOrderDetail = function() {
                if(!$scope.formData.sales_order_id) {
                     toastr.error('Silahkan pilih sales order terlebih dahulu')
                } else {
                    sales_order_detail_datatable.ajax.reload()
                    $('#modalSalesOrderDetail').modal('show')
                }
            }

            $scope.selectSalesOrder = function(e) {
                var tr = $(e).parents('tr')
                var dt = sales_order_datatable.row(tr).data()
                $scope.detail.sales_order_detail_id = null
                $scope.formData.sales_order_id = dt.id
                $scope.formData.sales_order_code = dt.code
                $scope.resetSalesOrderDetail()
                $('#modalSO').modal('hide')
            }

            $scope.selectSalesOrderDetail = function(e) {
                var tr = $(e).parents('tr')
                var dt = sales_order_detail_datatable.row(tr).data()
                console.log(dt)
                $scope.detail.sales_order_detail_id = dt.id
                $scope.detail.commodity_id = dt.commodity_id
                $scope.detail.qty = dt.qty
                $scope.detail.claim_qty = 0
                $scope.findCommodity()
                $('#modalSalesOrderDetail').modal('hide')
            }

            $scope.resetSalesOrderDetail = function() {
                if($scope.detail.type == 3) {
                   $scope.detail.commodity_id = null
                   $scope.detail.sales_order_detail_id = null
                   $scope.detail.commodity_name = null
                }
            }

            $scope.show = function() {
                if($stateParams.id) {
                    $http.get(baseUrl + '/operational/claims/' + $stateParams.id).then(function(data) {
                      var detail = $scope.formData.detail
                      $scope.formData = data.data;
                      $scope.formData.date_transaction = $filter('minDate')(data.data.date_transaction)
                      $scope.formData.detail = detail
                    }, function() {
                          $scope.show()
                    });
                }
            }
            $scope.show()

            $scope.showDetail = function() {
                if($stateParams.id) {
                    $http.get(baseUrl + '/operational/claims/' + $stateParams.id + '/detail').then(function(data) {
                      $scope.formData.detail = data.data
                      $scope.countGrandtotal()
                    }, function() {
                          $scope.showDetail()
                    });
                }
            }
            $scope.showDetail()

            $scope.resetDetail = function() {
                $scope.detail = {}
                $scope.detail.claim_categories = $scope.claim_categories
            }

            $scope.deleteDetail = function(val) {
                var detail = $scope.formData.detail.filter(x => x.commodity_id != val.commodity_id && x.qty != val.qty && x.price != val.price)
                $scope.formData.detail = detail
                $scope.countGrandtotal()
            }

            $scope.showCommodity = function() {
                $http.get(baseUrl + '/setting/general/commodity').then(function(data) {
                  $scope.data.commodity = data.data;
                }, function() {
                  $scope.showCommodity()
                });
            }
            $scope.showCommodity()

            $scope.findCommodity = function() {
                $http.get(baseUrl + '/setting/general/commodity/' + $scope.detail.commodity_id).then(function(data) {
                  $scope.detail.price = data.data.price;
                  $scope.detail.claim_price = data.data.price;
                  $scope.detail.commodity_name = data.data.name;
                  $scope.detail.total_price=$scope.detail.qty*$scope.detail.price
                });
            }

            $scope.appendTable = function() {
                if(!$scope.detail.commodity_id) {
                   toastr.error('Komoditas wajib diisi')
                } else {
                    $scope.detail.qty = $scope.detail.qty || 0
                    $scope.detail.price = $scope.detail.price || 0
                    $scope.detail.total_price = $scope.detail.total_price || 0
                    $scope.detail.claim_qty = $scope.detail.claim_qty || 0
                    $scope.detail.claim_price = $scope.detail.claim_price || 0
                    $scope.detail.claim_total_price = $scope.detail.claim_total_price || 0
                    $scope.formData.detail.push($scope.detail)
                    $scope.resetDetail()
                    $scope.countGrandtotal()
                }
            }

            $scope.countGrandtotal = function() {
                var grandtotal = 0
                for(d in $scope.formData.detail) {
                   D = $scope.formData.detail[d]
                   grandtotal += (D.qty * D.price)
                }
                $scope.formData.total = grandtotal
            }

            $scope.submitForm = function() {
                var method = 'post'
                var url = operationalClaimsService.url.store()
                $scope.disBtn  = true
                if($stateParams.id) {
                    var method = 'put'
                    var url = operationalClaimsService.url.update($stateParams.id)
                }
                $http[method](url, $scope.formData).then(function(data) {
                    $scope.disBtn=false;
                    toastr.success(data.data.message);
                    $state.go('operational.claims')
                }, function (xhr) {
                    $scope.disBtn=false;
                    if (xhr.status==422) {
                        var msgs="";
                        $.each(xhr.data.errors, function(i, val) {
                            msgs+=val+'<br>';
                        });
                        toastr.warning(msgs,"Validation Error!");
                    } else {
                        toastr.error(xhr.data.message,"Error has Found!");
                    }
                })
            }
        }
    }
});