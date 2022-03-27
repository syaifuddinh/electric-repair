app.controller('vendorList', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Daftar Vendor";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vendor/vendor_datatable'
    },
    columns:[
      {data:"codes",name:"codes"},
      {data:"name",name:"name"},
      {data:"address",name:"address"},
      {data:"phone",name:"phone"},
      {data:"email",name:"email"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

});
app.controller('vendorListShowPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tarif Vendor";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax : {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/vendor/vendor_price_datatable/'+$stateParams.id
    },
    columns:[
      {data:"cabang",name:"companies.name"},
      {data:"cost_type_name",name:"cost_types.name"},
      {data:"trayek",name:"routes.name"},
      {data:"vtype",name:"vtype"},
      {data:"price_full",name:"price_full",className:'text-right'},
      {data:"action_approve",name:"created_at"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

});
app.controller('vendorListShowPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Tarif Vendor";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.isedit=false;
  $scope.formData={};
  $scope.id = $stateParams.id
  $http.get(baseUrl+'/vendor/register_vendor/create_price/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.type_1=data.data.type_1;
    $scope.type_2=data.data.type_2;
    $scope.type_3=data.data.type_3;
    $scope.type_4=data.data.type_4;
    $scope.type_5=data.data.type_5;
    $scope.type_6=data.data.type_6;
    $scope.type_7=data.data.type_7;
    $scope.formData.company_id=data.data.item.company_id;
    $('.ibox-content').toggleClass('sk-loading');
  });
  $scope.div_tarif=false;

  $scope.showCostType = function() {
      $http.get(baseUrl+'/setting/cost_type').then(function(data) {
          $scope.cost_type = data.data
      }, function(){
          $scope.showCostType()
      });
  }
  $scope.showCostType()

  $scope.dom_switch=function(ids,company,isedit) {
    // console.log(ids);
    $scope.formData={};
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    if ($scope.type_1.indexOf(ids)!==-1) {
      $scope.formData.stype_id=1;
      $scope.formData.price_tonase=0;
      $scope.formData.min_tonase=0;
      $scope.formData.price_volume=0;
      $scope.formData.min_volume=0;
      $scope.formData.price_item=0;
      $scope.formData.min_item=0;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=true;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=true;
      $scope.div_tarif=false;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_2.indexOf(ids)!==-1) {
      $scope.formData.stype_id=2;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=true;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_3.indexOf(ids)!==-1) {
      $scope.formData.stype_id=3;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_4.indexOf(ids)!==-1) {
      $scope.formData.stype_id=4;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_5.indexOf(ids)!==-1) {
      $scope.formData.stype_id=5;
      $scope.div_trayek=false;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_rack=true;
      $scope.div_storage_tonase=true;
      $scope.div_storage_volume=true;
      $scope.div_handling_tonase=true;
      $scope.div_handling_volume=true;
    } else if ($scope.type_6.indexOf(ids)!==-1) {
      $scope.formData.stype_id=6;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else {
      $scope.formData.stype_id=7;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    }
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.formData.vendor_id = $scope.id
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/vendor_price?_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('vendor.vendor.show.price', {'id' : $scope.id});
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        if (xhr.status==422) {
          var msgs="";
          $.each(xhr.responseJSON.errors, function(i, val) {
            msgs+='- '+val+'<br>';
          });
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});
app.controller('vendorListShowPriceEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit Tarif Vendor";
  $('.ibox-content').toggleClass('sk-loading');
  $scope.isedit=false;

  $scope.id = $stateParams.id
  $http.get(baseUrl+'/vendor/register_vendor/edit_price/'+$stateParams.idprice).then(function(data) {
    $scope.data=data.data;
    var dt=data.data.item;
    $scope.type_1=data.data.type_1;
    $scope.type_2=data.data.type_2;
    $scope.type_3=data.data.type_3;
    $scope.type_4=data.data.type_4;
    $scope.type_5=data.data.type_5;
    $scope.type_6=data.data.type_6;
    $scope.type_7=data.data.type_7;
    $scope.formData={
      company_id:dt.company_id,
      cost_type_id:dt.cost_type_id,
      cost_category:dt.cost_category,
      date:dt.date,
      min_tonase:dt.min_tonase,
      price_tonase:dt.price_tonase,
      min_volume:dt.min_volume,
      price_volume:dt.price_volume,
      min_item:dt.min_item,
      price_item:dt.price_item,
      price_full:dt.price_full,
      route_id:dt.route_id,
      commodity_id:dt.commodity_id,
      name:dt.name,
      piece_id:dt.piece_id,
      service_id:dt.service_id,
      moda_id:dt.moda_id,
      vehicle_type_id:dt.vehicle_type_id,
      description:dt.description,
      price_handling_tonase:dt.price_handling_tonase,
      price_handling_volume:dt.price_handling_volume,
      rack_id:dt.rack_id,
      container_type_id:dt.container_type_id,
      stype_id:dt.service_type_id,
    }

    $scope.formData.date = $scope.formData.date.replace(/(\d{4})-(\d{2})-(\d{2})/, '$3-$2-$1') 
    $('.ibox-content').toggleClass('sk-loading');
  });

  $scope.showCostType = function() {
      $http.get(baseUrl+'/setting/cost_type').then(function(data) {
          $scope.cost_type = data.data
      }, function(){
          $scope.showCostType()
      });
  }
  $scope.showCostType()

  $scope.div_tarif=false;
  $scope.dom_switch=function(ids,company,isedit) {
    // console.log(ids);
    if (isedit==false) {
      $scope.formData={};
    }
    $scope.formData.service_id=ids;
    $scope.formData.company_id=company;
    $scope.formData.code=$scope.data.item.code;
    $scope.formData.name=$scope.data.item.name;

    if ($scope.type_1.indexOf(ids)!==-1) {
      $scope.formData.stype_id=1;
      if (isedit==false) {
        $scope.formData.price_tonase=0;
        $scope.formData.min_tonase=0;
        $scope.formData.price_volume=0;
        $scope.formData.min_volume=0;
        $scope.formData.price_item=0;
        $scope.formData.min_item=0;
      }
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=true;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=true;
      $scope.div_tarif=false;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_2.indexOf(ids)!==-1) {
      $scope.formData.stype_id=2;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=true;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_3.indexOf(ids)!==-1) {
      $scope.formData.stype_id=3;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_4.indexOf(ids)!==-1) {
      $scope.formData.stype_id=4;
      $scope.div_trayek=true;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=true;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else if ($scope.type_5.indexOf(ids)!==-1) {
      $scope.formData.stype_id=5;
      $scope.div_trayek=false;
      $scope.div_komoditas=true;
      $scope.div_satuan=false;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=false;
      $scope.div_rack=true;
      $scope.div_storage_tonase=true;
      $scope.div_storage_volume=true;
      $scope.div_handling_tonase=true;
      $scope.div_handling_volume=true;
    } else if ($scope.type_6.indexOf(ids)!==-1) {
      $scope.formData.stype_id=6;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    } else {
      $scope.formData.stype_id=7;
      $scope.div_trayek=false;
      $scope.div_komoditas=false;
      $scope.div_satuan=true;
      $scope.div_moda=false;
      $scope.div_armada=false;
      $scope.div_container=false;
      $scope.div_tarif_min=false;
      $scope.div_tarif=true;
      $scope.div_rack=false;
      $scope.div_storage_tonase=false;
      $scope.div_storage_volume=false;
      $scope.div_handling_tonase=false;
      $scope.div_handling_volume=false;
    }
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $scope.formData.vendor_id = $scope.id
    $.ajax({
      type: "post",
      url: baseUrl+'/marketing/vendor_price/'+$stateParams.idprice+'?_method=PUT&_token='+csrfToken,
      data: $scope.formData,
      success: function(data){
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        toastr.success("Data Berhasil Disimpan");
        $state.go('vendor.vendor.show.price', {'id':$scope.id});
      },
      error: function(xhr, response, status) {
        $scope.$apply(function() {
          $scope.disBtn=false;
        });
        // console.log(xhr);
        if (xhr.status==422) {
          var msgs="";
          $.each(xhr.responseJSON.errors, function(i, val) {
            msgs+='- '+val+'<br>';
          });
          toastr.warning(msgs,"Validation Error!");
        } else {
          toastr.error(xhr.responseJSON.message,"Error has Found!");
        }
      }
    });
  }

});
