manifests.directive('manifestsShow', function () {
    return {
        restrict: 'E',
        scope: {
            'manifest_id' :'=manifestId',
            'hide_type' :'=hideType',
            'code_column_name' :'=codeColumnName',
            'addRoute' : '=addRoute',
            'index_route' : '=indexRoute',
            'indexRouteId' : '=indexRouteId',
            'source' :'=source'
        },
        transclude:true,
        require:'ngModel',
        templateUrl: '/core/operational/manifests/view/manifests-show.html',
        controller: function ($scope, $http, $attrs, $rootScope, $compile, $filter, $timeout, $state, manifestsService, additionalFieldsService, $stateParams) {
            $('.ibox-content').addClass('sk-loading');
            $scope.isNotAllow = false
            $scope.additional_fields = []
            $scope.additional_jo_fields = []
            $scope.additional = {}

            $scope.adjustVisibility = function(obj) {
                $scope.hide_is_full = false
                if(obj) {
                    if(obj.source == 'sales_order') {
                        $scope.hide_is_full = true
                    }
                }
            }

            additionalFieldsService.dom.get('manifest', function(list){
                $scope.additional_fields = list
            })

            additionalFieldsService.dom.getInManifestKey(function(list){
                $scope.additional_jo_fields = list
            })

            $scope.back = function() {
                if($scope.index_route) {
                  if($scope.indexRouteId){
                    $state.go($scope.index_route, {"id" : $scope.indexRouteId})
                  } else {
                    $state.go($scope.index_route)
                  }
                } else {
                    if($rootScope.hasBuffer()) {
                        $rootScope.accessBuffer()
                    } else {
                        $rootScope.emptyBuffer()
                        $state.go('operational.manifest_ftl')
                    }
                }
            }


            $scope.cancel_posting=function(id) {
                let cof = confirm("Apakah Anda Yakin ?")
                if (cof) {
                    $http.post(`${baseUrl}/operational/manifest_ftl/cancel_cost_journal/${id}`).then(function(e) {
                    toastr.success("Biaya batal di posting.");
                        $state.reload()
                    })
                }
            }

            $scope.editAdditional = function(name, slug) {
                $scope.modalAdditionalTitle= 'Edit ' + name
                $scope.additional.value = $scope.item.additional[slug]
                $scope.additional_slug = slug
                $('#additionalModal').modal('show')
            }

            $scope.submitAdditional = function() {
                var params = {}
                params[$scope.additional_slug] = $scope.additional.value
                $rootScope.disBtn = true
                var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
                $http.put(baseUrl+'/operational/manifest_ftl/' + id + '/additional', params).then(function(resp) {
                    $rootScope.disBtn=false;
                    toastr.success(resp.data.message)
                    $scope.show()
                    $('#additionalModal').modal('hide')
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
            

            $scope.show = function() {
                var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
                $http.get(baseUrl+'/operational/manifest_ftl/'+id).then(function(data) {
                    $scope.item=data.data.item;
                    $scope.detail=data.data.detail;
                    var unit

                    $scope.$emit('getDeliveryOrderId', data.data.item.delivery_order_driver_id)

                    $scope.adjustVisibility($scope.item)
                    for(x in $scope.detail) {
                        unit = $scope.detail[x]
                        if(parseInt(unit.transported) > parseInt(unit.stock)) {
                            $scope.isNotAllow = true
                        }
                    }

                    $scope.cost=data.data.cost;
                    $scope.data=data.data;

                    $scope.cost_type=data.data.cost_type;
                    $scope.vendor=data.data.vendor;

                    $scope.detail_approve=[]
                    angular.forEach($scope.cost, function(val,i) {
                      var percent=(val.total_price-val.quotation_costs)/val.quotation_costs*100;
                      if (val.quotation_costs>0) {
                        if (val.total_price <= val.quotation_costs) {
                          $scope.detail_approve.push({approve_with:1})
                        } else if (percent <= 5) {
                          $scope.detail_approve.push({approve_with:2})
                        } else {
                          $scope.detail_approve.push({approve_with:3})
                        }
                      } else {
                        if (val.total_price < 50000000) {
                          // kurang dari 50 juta
                          $scope.detail_approve.push({approve_with:1})
                        } else if (val.total_price < 100000000) {
                          // kurang dari 100 juta
                          $scope.detail_approve.push({approve_with:2})
                        } else {
                          // lebih dari 100 juta
                          $scope.detail_approve.push({approve_with:3})
                        }
                      }
                    })
                    $('.ibox-content').removeClass('sk-loading');
                });
            }
            $scope.show()

            $scope.$on('manifestDetailStored', function(e, v){
                $scope.show()
            })

            $scope.costData={};
            $scope.full_campuran=[
                {no:1,name:'Full'},
                {no:0,name:'Campuran'},
            ];

          $scope.status=[
            {id:1,name:'Belum Diajukan'},
            {id:2,name:'Diajukan Keuangan'},
            {id:3,name:'Disetujui Keuangan'},
            {id:4,name:'Ditolak'},
            {id:5,name:'Diposting'},
            {id:6,name:'Revisi'},
            {id:7,name:'Diajukan Atasan'},
            {id:8,name:'Disetujui'},
          ]
          $scope.type_cost=[
            {id:1,name:'Biaya Operasional'},
            {id:2,name:'Reimbursement'},
          ]

          $scope.editCost=function(id) {
            $scope.costData={};
            $scope.costData.is_edit=true;
            $scope.costData.id=id;
            $scope.titleCost = 'Edit Biaya'
            $http.get(baseUrl+'/operational/manifest_ftl/edit_cost/'+id).then(function(data) {
              var dt=data.data;
              // $scope.costData.cost_type=dt.cost_type_id;
              $scope.costData.cost_type=parseInt(dt.cost_type_id);
              $scope.costData.vendor_id=parseInt(dt.vendor_id);
              $scope.costData.qty=dt.qty;
              $scope.costData.price=dt.price;
              $scope.costData.is_internal=dt.is_internal;
              $scope.costData.total_price=dt.total_price;
              $scope.costData.description=dt.description;
              $scope.costData.type=dt.type;
              $('#modalCost').modal('show');
            });
          }


          $scope.searchVendorPrice = function() {
            if($scope.costData.vendor_id && $scope.costData.cost_type) {
                var data = {
                    vehicle_type_id : $scope.item.vehicle_type_id,
                    cost_type_id : $scope.costData.cost_type,
                    vendor_id : $scope.costData.vendor_id
                }
                $http.get(baseUrl+'/marketing/vendor_price/trucking/search?' + $.param(data)).then(function(data) {
                    $scope.costData.price=data.data;
                    $scope.calcCTTotalPrice()
                });
            }
          }


          $scope.show_cost = function() {
              var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
              $http.get(baseUrl+'/operational/manifest_ftl/' + id + '/cost').then(function(data) {
                $scope.cost_detail=data.data.cost_detail;
                $('.ibox-content').removeClass('sk-loading');
              });
          }
          $scope.show_cost()

          $scope.changeCT=function(id) {
            $http.get(baseUrl+'/setting/cost_type/'+id).then(function(data) {
              $scope.cost_type_f=data.data.item;

              $scope.costData.vendor_id=$scope.cost_type_f.vendor_id;
              $scope.costData.qty=$scope.cost_type_f.qty;
              $scope.searchVendorPrice()
            });
          }


          $scope.deleteCost=function(id) {
            var cofs=confirm("Apakah anda yakin ?");
            if (!cofs) {
              return null;
            }
            $http.delete(baseUrl+'/operational/manifest_ftl/delete_cost/'+id).then(function(data) {
              $state.reload();
              toastr.success("Biaya Packing List telah dihapus !");
            })
          }


          $scope.addCost=function() {
            $scope.costData={};
            $scope.costData.is_internal=0;
            $scope.costData.type=1
            $('#modalCost').modal('show');
          }

          $scope.printSJ=function() {
            var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
            window.open(baseUrl+'/operational/manifest_ftl/print_sj/'+id,'_blank');
            // $http({method: 'POST', url: baseUrl+'/operational/manifest_ftl/print_sj/'+$stateParams.id, responseType:'blob'})
            // .then(function successCallback(data, status, headers, config) {
            //   var blob = response.data;
            //   var contentType = response.headers("content-type");
            //   var fileURL = URL.createObjectURL(blob);
            //   window.location.href=fileURL;
            // },
            // function errorCallback(data, status, headers, config) {
            //   toastr.error(data.statusText,"Error!");
            // });
          }

          $scope.setVehicle = function(){
            if($stateParams.id_shipment){
              var id = $stateParams.id;
              var id_shipment = $stateParams.id_shipment;
              $state.go('sales_order.sales_order.show.show_shipment.set_vehicle', {id: id, id_shipment: id_shipment})
            } else {
              var id = $stateParams.id;
              $state.go('operational.manifest_ftl.create_delivery', {id: id})
            }
          }

          $scope.setStripStuff=function() {
            $scope.timeData={}
            var dt=$scope.item;
            if (dt.depart) {
              $scope.timeData.depart_time=$filter('aTime')(dt.depart);
              $scope.timeData.depart_date=$filter('minDate')(dt.depart);
            }
            if (dt.arrive) {
              $scope.timeData.arrive_time=$filter('aTime')(dt.arrive);
              $scope.timeData.arrive_date=$filter('minDate')(dt.arrive);
            }
            $scope.timeData.container_no=dt.container_no
            $('#modalSet').modal('show');
          }

        $scope.editDetail=function(jsn) {
            $scope.leftover=0;
            $scope.transported=0;
            angular.forEach($scope.detail, function(val,i) {
                $scope.leftover+=val.leftover
            })
            $scope.leftover+=jsn.transported;
            $scope.editData={}
            $scope.editData.id=jsn.id;
            $scope.editData.transported=jsn.transported;
            $scope.editData.requested_qty=jsn.requested_qty;
            $scope.editData.discharged_qty=jsn.discharged_qty;
            $scope.editData.transported_origin=jsn.transported;
            $('#editDetail').modal('show');
        }

          $scope.showCancelDelivery = function() {
            $scope.cancelData = {};
            $('#cancelModal').modal('show');
          }

          $scope.submitCancelDelivery = function() {
            $scope.disBtn=true;
            $scope.cancelData.id = $scope.item.id;
            var url = baseUrl+'/operational/manifest_ftl/cancel_delivery/'+$scope.cancelData.id;

            $http.post(url, $scope.cancelData).then(function(data) {
              $('#cancelModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Pembatalan assignment kendaraan berhasil!");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
              if (error.status==422) {
                var det="";
                angular.forEach(error.data.errors,function(val,i) {
                  det+="- "+val+"<br>";
                });
                toastr.warning(det,error.data.message);
              } else {
                toastr.error(error.data.message,"Error pada pembatalan assignment kendaraan !");
              }
            });
          }

          $scope.editPrice=function(jsn) {
            $scope.editData={}
            $scope.editData.id=jsn.id
            $scope.editData.vendor_id=jsn.vendor_id
            $scope.editData.total_price=jsn.total_price
            $('#editPriceModal').modal();
          }

          $scope.deletePrice=function(ids) {
            var cfs=confirm("Apakah Anda Yakin?");
            if (cfs) {
              $http.delete(baseUrl+'/operational/manifest_fcl/delete_price/'+ids,{_token:csrfToken}).then(function success(data) {
                $state.reload();
                toastr.success("Data Berhasil Dihapus!");
              }, function error(data) {
                toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
              });
            }
          }

          $scope.submitPrice=function() {
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational/manifest_fcl/submit_price/'+$scope.editData.id,$scope.editData).then(function(data) {
              // $state.go('operational.job_order');
              $('#editPriceModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Biaya telah direvisi !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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

          $scope.submitEdit=function() {
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational/manifest_fcl/store_edit/'+$scope.editData.id,$scope.editData).then(function(data) {
              // $state.go('operational.job_order');
              $('#editDetail').modal('hide');
              $scope.show()
              toastr.success(data.data.message);
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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

          $scope.revision=function(jsn) {
            // console.log(jsn);
            $scope.revisiData={}
            $scope.revisiData.cost_id=jsn.id
            $scope.revisiData.cost_type_f=jsn.cost_type
            $scope.revisiData.qty=jsn.qty
            $scope.revisiData.price=jsn.price
            $scope.revisiData.total_price=jsn.total_price
            $scope.revisiData.before_revision_cost=jsn.total_price
            $scope.revisiData.description=jsn.description
            $scope.revisiData.vendor_id=jsn.vendor_id
            $('#revisiModal').modal('show');
          }

          $scope.submitRevisi=function() {
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational/manifest_fcl/store_revision/'+$scope.revisiData.cost_id,$scope.revisiData).then(function(data) {
              // $state.go('operational.job_order');
              $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Biaya telah direvisi !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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

            $scope.submitTime=function() {
                $http.post(baseUrl+'/operational/manifest_ftl/change_depart_arrive/'+$scope.item.id,$scope.timeData).then(function(data) {
                    toastr.success("Waktu Berangkat / Sampai Berhasil diganti!");
                    $('#modalSet').modal('hide');
                    $scope.show()
                });
            }

            $scope.saveSubmission=function(id) {
                var conf=confirm("Apakah anda ingin menyimpan di pengajuan biaya ?");
                if (conf) {
                  $http.post(baseUrl+'/operational/manifest_ftl/store_submission/'+id).then(function(data) {
                    $state.reload()
                    toastr.success("Pengajuan Biaya berhasil disimpan!");
                  });
                }
            }

          $scope.itemData={};
          $scope.addItem=function() {
            $scope.itemData={}
            $scope.itemData.customer_id=null
            $scope.customerList=[]

            $http.get(`${baseUrl}/operational/manifest_ftl/list_customer_manifest`).then(d => {
              $scope.customerList=d.data.data
            })
            $scope.listJobOrderGet()
            $('#modalItem').modal('show');
          }

          $scope.listJobOrderGet=function(customer_id=null){
            $scope.itemData.detail=[];
            var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
            $http.get(baseUrl+'/operational/manifest_ftl/list_job_order/'+id,{params:{customer_id:customer_id}}).then(function(data) {
              var html="";
              console.log(data.data, 'listJO')
              angular.forEach(data.data,function(val,i) {
                html+="<tr>";
                html+="<td>"+val.code+"</td>";
                html+="<td>"+val.customer+"</td>";
                html+="<td>"+val.item_name+"</td>";
                html+="<td>"+val.qty+"</td>";
                html+="<td>"+val.transported+"</td>";
                html+="<td>"+parseFloat(val.qty-val.transported)+"</td>";
                html+='<td><input class="form-control" jnumber2 only-num ng-model="itemData.detail['+i+'].pickup"></td>';
                html+="</tr>";

                $scope.itemData.detail.push({
                  id: val.id,
                  qty: val.qty,
                  qty_selisih: val.qty_selisih,
                  transported: val.transported,
                  sisa: parseFloat(val.qty-val.transported),
                  pickup: 0
                });
              });
              $('#itemTable tbody').html($compile(html)($scope))
            });
          }

          $scope.changeCustomerList=function(customer_id) {
            $scope.listJobOrderGet(customer_id)
          }

          $scope.checkManifestSimpan=function() {
            var tots=0;
            var stats=true;
            angular.forEach($scope.itemData.detail,function(v,i) {
              tots+=parseFloat(v.pickup);
              if (parseFloat(v.pickup) > v.sisa) {
                stats = true;
                return;
              }
            })

            if (tots<1 || stats) {
              return true;
            } else {
              return false;
            }
          }

          $scope.deleteDetail=function(id) {
            var conf=confirm("Apakah anda yakin ?");
            if (conf) {
              $http.delete(baseUrl+'/operational/manifest_ftl/delete_detail/'+id).then(function(data) {
                toastr.success("Detail Berhasil Dihapus!");
                $scope.show()
              });
            }
          }

          $scope.disBtn=false;
          $scope.submitCost=function() {
            $scope.disBtn=true;
            var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
            $http.post(baseUrl+'/operational/manifest_ftl/add_cost/'+id,$scope.costData).then(function(data) {
              // $state.go('operational.job_order');
              $('#modalCost').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Biaya Packing List berhasil ditambahkan!");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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


          $scope.ajukanAtasan=function(id) {
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational/manifest_ftl/ajukan_atasan',{id:id}).then(function(data) {
              // $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Biaya Telah Diajukan !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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
          $scope.approveAtasan=function(id) {
            var cofs=confirm("Apakah anda yakin ?");
            if (!cofs) {
              return null;
            }
            $scope.disBtn=true;
            $http.post(baseUrl+'/operational/manifest_ftl/approve_atasan',{id:id}).then(function(data) {
              // $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Biaya Telah Disetujui !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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
              $scope.disBtn=true;
              $http.post(baseUrl+'/operational/manifest_ftl/reject_atasan',{id:id}).then(function(data) {
                // $('#revisiModal').modal('hide');
                $timeout(function() {
                  $state.reload();
                },1000)
                toastr.success("Biaya Telah Ditolak !");
                $scope.disBtn=false;
              }, function(error) {
                $scope.disBtn=false;
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
            $scope.disBtn=true;
            var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
            $http.post(baseUrl+'/operational/manifest_ftl/cost_journal',{id:id}).then(function(data) {
              // $('#revisiModal').modal('hide');
              $timeout(function() {
                $state.reload();
              },1000)
              toastr.success("Biaya telah dijurnal !");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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

            $scope.calcCTTotalPrice=function(){
                $scope.costData.total_price=$scope.costData.qty*$scope.costData.price
            }

            $scope.storeItemFromPicking = function(v) {
                $scope.itemData = {}
                $scope.itemData.detail = []
                var params = {}
                params.picking_detail_id = v.picking_detail_id
                params.pickup = v.qty
                $scope.itemData.detail.push(params)
                $scope.submitItem()
            }

            $scope.$on('getItemWarehouse', function(e, v){
                $scope.storeItemFromPicking(v)    
            })

          $scope.submitItem=function() {
            $scope.disBtn=true;
            var id = $stateParams.id_shipment ? $stateParams.id_shipment : $stateParams.id;
            $http.post(baseUrl+'/operational/manifest_ftl/add_item/'+id,$scope.itemData).then(function(data) {
              $scope.show()
              $('#modalItem').modal('hide');
              toastr.success("Item berhasil ditambahkan!");
              $scope.disBtn=false;
            }, function(error) {
              $scope.disBtn=false;
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