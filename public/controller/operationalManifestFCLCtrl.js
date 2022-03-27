app.controller('operationalManifestFCL', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Packing List FCL / LCL";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {};

    additionalFieldsService.dom.getInIndexKey('manifest', function(list){
        var additional_fields = list
        columnDefs = [
            { 'title' : $rootScope.solog.label.manifest.code },
            { 'title' : $rootScope.solog.label.manifest.date },
            { 'title' : 'No. Surat Jalan' },
            { 'title' : 'Branch' },
            { 'title' : 'Route' },
            { 'title' : $rootScope.solog.label.general.type },
            { 'title' : $rootScope.solog.label.general.container },
            { 'title' : 'Voyage' },
            { 'title' : 'Driver' },
            { 'title' : 'Nopol' },
            { 'title' : 'Status' }
        ]

        columns = [
            {data:"code",name:"code",className:"font-bold"},
            {
                data:null,
                searchable:false,
                name:"date_manifest",
                render:resp => $filter('fullDate')(resp.date_manifest)
            },
            {data:"code_sj",name:"dod.code"},
            {data:"company",name:"companies.name"},
            {data:"trayek",name:"routes.name"},
            {data:"tipe_angkut",name:"tipe_angkut",className:"text-center"},
            {data:"container_no",name:"containers.container_no"},
            {data:"voyage",name:"voyage_schedules.voyage"},
            {data:"sopir",name:"dod.driver_name"},
            {data:"kendaraan",name:"dod.nopol"},
            {data:"job_status",name:"id"},
        ]

        for(x in additional_fields) {
            columns.push({
                data : additional_fields[x].slug,
                name : 'additional_manifests.' + additional_fields[x].slug
            })
            columnDefs.push({
                title : additional_fields[x].name
            })
        }
        
        columnDefs.push({title:""})
        columns.push({data:"action",name:"id",className:"text-center"})

        columnDefs = columnDefs.map((c, i) => {
            c.targets = i
            return c
        })

        oTable = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            order:[[11,'desc']],
            dom: 'Blfrtip',
            buttons: [{
                extend: 'excel',
                enabled: true,
                action: newExportAction,
                text: '<span class="fa fa-file-excel-o"></span> Export Excel',
                className: 'btn btn-default btn-sm pull-right',
                filename: 'Packing List FCL/LCL',
                sheetName: 'Data',
                title: 'Packing List FCL/LCL',
                exportOptions: {
                    rows: {
                        selected: true
                    }
                },
            }],
            lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
            ajax: {
                headers : {'Authorization' : 'Bearer '+authUser.api_token},
                url : baseUrl+'/api/operational/manifest_fcl_datatable',
                data : function(request) {
                    request['start_date'] = $scope.formData.start_date;
                    request['end_date'] = $scope.formData.end_date;
                    request['company_id'] = $scope.formData.company_id;
                    request['status'] = $scope.formData.status;

                    return request;
                },
                dataSrc: function(d) {
                    $('.ibox-content').removeClass('sk-loading');
                    return d.data;
                }
            },
            columns : columns,
            columnDefs : columnDefs,
            createdRow: function(row, data, dataIndex) {
                if($rootScope.roleList.includes('operational.manifest.container.detail')) {
                    $(row).find('td').attr('ui-sref', 'operational.manifest_fcl.show({id:' + data.id + '})')
                    $(row).find('td:last-child').removeAttr('ui-sref')
                } else {
                    $(oTable.table().node()).removeClass('table-hover')
                }
                $compile(angular.element(row).contents())($scope);
            }
        });
        oTable.buttons().container().appendTo('.ibox-tools')
    })


  $scope.searchData = function() {
    oTable.ajax.reload();
  }
  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }


  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/operational/manifest_fcl/'+ids,{_token:csrfToken}).then(function success(data) {
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});
app.controller('operationalManifestFCLCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Packing List";
  $('.ibox-content').addClass('sk-loading');
  $scope.isLoading = true;
  $scope.isLoadingTable = true;
  $scope.cancelData = {};
  $scope.formData={}
  $scope.formData.company_id=compId;
  $scope.formData.date_manifest=dateNow;
  $scope.formData.reff_no='-';
  $scope.formData.description='-';
  $scope.formData.is_full=1;

  $scope.cariContainer=function() {
    $('#containerModal').modal('show');
    containerTable.ajax.reload();
  }

  $scope.chooseContainer=function(id,name) {
    $scope.formData.container_id=id;
    $scope.formData.container_no=name;
    $('#containerModal').modal('hide');
  }

  var containerTable = $('#container_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX :false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/container_datatable',
      data: function(d) {
        d.is_fcl=$scope.formData.is_full;
      },
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center",sorting:false},
      {data:"container_no",name:"container_no",className:"font-bold"},
      {data:"booking_number",name:"booking_number"},
      {data:"container_type.full_name",name:"container_type.name"},
      {data:"voyage_schedule.vessel.name",name:"voyage_schedule.vessel.name"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });


  $http.get(baseUrl+'/operational/manifest_fcl/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.changeDriver=function(id) {
    $('.ibox-content').addClass('sk-loading');
    $scope.vehicles=[];
    $http.get(baseUrl+'/operational/manifest_ftl/cari_kendaraan/'+id).then(function(data) {
      angular.forEach(data.data,function(val,i) {
        $scope.vehicles.push(
          {id:val.vehicle_id,name:val.vehicle.nopol}
        )
      });
      $('.ibox-content').removeClass('sk-loading');
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_fcl',$scope.formData).then(function(data) {
      $state.go('operational.manifest_fcl');
      // $('#modalCost').modal('hide');
      // $timeout(function() {
      //   $state.go('operational.manifest_ftl.show',{id:$stateParams.id});
      // },1000)
      toastr.success("Data berhasil disimpan!");
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
});
app.controller('operationalManifestFCLEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Packing List";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={}

  $scope.cariContainer=function() {
    $('#containerModal').modal('show');
  }

  $scope.chooseContainer=function(id,name) {
    $scope.formData.container_id=id;
    $scope.formData.container_no=name;
    $('#containerModal').modal('hide');
  }

  var containerTable = $('#container_datatable').DataTable({
    processing: true,
    serverSide: true,
    scrollX :false,
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/operational/container_datatable',
      data: function(d) {
        d.is_fcl=$scope.formData.is_full;
      }
    },
    columns:[
      {data:"action_choose",name:"action_choose",className:"text-center",sorting:false},
      {data:"container_no",name:"container_no",className:"font-bold"},
      {data:"booking_number",name:"booking_number"},
      {data:"container_type.full_name",name:"container_type.name"},
      {data:"voyage_schedule.vessel.name",name:"voyage_schedule.vessel.name"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $http.get(baseUrl+'/operational/manifest_fcl/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.company_id=dt.company_id;
    $scope.formData.date_manifest=$filter('minDate')(dt.date_manifest);
    $scope.formData.reff_no=dt.reff_no;
    $scope.formData.description=dt.description;
    $scope.formData.is_full=dt.is_full;
    $scope.formData.container_no=dt.container == null ? '' : dt.container.container_no;
    $scope.formData.container_id=dt.container_id;
    $scope.formData.route_id=dt.route_id;
  }).then(e => {
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/operational/manifest_fcl/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('operational.manifest_fcl.show',{id:$stateParams.id});
      // $('#modalCost').modal('hide');
      // $timeout(function() {
      //   $state.go('operational.manifest_ftl.show',{id:$stateParams.id});
      // },1000)
      toastr.success("Data berhasil disimpan!");
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

});
app.controller('operationalManifestFCLChangeVessel', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Set Kapal Packing List";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData={}

  $scope.back=function() {
    $state.go('operational.manifest_fcl.show',{id:$stateParams.id},{location:'replace'})
  }

  $http.get(baseUrl+'/operational/manifest_fcl/change_vessel/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.formData.code=dt.code;
    if(dt.container !== null) {
        $scope.formData.container_no=dt.container.container_no;
        $scope.formData.seal_no=dt.container.seal_no;
        $scope.formData.voyage_id=dt.container.voyage_schedule_id;
        $scope.changeVoyage(dt.container.voyage_schedule_id);
    }
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.changeVoyage=function(id) {
    $scope.formData.vessel_id=$rootScope.findJsonId(id,$scope.data.voyage).vessel_id;
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_fcl/store_vessel/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('operational.manifest_fcl.show',{id:$stateParams.id});
      toastr.success("Data berhasil disimpan!");
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

});
app.controller('operationalManifestFCLShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter, additionalFieldsService) {
    $rootScope.pageTitle="Packing List FCL / LCL";
    $scope.isNotAllow = false
    $scope.additional_fields = []
    $scope.additional = {}

    additionalFieldsService.dom.getInManifestKey(function(list){
        $scope.additional_jo_fields = list
    })


  $scope.show = function() {
      $http.get(baseUrl+'/operational/manifest_ftl/'+$stateParams.id).then(function(data) {
        $scope.data=data.data;
        $scope.item=data.data.item;
        $scope.detail=data.data.detail;
        var unit
        for(x in $scope.detail) {
          unit = $scope.detail[x]
          if(parseInt(unit.transported) > parseInt(unit.stock)) {
              $scope.isNotAllow = true
          }
        }

        $scope.cost=data.data.cost;
        $scope.cost_type=data.data.cost_type;
        $scope.vendor=data.data.vendor;
        $scope.vehicles=data.data.vehicles;
        $scope.drivers=data.data.drivers;

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

      }, function(){
          $scope.show()
      });
  }
  $scope.show()

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

    additionalFieldsService.dom.get('manifest', function(list){
        $scope.additional_fields = list
    })

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
        $http.put(baseUrl+'/operational/manifest_ftl/' + $stateParams.id + '/additional', params).then(function(resp) {
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

  $scope.searchVendorPrice = function() {
    if($scope.costData.vendor_id && $scope.costData.cost_type) {
        var data = {
            container_type_id : $scope.item.container_type_id,
            cost_type_id : $scope.costData.cost_type,
            vendor_id : $scope.costData.vendor_id
        }
        $http.get(baseUrl+'/marketing/vendor_price/container/search?' + $.param(data)).then(function(data) {
            $scope.costData.price=data.data;
            $scope.calcCTTotalPrice()
        });
    }
  }

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

  $scope.cancel_posting=function(id) {
    let cof = confirm("Apakah Anda Yakin ?")
    if (cof) {
      $http.post(`${baseUrl}/operational/manifest_ftl/cancel_cost_journal/${id}`).then(function(e) {
        toastr.success("Biaya batal di posting.");
        $scope.show()
      })
    }
  }

  $scope.show_cost = function() {
      $http.get(baseUrl+'/operational/manifest_ftl/' + $stateParams.id + '/cost').then(function(data) {
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


  $scope.setVehicle=function() {
    $scope.vehicleData={}
    if ($scope.data.delivery_order.length<1) {
      // tidak ada do
      $scope.vehicleData.is_internal=1
    } else {
      // ada do
      $scope.vehicleData.is_edit=1
      angular.forEach($scope.data.delivery_order, function(val,i) {
        $scope.vehicleData.is_internal=$scope.item.is_internal_driver
        $scope.vehicleData.do_id=val.id
        $scope.vehicleData.manifest_id=$scope.item.id
        $scope.vehicleData.delivery_order_number=val.code_sj
        $scope.vehicleData.vendor_id=val.vendor_id
        if ($scope.item.is_internal_driver) {
          $scope.vehicleData.vehicle_internal_id=val.vehicle_id
          $scope.vehicleData.driver_internal_id=val.driver_id
        } else {
          $scope.vehicleData.vendor_id=val.vendor_id
          $scope.vehicleData.vehicle_eksternal_id=val.vehicle_id
          $scope.vehicleData.driver_eksternal_id=val.driver_id
        }
      })
    }
    $('#modalVehicle').modal()
  }

  $scope.setVehicleInternalChange=function(data) {
    $scope.vehicleData={}
    $scope.vehicleData.is_internal=data.is_internal
    $scope.vehicleData.is_edit=data.is_edit
    $scope.vehicleData.do_id=data.do_id
    $scope.vehicleData.code_sj=data.code_sj
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


  $scope.submitEditVehicle=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_ftl/update_delivery/'+$scope.vehicleData.manifest_id,$scope.vehicleData).then(function(data) {
      // $state.go('operational.job_order');
      $('#modalVehicle').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Kendaraan & Driver Berhasil di Setting !");
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

  $scope.submitVehicle=function() {
    if ($scope.vehicleData.is_edit) {
      return $scope.submitEditVehicle()
    }
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_ftl/store_delivery/'+$stateParams.id,$scope.vehicleData).then(function(data) {
      // $state.go('operational.job_order');
      $('#modalVehicle').modal('hide');
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Kendaraan & Driver Berhasil di Setting !");
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


  $scope.deleteDetail=function(id) {
    var conf=confirm("Apakah anda yakin ?");
    if (conf) {
      $http.delete(baseUrl+'/operational/manifest_ftl/delete_detail/'+id).then(function(data) {
        toastr.success("Detail Berhasil Dihapus!");
        $state.reload();
      });
    }
  }

  $scope.printSJ=function() {
    window.open(baseUrl+'/operational/manifest_ftl/print_sj/'+$stateParams.id,'_blank');
  }

  $scope.editDetail=function(jsn) {
    // console.log(jsn)

    $scope.leftover=0;
    $scope.transported=0;
    angular.forEach($scope.detail, function(val,i) {
      $scope.leftover+=val.leftover
    })
    $scope.leftover+=jsn.transported;
    $scope.editData={}
    $scope.editData.id=jsn.id;
    $scope.editData.transported=jsn.transported;
    $scope.editData.transported_origin=jsn.transported;
    $('#editDetail').modal('show');
  }

  $scope.submitEdit=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_fcl/store_edit/'+$scope.editData.id,$scope.editData).then(function(data) {
      // $state.go('operational.job_order');
      $('#editDetail').modal('hide');
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

  $scope.setVessel=function() {
    $state.go('operational.manifest_fcl.change_vessel',{id:$stateParams.id})
  }

  $scope.setStripStuff=function() {
    $scope.timeData={}
    var dt=$scope.item.container;
    if (dt.stripping) {
      $scope.timeData.stripping_time=$filter('aTime')(dt.stripping);
      $scope.timeData.stripping_date=$filter('minDate')(dt.stripping);
    }
    if (dt.stuffing) {
      $scope.timeData.stuffing_time=$filter('aTime')(dt.stuffing);
      $scope.timeData.stuffing_date=$filter('minDate')(dt.stuffing);
    }
    $('#modalSet').modal('show');
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


  $scope.back=function() {
    $state.go('operational.manifest_fcl.show',{id:$stateParams.id})
  }

  $scope.full_campuran=[
    {id:1,name:'<span class="badge badge-success">FULL</span>'},
    {id:0,name:'<span class="badge badge-warning">CAMPURAN</span>'},
  ];
  $scope.costData={};

  $scope.addCost=function() {
    $scope.costData={};
    $scope.costData.is_internal=0;
    $('#modalCost').modal('show');
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
  $scope.submitTime=function() {
    $scope.timeData.manifest_id = $stateParams.id
    $http.post(baseUrl+'/operational/manifest_fcl/change_stuff_strip/'+$scope.item.container.id,$scope.timeData).then(function(data) {
      toastr.success("Waktu Muat / Bongkar Kapal Berhasil diganti!");
      $('#modalSet').modal('hide');
      $timeout(function() {
        $state.reload()
      },1000)
    });
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
    $http.get(baseUrl+'/operational/manifest_ftl/list_job_order/'+$stateParams.id,{params:{customer_id:customer_id}}).then(function(data) {
      var html="";
      for (var i = 0; i < data.data.length; i++) {
        const val = data.data[i]
        // if(parseFloat(val.qty-val.transported) <= 0) continue;
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
          transported: val.transported,
          sisa: parseFloat(val.qty-val.transported),
          pickup: 0
        });
      }
      $('#itemTable tbody').html($compile(html)($scope))
    });
  }

  $scope.changeCustomerList=function(customer_id) {
    $scope.listJobOrderGet(customer_id)
  }

  $scope.disBtn=false;
  $scope.submitCost=function() {
    $scope.disBtn=true;
    $scope.costData.type = 1
    $http.post(baseUrl+'/operational/manifest_ftl/add_cost/'+$stateParams.id,$scope.costData).then(function(data) {
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


  $scope.cost_journal=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_ftl/cost_journal',{id:$stateParams.id}).then(function(data) {
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




  $scope.submitItem=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_ftl/add_item/'+$stateParams.id,$scope.itemData).then(function(data) {
      // $state.go('operational.job_order');
      $('#modalItem').modal('hide');
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

});
app.controller('operationalManifestFCLPickup', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Packing List FCL | Set Kendaraan";
  $scope.formData={};

  $(".clockpick").clockpicker({
    placement:'right',
    autoclose:true,
    donetext:'DONE',
  });

  $http.get(baseUrl+'/operational/manifest_fcl/create_delivery/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $scope.data=data.data;
    $scope.formData.code_manifest=$scope.item.code;
    $scope.formData.pick_date=dateNow;
    $scope.formData.pick_time=timeNow;
    $scope.formData.finish_date=dateNow;
    $scope.formData.finish_time=timeNow;
  });

  $scope.changeCustomer1=function(id) {
    $scope.contact_address1=[];
    $http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
      angular.forEach(data.data.address,function(val,i) {
        $scope.contact_address1.push(
          {id:val.id,name:val.name+', '+val.address}
        )
      });
    });
  }
  $scope.changeCustomer2=function(id) {
    $scope.contact_address2=[];
    $http.get(baseUrl+'/operational/job_order/cari_address/'+id).then(function(data) {
      angular.forEach(data.data.address,function(val,i) {
        $scope.contact_address2.push(
          {id:val.id,name:val.name+', '+val.address}
        )
      });
    });
  }
  $scope.changeDriver=function(id) {
    $scope.vehicles=[];
    $http.get(baseUrl+'/operational/manifest_fcl/cari_kendaraan/'+id).then(function(data) {
      angular.forEach(data.data,function(val,i) {
        $scope.vehicles.push(
          {id:val.vehicle_id,name:val.vehicle.nopol}
        )
      });
    });
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/operational/manifest_fcl/store_delivery/'+$stateParams.id,$scope.formData).then(function(data) {
      // $state.go('operational.job_order');
      // $('#modalCost').modal('hide');
      $timeout(function() {
        $state.go('operational.manifest_fcl.show',{id:$stateParams.id});
      },1000)
      toastr.success("Data berhasil disimpan!");
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

});
