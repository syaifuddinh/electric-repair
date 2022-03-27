app.controller('KelompokAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Kelompok Asset";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url : baseUrl+'/api/finance/asset_group_datatable',
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"name",name:"name",className:""},
      // {data:"date_needed",name:"date_needed",className:""},
      {data:"method",name:"method",className:"text-center"},
      {data:"description",name:"description"},
      {data:"action",name:"id",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.deletes=function(ids) {
    var cfs=confirm("Apakah Anda Yakin?");
    if (cfs) {
      $http.delete(baseUrl+'/finance/asset_group/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

});

app.controller('KelompokAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={}
  $scope.formData.method=1

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]

  $http.get(baseUrl+'/finance/asset_group/create').then(function(data) {
    $scope.data=data.data;
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/asset_group',$scope.formData).then(function(data) {
      $state.go('finance.kelompok_asset');
      toastr.success("Data Berhasil Disimpan!");
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
app.controller('KelompokAssetEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Edit";
  $scope.formData={}

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]

  $http.get(baseUrl+'/finance/asset_group/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data=data.data;
    var dt=$scope.data.item;
    $scope.formData.code=dt.code
    $scope.formData.name=dt.name
    $scope.formData.method=dt.method
    $scope.formData.umur_ekonomis=dt.umur_ekonomis
    $scope.formData.account_asset_id=dt.account_asset_id
    $scope.formData.account_accumulation_id=dt.account_accumulation_id
    $scope.formData.account_depreciation_id=dt.account_depreciation_id
    $scope.formData.description=dt.description
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.put(baseUrl+'/finance/asset_group/'+$stateParams.id,$scope.formData).then(function(data) {
      $state.go('finance.kelompok_asset');
      toastr.success("Data Berhasil Disimpan!");
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

app.controller('SaldoAwal', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Saldo Awal Asset";

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url : baseUrl+'/api/finance/asset_datatable',
    },
    columns:[
      {data:"company.name",name:"company.name",className:""},
      {data:"code",name:"code",className:"font-bold"},
      {data:"name",name:"name",className:""},
      {data:"asset_group.name",name:"asset_group.name",className:""},
      // {data:"date_needed",name:"date_needed",className:""},
      {data:"asset_type",name:"asset_type",className:"text-center"},
      {data:"method",name:"method",className:"text-center"},
      {data:"status",name:"status",className:"text-center"},
      {data:"action",name:"id",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.asset_type=[
    {id:1,name:'Aset tidak Berwujud'},
    {id:2,name:'Aset Berwujud'},
  ]

});

app.controller('SaldoAwalAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={};
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
  $scope.formData.purchase_price=0;
  $scope.formData.method=1;

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.type=[
    {id:1,name:'Aset tidak Berwujud'},
    {id:2,name:'Aset Berwujud'},
  ]

  $http.get(baseUrl+'/finance/asset/create').then(function(data) {
    $scope.data=data.data;
  });

  $scope.assetGroupChange=function(val) {
    var dt=$rootScope.findJsonId(val,$scope.data.asset_group)
    $scope.formData.umur_ekonomis=dt.umur_ekonomis
    $scope.formData.method=dt.method
    $scope.formData.account_asset_id=dt.account_asset_id
    $scope.formData.account_depreciation_id=dt.account_depreciation_id
    $scope.formData.account_accumulation_id=dt.account_accumulation_id
  }

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn=true;
    $http.post(baseUrl+'/finance/asset/',$scope.formData).then(function(data) {
      $state.go('finance.saldoawal_asset');
      toastr.success("Data Berhasil Disimpan!");
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

app.controller('SaldoAwalAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Saldo Awal Asset";

  $http.get(baseUrl+'/finance/asset/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.item=data.data.item;
  });

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.asset_type=[
    {id:1,name:'Aset Tidak Berwujud'},
    {id:2,name:'Aset Berwujud'},
  ]

});

app.controller('PembelianAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Pembelian  Asset";
});

app.controller('PembelianAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah";
  $scope.formData={};
  $scope.formData.detail=[];
  $scope.detail={};
  $scope.formData.company_id=compId;
  $scope.formData.type_bayar=1;
  $scope.formData.termin=1;
  $scope.formData.date_transaction=dateNow;
  $scope.detail.harga=0;
  $scope.detail.residu=0;

  $scope.termin=[
    {id:1,name:"Cash"},
    {id:2,name:"Kredit"},
  ];

   var urut=0;
   $scope.appendTable=function() {
    var dt=$scope.detail;

    var html="";
    html+="<tr id='row-"+urut+"'>";
    html+="<td>"+$('#name').val()+"</td>";
    html+="<td>"+$scope.detail.harga+"</td>";
    html+="<td>"+$scope.detail.residu+"</td>";
    html+="<td>"+$scope.formData.date_transaction+"</td>";
    html+="<td>"+$('#desc').val()+"</td>";

    html+="<td><a ng-click='deleteAppend("+urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.formData.detail.push($scope.detail);

    $scope.detail={};
    $scope.detail.residu=0;
    $scope.detail.harga=0;
    $scope.detail.code=''
    $scope.detail.name=''
    $scope.detail.tipe=0
    $scope.detail.ue=0
    $scope.detail.metode=0
    $scope.detail.kelompok=0
    urut++;
      $scope.hitungTotalBayar();
  }

   $scope.hitungTotalBayar=function() {
    var total=0;
    angular.forEach($scope.formData.detail,function(val,i) {
      total+=parseFloat(val.harga);
    });
    $scope.formData.total_bayar=total;
  }

  $scope.deleteAppend=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.detail[ids];
    $scope.hitungTotalBayar();
  }
});

app.controller('PembelianAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Pembelian Asset";
});

app.controller('DaftarAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Daftar Asset";
});

app.controller('DaftarAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Asset";
});


app.controller('DepresiasiAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Depresiasi Asset";
});

app.controller('DepresiasiAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Depresiasi";
});

app.controller('PengafkiranAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Penghapusan Asset";
});

app.controller('PengafkiranAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Penghapusan Asset";
});

app.controller('PengafkiranAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Penghapusan Asset";
  $scope.formData={};
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
});

app.controller('PenjualanAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Penjualan Asset";

});

app.controller('PenjualanAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Penjualan Asset";
  $scope.formData={};
  $scope.formData.detail=[];
  $scope.detail={};
  $scope.formData.company_id=compId;
  $scope.formData.type_bayar=1;
  $scope.formData.termin=1;
  $scope.formData.date_transaction=dateNow;
  $scope.detail.nb=0;
  $scope.detail.harga=0;

  $scope.termin=[
    {id:1,name:"Cash"},
    {id:2,name:"Kredit"},
  ];

   var urut=0;
   $scope.appendTable=function() {
    var dt=$scope.detail;

    var html="";
    html+="<tr id='row-"+urut+"'>";
    html+="<td></td>";
    html+="<td>"+$scope.detail.harga+"</td>";
    html+="<td>"+$scope.detail.nb+"</td>";
    html+="<td>"+$scope.detail.description+"</td>";

    html+="<td><a ng-click='deleteAppend("+urut+")'><span class='fa fa-trash'></span></a></td>";
    html+="</tr>";

    $('#appendTable tbody').append($compile(html)($scope));
    $scope.formData.detail.push($scope.detail);

    $scope.detail={};
    $scope.detail.nb=0;
    $scope.detail.harga=0;
    urut++;
      $scope.hitungTotalBayar();
  }

   $scope.hitungTotalBayar=function() {
    var total=0;
    angular.forEach($scope.formData.detail,function(val,i) {
      total+=parseFloat(val.harga);
    });
    $scope.formData.total_bayar=total;
  }

  $scope.deleteAppend=function(ids) {
    $('#row-'+ids).remove();
    delete $scope.formData.detail[ids];
    $scope.hitungTotalBayar();
  }
});

app.controller('PenjualanAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Penjualan Asset";

});
