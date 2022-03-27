app.controller('settingGeneralIndex', function(solog, $scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting";
    $scope.states=$state;
});

app.controller('settingGeneralGeneral', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting";
    $scope.states=$state;
    $scope.data = []

    $scope.showGeneral = function() {
        $http.get(baseUrl+'/setting/setting/general').then(function(data){
            $scope.data.push(data.data[0])
        }, function() {
            $timeout(function(){
                $scope.showGeneral()
            }, 10000)
        })
    }

    $scope.showGoodReceipt = function() {
        $http.get(baseUrl+'/setting/setting/good_receipt').then(function(data){
            $scope.data.push(data.data[0])
        }, function() {
            $timeout(function(){
                $scope.showGoodReceipt()
            }, 10000)
        })
    }

    $scope.showPicking = function() {
        $http.get(baseUrl+'/setting/setting/picking').then(function(data){
            $scope.data.push(data.data[0])
        }, function() {
            $timeout(function(){
                $scope.showPicking()
            }, 10000)
        })
    }


    $scope.showShipment = function() {
        $http.get(baseUrl+'/setting/setting/shipment').then(function(data){
            $scope.data.push(data.data[0])
        }, function() {
            $timeout(function(){
                $scope.showShipment()
            }, 10000)
        })
    }

    $scope.showJobOrder = function() {
        $http.get(baseUrl+'/setting/setting/job_order').then(function(data){
            $scope.data.push(data.data[0])
        }, function() {
            $timeout(function(){
                $scope.showJobOrder()
            }, 10000)
        })
    }

    $scope.showSalesOrder = function() {
        $http.get(baseUrl+'/setting/setting/sales_order').then(function(data){
            $scope.data.push(data.data[0])
        }, function() {
            $timeout(function(){
                $scope.showSalesOrder()
            }, 10000)
        })
    }

    $scope.show = function() {
        $http.get(baseUrl+'/setting/setting/work_order').then(function(data){
            $scope.data.push(data.data[0])
            $scope.showJobOrder()
            $scope.showSalesOrder()
            $scope.showGoodReceipt()
            $scope.showPicking()
            $scope.showShipment()
        }, function() {
            $timeout(function(){
                $scope.show()
            }, 10000)
        })
    }
    $scope.showGeneral()
    $timeout(function() {
        $scope.show()
    }, 600)
    $scope.showService = function() {
        $http.get(baseUrl+'/setting/general/service').then(function(data) {
            $scope.services=data.data
        });
    }
    $scope.showService()

    $scope.showPiece = function() {
        $http.get(baseUrl+'/setting/general/satuan').then(function(data) {
            $scope.pieces=data.data;
        });
    }
    $scope.showPiece()

    $scope.submitForm = function() {
        $http.put(baseUrl+'/setting/setting',$scope.data).then(function(data) {
            $rootScope.storeSettings()
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

app.controller('settingGeneralPrintRemark', function(hardList, $scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="General Setting | Remark Cetakan";
  $scope.states=$state;
  $scope.formData={
      additional : {}
  }
  
  $scope.show = function() {
      $http.get(baseUrl+'/setting/general/print_remark').then(function(data){
        $scope.formData=data.data
        $scope.formData.additional = JSON.parse($scope.formData.additional)
        var attachment_container = $('#attachment_container')
          img = $("<div class='col-md-12 img-container'><img style='height:auto;width:100%' onclick='window.open(\"" + $scope.formData.logo + "\")' class='img-thumbnail' src='" + $scope.formData.logo + "'></div>")
          attachment_container.prepend(img)
          $compile(attachment_container)($scope);
      }, function() {
        $scope.show()
      })
  }
  $scope.show()

  $('#files').change(function(){
      var input = this
      $scope.store_attachment()
      if (input.files.length > 0) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
        }
        
      }
  })
  $scope.store_attachment=function() {
    var fd = new FormData();
    var files = $('#files')[0].files[0];
    fd.append('logo', files);
    store_attachment_btn = $('[for="files"]')
    store_attachment_btn.addClass('disabled')
    $.ajax({
        url:baseUrl+'/setting/general/print_remark/logo?_token='+csrfToken,
        contentType : false,
        processData : false,
        type : 'POST',
        data : fd,
        beforeSend : function(request) {
          request.setRequestHeader('Authorization', 'Bearer ' + authUser.api_token);
        },
        success:function(data) {
          store_attachment_btn.removeClass('disabled')
          toastr.success("Logo berhasil di-upload!");
          $('.img-container').remove()
          var unit
          var attachment_container = $('#attachment_container')
          unit = data.attachments
          img = $("<div class='col-md-12 img-container'><img style='height:auto;width:100%' onclick='window.open(\"" + unit.url + "\")' class='img-thumbnail' src='" + unit.url + "'></div>")
          attachment_container.prepend(img)
          $compile(attachment_container)($scope);
        },
        error : function(xhr) {
          store_attachment_btn.removeClass('disabled')
          var resp = JSON.parse(xhr.responseText);
           $('.submitButton').removeAttr('disabled');
           toastr.error(resp.message,"Error Has Found !");
        }
    });
  }

  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/setting/general/store_remark',$scope.formData).then(function(data) {
      $scope.disBtn=false;
      $timeout(function() {
        $state.reload();
      },1000)
      toastr.success("Data Berhasil Disimpan !");
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

app.controller('settingGeneralVessel', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Vessel";
    $scope.formData={
        _token:csrfToken
    }

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/vessel/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
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
    
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        order: [],
        buttons: [{
          extend: 'excel',
          enabled: true,
          action: newExportAction,
          text: '<span class="fa fa-file-excel-o"></span> Export Excel',
          className: 'btn btn-default btn-sm pull-right',
          filename: 'Kapal',
          messageTop: 'Kapal',
          sheetName: 'Data',
          title: 'Kapal',
          exportOptions: {
            rows: {
              selected: true
            }
          },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/vessel_datatable'
        },
        columns:[
        {data:"vendor.name",name:"vendor.name"},
        {data:"code",name:"code"},
        {data:"name",name:"name"},
        {data:"action",name:"action",className:"text-center"},
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

    oTable.buttons().container().appendTo('.ibox-tools')

    $http.get(baseUrl+'/setting/general/vessel').then(function(data) {
        $scope.vendor=data.data.vendor;
    });


    $scope.add=function() {
        $scope.modalTitle="Add Kapal";
        $scope.formData.code='';
        $scope.formData.name='';
        $scope.url=baseUrl+'/setting/general/store_vessel';
        $('#modal').modal('show');
    }
    $scope.edit=function(ids) {
        $scope.modalTitle="Edit Kapal";
        $http.get(baseUrl+'/setting/general/vessel/'+ids).then(function(data) {
            $scope.formData.code=data.data.item.code;
            $scope.formData.name=data.data.item.name;
            $scope.formData.vendor_id=data.data.item.vendor_id;
            $scope.url=baseUrl+'/setting/general/store_vessel/'+ids;
            $('#modal').modal('show');
        });
    }

    $scope.submitForm=function() {
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formData,
            success: function(data){
                $('#modal').modal('hide');
                oTable.ajax.reload();
                toastr.success("Data Berhasil Disimpan!");
            },
            error: function(xhr, response, status) {
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
});

app.controller('settingGeneralCountries', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Country";
    $scope.formData={
        _token:csrfToken
    }

    $scope.delete=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/countries/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function(xhr){
                console.log(status)
                if (xhr.status==422) {
                  var msgs="";
                  $.each(xhr.data.errors, function(i, val) {
                    msgs+=val+'<br>';
                  });
                  toastr.warning(msgs,"Validation Error!");
                } else {
                  toastr.error(xhr.data.message,"Error has Found!");
                }
            });
        }
    }
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
          extend: 'excel',
          enabled: true,
          action: newExportAction,
          text: '<span class="fa fa-file-excel-o"></span> Export Excel',
          className: 'btn btn-default btn-sm pull-right',
          filename: 'Negara',
          messageTop: 'Negara',
          sheetName: 'Data',
          title: 'Negara',
          exportOptions: {
            rows: {
              selected: true
            }
          },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/countries_datatable'
        },
        columns:[
        {data:"name",name:"name"},
        {
            data:"action",name:"action",className:"text-center"},
            ], 
            createdRow: function(row, data, dataIndex) {
                $(row).find('td').attr('ng-click', 'edit($event.currentTarget)')
                $(row).find('td:last-child').removeAttr('ng-click')
                $compile(angular.element(row).contents())($scope);
            }
        });
    oTable.buttons().container().appendTo( '.ibox-tools')

    $scope.add=function() {
        $scope.modalTitle="Add Country";
        $scope.formData.code='';
        $scope.formData.name='';
        $scope.url=baseUrl+'/setting/general/store_countries';
        $('#modal_negara').modal('show');
    }

    
    if($rootScope.hasBuffer()) {
        $scope.add()
    }

    $('#modal_negara').on('hidden.bs.modal', function(){
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        }
    })

    $scope.edit=function(obj) {
        $scope.modalTitle="Edit Country";
        var tr = $(obj).parents('tr');
        var unit = oTable.row(tr).data();
        $scope.formData.name = unit.name;
        $scope.url=baseUrl+'/setting/general/store_countries/' + unit.id;
        $('#modal_negara').modal('show');
    }

    $scope.submitForm=function() {
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formData,
            success: function(data){
                $('#modal_negara').modal('hide');
                oTable.ajax.reload();
                toastr.success("Data Berhasil Disimpan!");
            },
            error: function(xhr, response, status) {
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
});

app.controller('settingGeneralBank', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Bank";
    $scope.formData={
        _token:csrfToken
    }
    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/bank/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            }, function(xhr){
                console.log(status)
                if (xhr.status==422) {
                  var msgs="";
                  $.each(xhr.data.errors, function(i, val) {
                    msgs+=val+'<br>';
                  });
                  toastr.warning(msgs,"Validation Error!");
                } else {
                  toastr.error(xhr.data.message,"Error has Found!");
                }
            });
        }
    }
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        order: [],
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
          extend: 'excel',
          enabled: true,
          action: newExportAction,
          text: '<span class="fa fa-file-excel-o"></span> Export Excel',
          className: 'btn btn-default btn-sm pull-right',
          filename: 'Bank',
          messageTop: 'Bank',
          sheetName: 'Data',
          title: 'Bank',
          exportOptions: {
            rows: {
              selected: true
            }
          },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/bank_datatable'
        },
        columns:[
        {data:"code",name:"code"},
        {data:"name",name:"name"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('setting.general_setting.bank.edit')) {
                $(row).find('td').attr('ng-click', 'edit(' + data.id + ')')
                $(row).find('td:last-child').removeAttr('ng-click')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });
    oTable.buttons().container().appendTo('.ibox-tools')
    $scope.add=function() {
        $scope.modalTitle="Add Bank";
        $scope.formData.code='';
        $scope.formData.name='';
        $scope.url=baseUrl+'/setting/general/store_bank';
        $('#modal').modal('show');
    }
    $scope.edit=function(ids) {
        $scope.modalTitle="Edit Bank";
        $http.get(baseUrl+'/setting/general/bank/'+ids).then(function(data) {
            $scope.formData.code=data.data.code;
            $scope.formData.name=data.data.name;
            $scope.url=baseUrl+'/setting/general/store_bank/'+ids;
            $('#modal').modal('show');
        });
    }

    $scope.submitForm=function() {
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formData,
            success: function(data){
                $('#modal').modal('hide');
                oTable.ajax.reload();
                toastr.success("Data Berhasil Disimpan!");
            },
        });
    }
});
app.controller('settingGeneralCustomer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Customer";
    $scope.formData={
        _token:csrfToken
    }
    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/customer_stage/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }
    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Customer stage',
            sheetName: 'Data',
            title: 'Customer stage',
            exportOptions: {
              rows: {
                selected: true
              }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/customer_stage_datatable'
        },
        columns:[
        {data:"name",name:"name"},
        {data:"bobot",name:"bobot"},
        {data:"is_prospect",name:"is_prospect",className:"text-center"},
        {data:"is_negotiation",name:"is_negotiation",className:"text-center"},
        {data:"is_close_deal",name:"is_close_deal",className:"text-center"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    oTable.buttons().container().appendTo('.ibox-tools')

    $scope.add=function() {
        $scope.modalTitle="Add Customer Stage";
        $scope.formData.bobot='';
        $scope.formData.name='';
        $scope.formData.is_close_deal=0;
        $scope.formData.is_prospect=0;
        $scope.formData.is_negotiation=0;
        $scope.url=baseUrl+'/setting/general/store_customer_stage';
        $('#modal').modal('show');
    }
    $scope.edit=function(ids) {
        $scope.modalTitle="Edit Customer Stage";
        $http.get(baseUrl+'/setting/general/customer_stage/'+ids).then(function(data) {
            $scope.formData.bobot=data.data.bobot;
            $scope.formData.name=data.data.name;
            $scope.formData.is_close_deal=data.data.is_close_deal;
            $scope.formData.is_prospect=data.data.is_prospect;
            $scope.formData.is_negotiation=data.data.is_negotiation;
            $scope.url=baseUrl+'/setting/general/store_customer_stage/'+ids;
            $('#modal').modal('show');
        });
    }

    $scope.submitForm=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formData,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                $('#modal').modal('hide');
                oTable.ajax.reload();
                toastr.success("Data Berhasil Disimpan!");
// $state.go('setting.company');
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
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

});

app.controller('settingGeneralService', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, unitsService) {
    $rootScope.pageTitle="General Setting | Layanan";
    $scope.formData={
        _token:csrfToken
    }

    $scope.showServiceType = function() {
        $http.get(baseUrl+'/setting/general/service_type').then(function(data) {
            $scope.service_type=data.data
        });
    }
    $scope.showServiceType()

    $scope.showServiceGroup = function() {
        $http.get(baseUrl+'/setting/general/service_group').then(function(data) {
            $scope.service_group=data.data
        });
    }
    $scope.showServiceGroup()

    $scope.showAccount = function() {
        $http.get(baseUrl+'/setting/general/account').then(function(data) {
            $scope.account=data.data
        });
    }
    $scope.showAccount()

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/service/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Layanan',
            sheetName: 'Data',
            title: 'Layanan',
            exportOptions: {
                rows: {
                    selected: true
                }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/service_datatable'
        },
        columns:[
            {data:"name",name:"name"},
            {data:"description",name:"description"},
            {data:"service_type.name",name:"service_type.name"},
            {data:"service_group.name",name:"service_group.name"},
            {data:"account_name",name:"account_name"},
            {data:"is_default",name:"is_default"},
            {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('setting.general_setting.piece.edit')) {
                $(row).find('td').attr('ng-click', 'edit(' + data.id + ')')
                $(row).find('td:last-child').removeAttr('ng-click')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo('.ibox-tools')

    $scope.add=function() {
        $scope.modalTitle="Add Layanan";
        $scope.is_edit = false
        $scope.formData={}
        $scope.formData.description='';
        $scope.formData.name='';
        $scope.formData.is_default=0;
        $scope.url=baseUrl+'/setting/general/store_service';
        $('#modal').modal('show');
    }
    if($rootScope.hasBuffer()) {
        $scope.add()
    }
    $('#modal').on('hidden.bs.modal', function(){
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        }
    })
    $scope.edit=function(ids) {
        $scope.is_edit = true
        $scope.modalTitle="Edit Service";
        $rootScope.showKpiStatus(ids)
        $http.get(baseUrl+'/setting/general/service/'+ids).then(function(data) {
            $scope.formData.name=data.data.item.name;
            $scope.formData.description=data.data.item.description;
            $scope.formData.is_overtime=data.data.item.is_overtime;
            $scope.formData.is_default=data.data.item.is_default;
            $scope.formData.service_type_id=data.data.item.service_type_id;
            $scope.formData.service_group_id=data.data.item.service_group_id;
            $scope.formData.account_sale_id=data.data.item.account_sale_id;
            $scope.formData.kpi_status_id=data.data.item.kpi_status_id;
            $scope.url=baseUrl+'/setting/general/store_service/'+ids;
          $('#modal').modal('show');
        });
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http.post($scope.url,$scope.formData).then(function(data) {
            $('#modal').modal('hide');
            toastr.success("Data Berhasil Disimpan!");
            oTable.ajax.reload();
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


app.controller('settingGeneralVendorJobStatus', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Vendor Job";
    $scope.formData={
        _token:csrfToken
    }

    $scope.delete=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/vendor_job_status/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX:false,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Vendor Job',
            sheetName: 'Data',
            title: 'Vendor Job',
            exportOptions: {
                rows: {
                    selected: true
                }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/vendor_job_status_datatable'
        },
        columns:[
            {data:"priority",name:"priority"},
            {data:"name",name:"name"},
            {
                data:null,
                searchable:false,
                orderable:false,
                className:'text-center',
                render:function(resp) {
                    var r = ''
                    if(resp.editable ) {
                        r = '<a ng-click=\'edit(' + JSON.stringify(resp) + ')\'><span class="fa fa-edit"></span></a>&nbsp;&nbsp;'
                        r += '<a ng-click="delete(' + resp.id + ')"><span class="fa fa-trash"></span></a>&nbsp;'
                    }
                    return r
                }
            }
        ],
        createdRow: function(row, data, dataIndex) {
            if(data.editable) {
                $(row).find('td').attr('ng-click', 'edit(' + JSON.stringify(data) + ')')
                $(row).find('td:last-child').removeAttr('ng-click')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo('.ibox-tools')

    $scope.add=function() {
        $scope.modalTitle="Add Vendor Job Status";
        $scope.formData={}
        $scope.formData.description='';
        $scope.formData.name='';
        $scope.formData.is_default=0;
        $scope.url=baseUrl+'/setting/vendor_job_status';
        $scope.method = 'post'
        $('#modal').modal('show');
    }
    if($rootScope.hasBuffer()) {
        $scope.add()
    }
    $('#modal').on('hidden.bs.modal', function(){
        if($rootScope.hasBuffer()) {
            $rootScope.accessBuffer()
        }
    })
    $scope.edit=function(d) {
        var ids = d.id
        $scope.modalTitle="Edit Vendor Job Status";
        $scope.formData.name=d.name;
        $scope.formData.priority=d.priority;
        $scope.url=baseUrl+'/setting/vendor_job_status/'+ids;
        $scope.method = 'put'
      $('#modal').modal('show');
    }

    $scope.disBtn=false;
    $scope.submitForm=function() {
        $scope.disBtn=true;
        $http[$scope.method]($scope.url,$scope.formData).then(function(data) {
            $('#modal').modal('hide');
            toastr.success("Data Berhasil Disimpan!");
            oTable.ajax.reload();
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

app.controller('settingGeneralContainer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, containerTypesService) {
    $rootScope.pageTitle="General Setting | Tipe Kontainer";
    $scope.formData={
        _token:csrfToken
    }

    $scope.sizes = [
        {
            'value' : 20,
            'unit' : 'STD'
        },
        {
            'value' : 40,
            'unit' : 'STD/HC'
        },
        {
            'value' : 40,
            'unit' : 'RF'
        },
    ];

    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            containerTypesService.api.delete(ids, function(){
                oTable.ajax.reload();
            })
        }
    }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Blfrtip',
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/container_datatable'
        },
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: $rootScope.solog.label.general.container_type,
            messageTop: $rootScope.solog.label.general.container_type,
            sheetName: 'Data',
            title: $rootScope.solog.label.general.container_type,
            exportOptions: {
                rows: {
                    selected: true
                }
            },
        }],
        columns:[
            {data:"code",name:"code"},
            {data:"name",name:"name"},
            {
                data:null,
                name:"size",
                render : resp => resp.size + ' ' + resp.unit
            },
            {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    oTable.buttons().container().appendTo('#export_button');

    $scope.add=function() {
        $scope.modalTitle="Add Container Type";
        $scope.formData.code='';
        $scope.formData.name='';
        $scope.formData.size=null;
        $scope.url=baseUrl+'/setting/general/store_container';
        $('#modal').modal('show');
    }
    $scope.edit=function(button) {
        $scope.modalTitle="Edit Container Type";
        var tr = $(button).parents('tr');
        var data = oTable.row(tr).data();
        $scope.formData.id = data.id;
        $scope.formData.code = data.code;
        $scope.formData.name = data.name;
        $scope.formData.size = {}
        $scope.formData.size = $scope.sizes.find(
            value => value.value == data.size && value.unit == data.unit
        );
        $scope.url=baseUrl+'/setting/general/store_container/'+data.id;
        $('#modal').modal('show');
    }

    $scope.submitForm=function() {
        $scope.formData.unit = $scope.formData.size.unit 
        $scope.formData.size = $scope.formData.size.value 
        if($scope.formData.id) {
            containerTypesService.api.update($scope.formData, $scope.formData.id, function(){
                $('#modal').modal('hide');
                oTable.ajax.reload()
            })
        } else {
            containerTypesService.api.store($scope.formData, function(){
                oTable.ajax.reload()
                $('#modal').modal('hide');
            })            
        }
    }
});
app.controller('settingGeneralSatuan', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, unitsService) {
    $rootScope.pageTitle="General Setting | Satuan";
    $scope.formData={
        _token:csrfToken
    }
    $scope.deletes=function(ids) {
        var cfs=confirm("Are you sure ?");
        if (cfs) {
            unitsService.api.delete(ids, function(){
                oTable.ajax.reload();
            })
        }
    }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
          extend: 'excel',
          enabled: true,
          action: newExportAction,
          text: '<span class="fa fa-file-excel-o"></span> Export Excel',
          className: 'btn btn-default btn-sm pull-right',
          filename: 'Satuan',
          sheetName: 'Data',
          title: 'Satuan',
          exportOptions: {
            rows: {
              selected: true
            }
          },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/satuan_datatable'
        },
        columns:[
            {data:"name",name:"name"},
            {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('setting.general_setting.piece.edit')) {
                $(row).find('td').attr('ng-click', 'edit(' + data.id + ')')
                $(row).find('td:last-child').removeAttr('ng-click')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });
    oTable.buttons().container().appendTo('.ibox-tools')

    $scope.add=function() {
        $scope.modalTitle="Add Unit";
        $scope.formData.code='';
        $scope.formData.name='';
        $('#modal').modal('show');
    }

    $scope.edit=function(ids) {
        $scope.modalTitle="Edit Unit";
        unitsService.api.show(ids, function(dt){
            $scope.formData = dt
        })
        $('#modal').modal('show');
    }

    $scope.submitForm=function() {
        if($scope.formData.id) {
            unitsService.api.update($scope.formData, $scope.formData.id, function(){
                $('#modal').modal('hide');
                oTable.ajax.reload()
            })
        } else {
            unitsService.api.store($scope.formData, function(){
                oTable.ajax.reload()
                $('#modal').modal('hide');
            })            
        }
    }
});
app.controller('settingGeneralCommodity', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Komoditas";
    $scope.formData={
        _token:csrfToken
    }
    $scope.deletes=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/commodity/'+ids,{_token:csrfToken}).then(function() {
                oTable.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }

    oTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Commodity',
            messageTop: 'Commodity',
            sheetName: 'Data',
            title: 'Commodity',
            exportOptions: {
                rows: {
                    selected: true
                }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/commodity_datatable'
        },
        columns:[
            // {data:"code",name:"code"},
            {data:"name",name:"name"},
            {data:"is_expired",name:"is_expired"},
            {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo('#export_button');

    $scope.add=function() {
        $scope.modalTitle="Add Commodity";
        $scope.formData.code='';
        $scope.formData.name='';
        $scope.formData.is_default=0;
        $scope.formData.is_expired=1;
        $scope.url=baseUrl+'/setting/general/store_commodity';
        $('#modal').modal('show');
    }
    $scope.edit=function(ids) {
        $scope.modalTitle="Edit Commodity";
        $http.get(baseUrl+'/setting/general/commodity/'+ids).then(function(data) {
            $scope.formData.name=data.data.name;
            $scope.formData.is_expired=data.data.is_expired;
            $scope.formData.is_default=data.data.is_default;
            $scope.url=baseUrl+'/setting/general/store_commodity/'+ids;
            $('#modal').modal('show');
        });
    }

    $scope.submitForm=function() {
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formData,
            success: function(data){
                $('#modal').modal('hide');
                oTable.ajax.reload();
                toastr.success("Data Berhasil Disimpan!");
            },
        });
    }
});

app.controller('settingGeneralAirPort', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Dermaga & Bandara";
    var tablePort = $('#port_datatable').DataTable({
        processing: true,
        serverSide: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Port',
            sheetName: 'Data',
            title: 'Port',
            exportOptions: {
              rows: {
                selected: true
              }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/port_datatable'
        },
        columns:[
        {data:"code",name:"code"},
        {data:"portname",name:"portname"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    tablePort.buttons().container().appendTo('#port .ibox-tools')

    var tableAirPort = $('#airport_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/airport_datatable'
        },
        columns:[
        {data:"code",name:"code"},
        {data:"portname",name:"portname"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.addPort=function() {
        $scope.formPort={};
        $scope.modalTitlePort="Add Port";
        $("#modal_port").modal('show');
        $scope.url=baseUrl+'/setting/general/store_port?_token='+csrfToken;
    }
    $scope.addAirPort=function() {
        $scope.formAirPort={};
        $scope.modalTitleAirPort="Add Bandara";
        $("#modal_airport").modal('show');
        $scope.url=baseUrl+'/setting/general/store_airport?_token='+csrfToken;
    }
    $scope.deletePort=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/port/'+ids,{_token:csrfToken}).then(function() {
                tablePort.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }
    $scope.deleteAirPort=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/airport/'+ids,{_token:csrfToken}).then(function() {
                tableAirPort.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }

    $http.get(baseUrl+'/setting/general/port/').then(function(data) {
        $scope.country=data.data.country;
    })

    $scope.editPort=function(ids) {
        $scope.formPort={};
        $scope.modalTitlePort="Edit Dermaga";
        $http.get(baseUrl+'/setting/general/port/'+ids).then(function(data) {
            $scope.formPort.code=data.data.code;
            $scope.formPort.name=data.data.name;
            $scope.formPort.island_name=data.data.island_name;
            $scope.formPort.country_id=data.data.country_id;
            $scope.url=baseUrl+'/setting/general/store_port/'+ids+'?_token='+csrfToken;
            $('#modal_port').modal('show');
        });
    }
    $scope.editAirPort=function(ids) {
        $scope.formAirPort={};
        $scope.modalTitlePort="Edit Dermaga";
        $http.get(baseUrl+'/setting/general/airport/'+ids).then(function(data) {
            $scope.formAirPort.code=data.data.code;
            $scope.formAirPort.name=data.data.name;
            $scope.formAirPort.island_name=data.data.island_name;
            $scope.url=baseUrl+'/setting/general/store_airport/'+ids+'?_token='+csrfToken;
            $('#modal_airport').modal('show');
        });
    }
    $scope.disBtn=false;
    $scope.submitPort=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formPort,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modal_port").modal('hide');
                tablePort.ajax.reload();
// $state.go('setting.company');
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
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
    $scope.submitAirPort=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formAirPort,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modal_airport").modal('hide');
                tableAirPort.ajax.reload();
// $state.go('setting.company');
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
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

});
app.controller('settingGeneralVendor', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Tipe Vendor & Alamat";
    var tableVendorType = $('#vendor_type_datatable').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Vendor type',
            sheetName: 'Data',
            title: 'Vendor type',
            exportOptions: {
              rows: {
                selected: true
              }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/vendor_type_datatable'
        },
        columns:[
        {data:"name",name:"name"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    tableVendorType.buttons().container().appendTo('#vendor_category .ibox-tools')

    var tableAddressType = $('#address_type_datatable').DataTable({
        processing: true,
        serverSide: true,
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Address type',
            sheetName: 'Data',
            title: 'Address type',
            exportOptions: {
              rows: {
                selected: true
              }
            },
        }],
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/address_type_datatable'
        },
        columns:[
        {data:"name",name:"name"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    tableAddressType.buttons().container().appendTo('#address_type .ibox-tools')

    $scope.addVendorType=function() {
        $scope.formVendorType={};
        $scope.modalTitleVendorType="Add Vendor Type";
        $("#modal_vendor_type").modal('show');
        $scope.url=baseUrl+'/setting/general/store_vendor_type?_token='+csrfToken;
    }
    $scope.addAddressType=function() {
        $scope.formAddressType={};
        $scope.modalTitleAddressType="Add Address Type";
        $("#modal_address_type").modal('show');
        $scope.url=baseUrl+'/setting/general/store_address_type?_token='+csrfToken;
    }
    $scope.deleteVendorType=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/vendor_type/'+ids,{_token:csrfToken}).then(function() {
                tableVendorType.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }
    $scope.deleteAddressType=function(ids) {
        var cfs=confirm("Apakah Anda Yakin?");
        if (cfs) {
            $http.delete(baseUrl+'/setting/general/address_type/'+ids,{_token:csrfToken}).then(function() {
                tableAddressType.ajax.reload();
                toastr.success("Data Berhasil Dihapus!");
            });
        }
    }

    $scope.editVendorType=function(ids) {
        $scope.formVendorType={};
        $scope.modalTitleVendorType="Edit Tipe Vendor";
        $http.get(baseUrl+'/setting/general/vendor_type/'+ids).then(function(data) {
            $scope.formVendorType.name=data.data.name;
            $scope.url=baseUrl+'/setting/general/store_vendor_type/'+ids+'?_token='+csrfToken;
            $('#modal_vendor_type').modal('show');
        });
    }
    $scope.editAddressType=function(ids) {
        $scope.formAddressType={};
        $scope.modalTitleAddressType="Edit Address Type";
        $http.get(baseUrl+'/setting/general/address_type/'+ids).then(function(data) {
            $scope.formAddressType.name=data.data.name;
            $scope.url=baseUrl+'/setting/general/store_address_type/'+ids+'?_token='+csrfToken;
            $('#modal_address_type').modal('show');
        });
    }
    $scope.disBtn=false;
    $scope.submitVendorType=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formVendorType,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modal_vendor_type").modal('hide');
                tableVendorType.ajax.reload();
// $state.go('setting.company');
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
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
    $scope.submitAddressType=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formAddressType,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modal_address_type").modal('hide');
                tableAddressType.ajax.reload();
// $state.go('setting.company');
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
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

});
app.controller('settingGeneralLead', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="General Setting | Leads";
    var tableStatus = $('#status_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/lead_status_datatable'
        },
        columns:[
        {data:"status",name:"status"},
        {data:"is_active",name:"is_active"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    var tableSource = $('#source_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/lead_source_datatable'
        },
        columns:[
        {data:"name",name:"name"},
        {data:"is_active",name:"is_active"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
    var tableIndustry = $('#industry_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax : {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/setting/industry_datatable'
        },
        columns:[
        {data:"code",name:"code"},
        {data:"name",name:"name"},
        {data:"is_active",name:"is_active"},
        {data:"action",name:"action",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.editStatus=function(ids) {
        $scope.formStatus={};
        $scope.modalTitleStatus="Edit Status Lead";
        $http.get(baseUrl+'/setting/general/lead_status/'+ids).then(function(data) {
            $scope.formStatus.status=data.data.status;
            $scope.formStatus.is_active=data.data.is_active;
            $scope.url=baseUrl+'/setting/general/store_lead_status/'+ids+'?_token='+csrfToken;
            $('#modalStatus').modal('show');
        });
    }
    $scope.editSource=function(ids) {
        $scope.formSource={};
        $http.get(baseUrl+'/setting/general/lead_source/'+ids).then(function(data) {
            $scope.formSource.name=data.data.name;
            $scope.formSource.is_active=data.data.is_active;
            $scope.url=baseUrl+'/setting/general/store_lead_source/'+ids+'?_token='+csrfToken;
            $('#modalSource').modal('show');
        });
    }
    $scope.editIndustry=function(ids) {
        $scope.formIndustry={};
        $scope.modalTitleIndustry="Edit Industri";
        $http.get(baseUrl+'/setting/general/industry/'+ids).then(function(data) {
            $scope.formIndustry.code=data.data.code;
            $scope.formIndustry.name=data.data.name;
            $scope.formIndustry.is_active=data.data.is_active;
            $scope.url=baseUrl+'/setting/general/store_industry/'+ids+'?_token='+csrfToken;
            $('#modalIndustry').modal('show');
        });
    }
    $scope.addIndustry=function(ids) {
        $scope.formIndustry={};
        $scope.formIndustry.is_active=1;
        $scope.modalTitleIndustry="Add Industri";
        $scope.url=baseUrl+'/setting/general/store_industry?_token='+csrfToken;
        $('#modalIndustry').modal('show');
    }
    $scope.disBtn=false;
    $scope.submitStatus=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formStatus,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modalStatus").modal('hide');
                tableStatus.ajax.reload();
// $state.go('setting.company');
},
error: function(xhr, response, status) {
    $scope.$apply(function() {
        $scope.disBtn=false;
    });
// console.log(xhr);
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
    $scope.submitSource=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formSource,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modalSource").modal('hide');
                tableSource.ajax.reload();
            },
            error: function(xhr, response, status) {
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
// console.log(xhr);
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
    $scope.submitIndustry=function() {
        $scope.disBtn=true;
        $.ajax({
            type: "post",
            url: $scope.url,
            data: $scope.formIndustry,
            success: function(data){
                $scope.$apply(function() {
                    $scope.disBtn=false;
                });
                toastr.success("Data Berhasil Disimpan");
                $("#modalIndustry").modal('hide');
                tableIndustry.ajax.reload();
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

});
