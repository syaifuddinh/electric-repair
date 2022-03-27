contracts.directive('contractShow', function () {
    return {
        restrict: 'E',
        scope: {
            index_route : "=indexRoute",
            create_contract_route : "=createContractRoute",
            hide_service : "=hideService"
        },
        require:'ngModel',
        templateUrl: '/core/marketing/contracts/view/contract-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, $stateParams, contractsService, operationalClaimsService, hardList) {
            $scope.id = $stateParams.id
            $scope.descriptionData={}
            $scope.baseUrl=baseUrl;
            $('.sk-container').addClass('sk-loading');

            $scope.formData = {}
            $scope.formData.quotation_id = $stateParams.id
            $scope.formData.detail_items = []

            $('#item_detail').hide()
            $scope.openService = function() {
                $('.tab-item').hide()
                $('#service_detail').show()
            }
            $scope.openItem = function() {
                $('.tab-item').hide()
                $('#item_detail').show()
            }
            $scope.openCost = function() {
                $('.tab-item').hide()
                $('#cost_detail').show()
            }

            $scope.showItems = function() {
              $scope.$broadcast('showItemsModal', 0)
            }
            
            $scope.showCosts = function() {
              $scope.formQuoCost={};
              $scope.cost_type_data={};
              $('#costsModal').modal('show');
            }

            $scope.createContract = function() {
                if($scope.create_contract_route) {
                    $state.go($scope.create_contract_route, { id : $scope.data.item.id })
                } else {
                    $state.go("marketing.inquery.show.add_contract", { id : $scope.data.item.id })
                }
            }

            $scope.adjustTab = function() {
                if($scope.hide_service) {
                    $scope.openItem()
                    var service_tab = $('#service_tab')
                    service_tab.parent('li').hide();

                    var item_tab = $('#item_tab')
                    item_tab.parent('li').addClass('active')
                } else {
                    $scope.openService()
                }
            }
            $scope.adjustTab()

            // $scope.itemDetail = {}
            // $scope.editItemPrice = function() {
            //   $scope.itemDetail.is_edit = true
            //   $scope.$broadcast('editItemPriceByQuotation', $stateParams.id)
            // }

            // $scope.abortItemPrice = function() {
            //   $scope.itemDetail.is_edit = false
            //   $scope.$broadcast('abortItemPrice')
            // }

            $scope.addItem = function(jsn) {
                var id = Math.round(Math.random() * 9999999999)
                var params = {}
                params.id = id
                params.item_name = jsn.name
                params.item_code = jsn.code
                params.item_id = jsn.id
                params.price_list = jsn.harga_jual
                params.price = jsn.harga_jual

                $scope.formData.detail_items.push(params)
            }

            $scope.$on('getItem', function(e, v){
              $scope.addItem(v)
            })
            $scope.$on('getItems', function(e, items){
                for(i in items) {
                    $scope.addItem(items[i])
                }
            })

            $scope.deleteItem = function(id) {
              $scope.formData.detail_items = $scope.formData.detail_items.filter(x => x.id != id) 
          }

            $scope.show = function() {
                $http.get(baseUrl+'/marketing/inquery/'+$stateParams.id).then(function(data) {
                  $scope.data=data.data;
                  var details = data.data.details
                  var items = data.data.detail_items.map(function(item){
                      var i = {}

                      i.id = item.id
                      i.item_name = item.item.name
                      i.item_code = item.item.code
                      i.item_id = item.item.id
                      i.price_list = item.item.harga_jual
                      i.price = item.price
                      return i;
                  })
                  $scope.data.details = details
                  $scope.formData.detail_items = items

                  $scope.descriptionData.description_inquery=$scope.data.item.description_inquery;
                  $scope.is_approve_count=0;
                  angular.forEach(data.data.details, function(val,i) {
                    $scope.is_approve_count+=(val.is_approve?0:1);
                  })
                  $scope.hitungSum()
                  $('.sk-container').removeClass('sk-loading');
                }, function(){
                  $scope.show()
                });
            }
            $scope.show()

            $scope.imposition=[
                {id:1,name:"Kubikasi"},
                {id:2,name:"Tonase"},
                {id:3,name:"Item"},
                {id:4,name:"Borongan"}
            ];

            $scope.bill_type=[
                {id:1,name:'<span class="badge badge-success">PER PENGIRIMAN</span>'},
                {id:2,name:'<span class="badge badge-warning">BORONGAN</span>'},
            ]

            $scope.cancelQuotation=function() {
            var cofs=confirm("Apakah anda yakin ingin membatalkan Quotation ini?");
            if (cofs) {
              $http.post(baseUrl+'/marketing/inquery/cancel_quotation/'+$stateParams.id).then(function(data) {
                toastr.success("Quotation telah dibatalkan!");
                $state.reload();
              });
            }
            }

            $scope.cancelCancelQuotation=function() {
            var cofs=confirm("Apakah anda ingin mengembalikan Quotation ini?");
            if (cofs) {
              $http.post(baseUrl+'/marketing/inquery/cancel_cancel_quotation/'+$stateParams.id).then(function(data) {
                toastr.success("Berhasil!");
                $state.reload();
              });
            }
            }

            $scope.approveDetail=function(id) {
            var confs=confirm("Apakah Anda ingin menyetujui item penawaran ini ?");
            if (confs) {
              $http.post(baseUrl+'/marketing/inquery/approve_detail/'+id).then(function(data) {
                toastr.success("Detail berhasil disetujui!");
                $state.reload();
              });
            }
            }

            $scope.status_approve=[
            {id:1,name:'Penawaran'},
            {id:2,name:'Penawaran Diajukan'},
            {id:3,name:'Penawaran Disetujui'},
            {id:4,name:'Kontrak'},
            {id:5,name:'Ditolak'},
            {id:6,name:'Quotation Dibatalkan'},
            ];
            // console.log(hardList);
            $scope.hardList=hardList;
            $scope.deletes=function(ids) {
            var cfs=confirm("Apakah Anda Yakin?");
            if (cfs) {
              $http.delete(baseUrl+'/marketing/inquery/delete_detail/'+ids,{_token:csrfToken}).then(function success(data) {
                toastr.success("Data Berhasil Dihapus!");
                $scope.show();
              }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
              });
            }
            }

            $scope.editDescription=function() {
              $('#descriptionModal').modal('show');
            }

            $scope.hitungSum=function() {
                $scope.totalPenawaran=0
                $scope.totalKontrak=0
                $scope.totalBiaya=0
                angular.forEach($scope.data.details, function(val,i) {
                  $scope.totalPenawaran+=val.price_inquery_tonase+val.price_inquery_volume+val.price_inquery_item+val.price_inquery_full;
                  $scope.totalKontrak+=val.price_contract_tonase+val.price_contract_volume+val.price_contract_item+val.price_contract_full;
                  $scope.totalBiaya+=val.cost;
                });

                var margin=(($scope.totalPenawaran-$scope.totalBiaya)/$scope.totalPenawaran)*100;
                $scope.margin=margin.toFixed(2);
                // console.log('Margin: '+margin.toFixed(2));
            }

            $scope.delete_cost=function(ids) {
              var cfs=confirm("Apakah Anda Yakin?");
              if (cfs) {
                $http.delete(baseUrl+'/marketing/inquery/delete_detail_cost/'+ids,{_token:csrfToken}).then(function success(data) {
                  // $state.reload();
                  oTable.ajax.reload();
                  oTableCost.ajax.reload();
                  $scope.total_cost=data.data.total_cost;
                  toastr.success("Data Berhasil Dihapus!");
                }, function error(data) {
                  toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
                });
              }
            }

            $scope.quotation_detail_id=0;

            oTable = $('#detail_datatable').DataTable({
              processing: true,
              serverSide: true,
              ordering: false,
              searching: false,
              scrollX : false,
              paging: false,
              ajax : {
                headers : {'Authorization' : 'Bearer '+authUser.api_token},
                url : baseUrl+'/api/marketing/inquery_detail_cost_datatable',
                data: function(d) {
                  d.quotation_detail_id=$scope.quotation_detail_id;
                }
              },
              columns:[
                {data:"cost_type.code",name:"cost_type.code"},
                {data:"cost_type.name",name:"cost_type.name"},
                {data:"vendor.name",name:"vendor.name"},
                {data:"total",name:"total"},
                {data:"cost",name:"cost"},
                {data:"total_cost",name:"total_cost"},
                {data:"action",name:"action",className:"text-center"},
              ],
              createdRow: function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
              }
            });
            
            oTableCost = $('#quotation_costs_datatable').DataTable({
              processing: true,
              serverSide: true,
              ordering: false,
              searching: false,
              scrollX : false,
              paging: false,
              ajax : {
                headers : {'Authorization' : 'Bearer '+authUser.api_token},
                url : baseUrl+'/api/marketing/inquery_detail_cost_datatable',
                data: function(d) {
                  d.quotation_id = $stateParams.id
                }
              },
              columns:[
                {data:"cost_type.code",name:"cost_type.code"},
                {data:"cost_type.name",name:"cost_type.name"},
                {data:"vendor.name",name:"vendor.name"},
                {data:"total",name:"total"},
                {data:"cost",name:"cost"},
                {data:"total_cost",name:"total_cost"},
                {data:"action_data",name:"action_data",className:"text-center",
                  render: function(resp){
                    return '<a ng-if="$root.in_array(data.item.status_approve,[1,2])" ng-click=\"delete_cost('+resp.id+')\"><span class="fa fa-trash-o"></span></a>'
                  }
                },
              ],
              createdRow: function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
              }
            });

            $scope.ajukan=function() {
              $http.post(baseUrl+'/marketing/inquery/ajukan/'+$stateParams.id).then(function(res) {
                toastr.success("Quotation telah diajukan.","Selamat!");
                $state.reload();
              }, function(err){
                if (err.status==422) {
                  var msgs="";
                  $.each(err.data.errors, function(i, val) {
                    msgs+=val+'<br>';
                  });
                  toastr.warning(msgs,"Validation Error!");
                } else {
                  toastr.error(err.data.message,"Error Has Found!");
                }
              });
            }
            $scope.reject=function() {
              var confs=confirm("Apakah Anda ingin membatalkan quotation ?");
              if (confs) {
                $http.post(baseUrl+'/marketing/inquery/reject/'+$stateParams.id).then(function(res) {
                  toastr.success("Berhasil!");
                  $state.reload();
                });
              }
            }
            $scope.approveInquery=function() {
              $http.post(baseUrl+'/marketing/inquery/approve/'+$stateParams.id).then(function(res) {
                toastr.success("Quotation telah disetujui.","Selamat!");
                $state.reload();
              });
            }
            $scope.approveManager=function() {
              $http.post(baseUrl+'/marketing/inquery/approve_manager/'+$stateParams.id).then(function(res) {
                toastr.success("Quotation telah disetujui.","Selamat!");
                $state.reload();
              });
            }
            $scope.approveDirection=function() {
              $http.post(baseUrl+'/marketing/inquery/approve_direction/'+$stateParams.id).then(function(res) {
                toastr.success("Quotation telah disetujui.","Selamat!");
                $state.reload();
              });
            }

            $scope.detail_cost=function(ids) {
              if($rootScope.roleList.includes('marketing.quotation.detail.detail_info.detail_cost')) {
                  $scope.quotation_detail_id=ids;
                  // console.log($scope.quotation_detail_id);
                  $scope.div_form_detail=false;
                  $scope.button_form_detail=true;

                  $scope.detailCost={};
                  $scope.formCost={};
                  $scope.formCost.quotation_detail_id=ids;
                  oTable.ajax.reload();
                  $http.get(baseUrl+'/marketing/inquery/detail_cost/'+ids).then(function(data) {
                    var dts=data.data;
                    $scope.detailCost.route=(dts.route_id?dts.route.name:'');
                    $scope.detailCost.commodity=(dts.commodity_id?dts.commodity.name:'');
                    $scope.detailCost.penawaran=dts.price_inquery_tonase+dts.price_inquery_volume+dts.price_inquery_item+dts.price_inquery_full;
                    $scope.detailCost.kontrak=dts.price_contract_tonase+dts.price_contract_volume+dts.price_contract_item+dts.price_contract_full;
                    $scope.detailCost.description=dts.description_inquery;
                    // $scope.detailCost.cost=dts.cost;
                    $scope.total_cost=dts.cost;
                    $('#modal_detail').modal('show');
                  });
              } else {
                  toastr.error('Akses tidak diizinkan')
              }
            }

            $('#modal_detail').on('hidden.bs.modal', function() {
              $scope.show()
            })

            $scope.cancel_cost=function() {
              $scope.div_form_detail=false;
              $scope.button_form_detail=true;
            }

            $scope.addCost=function() {
              $scope.formCost={};
              $scope.cost_type_data={};
              $scope.formCost.quotation_detail_id=$scope.quotation_detail_id;
              $scope.div_form_detail=true;
              $scope.button_form_detail=false;
            }
            $scope.cost_type_data={};
            $scope.changeCT=function(id) {
              $http.get(baseUrl+'/setting/cost_type/'+id).then(function(res) {
                // console.log(res);
                $scope.cost_type_data=res.data.item;
                $scope.formCost.total=res.data.item.qty;
                $scope.formCost.cost=res.data.item.cost;
                $scope.formCost.total_cost=res.data.item.initial_cost;
                $scope.formCost.is_internal=(res.data.item.vendor_id?0:1);
                $scope.formCost.vendor_id=res.data.item.vendor_id;
              });
            }

            $scope.disBtn=false;
            $scope.submitDetailCost=function() {
              $scope.disBtn=true;
              $http.post(baseUrl+'/marketing/inquery/store_detail_cost/'+$stateParams.id,$scope.formCost).then(function(data) {
                $scope.disBtn=false;
                toastr.success("Data Berhasil Disimpan");
                $scope.div_form_detail=false;
                $scope.button_form_detail=true;
                $scope.total_cost=data.data.total_cost
                oTable.ajax.reload();
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

            $scope.changeQuoCT=function(id) {
              $http.get(baseUrl+'/setting/cost_type/'+id).then(function(res) {
                // console.log(res);
                $scope.cost_type_data=res.data.item;
                $scope.formQuoCost.total=res.data.item.qty;
                $scope.formQuoCost.cost=res.data.item.cost;
                $scope.formQuoCost.total_cost=res.data.item.initial_cost;
                $scope.formQuoCost.is_internal=(res.data.item.vendor_id?0:1);
                $scope.formQuoCost.vendor_id=res.data.item.vendor_id;
              });
            }

            $scope.submitQuoCost=function() {
              $scope.disBtn=true;
              $http.post(baseUrl+'/marketing/inquery/store_cost/'+$stateParams.id,$scope.formQuoCost).then(function(data) {
                $scope.disBtn=false;
                toastr.success("Data Berhasil Disimpan");
                $('#costsModal').modal('hide');
                oTableCost.ajax.reload();
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

            $scope.submitDescription=function() {
                var url = baseUrl+'/marketing/inquery/store_description/'+$stateParams.id
                $scope.disBtn=true;
                var payload = $scope.descriptionData

                $http.post(url, payload).then(function(resp) {
                    $scope.disBtn = false;
                    $('#descriptionModal').modal('hide');
                    $scope.show()
                    toastr.success(resp.data.message)
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

            $scope.submitDetailItems = function() {
                console.log($scope.formData);
                var url = baseUrl+'/marketing/inquery/store_detail_item/'+$stateParams.id
                $scope.disBtn=true;
                var payload = {
                  'quotation_id': $scope.formData.quotation_id,
                  'detail_items': $scope.formData.detail_items
                }

                $http.post(url, payload).then(function(resp) {
                    $scope.disBtn = false;
                    $scope.show()
                    toastr.success(resp.data.message)
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