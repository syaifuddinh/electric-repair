app.controller('KelompokAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Kelompok Asset";
    $('.ibox-content').addClass('sk-loading');

    oTable = $('#datatable').DataTable({
        processing: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        serverSide: true,
        dom: 'Blfrtip',
        buttons: [{
            extend: 'excel',
            enabled: true,
            action: newExportAction,
            text: '<span class="fa fa-file-excel-o"></span> Export Excel',
            className: 'btn btn-default btn-sm pull-right',
            filename: 'Kelompok Asset',
            sheetName: 'Data',
            title: 'Kelompok Asset',
            exportOptions: {
                rows: {
                    selected: true
                }
            },
        }],
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/finance/asset_group_datatable',
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },
        columns:[
            {data:"code",name:"code",className:"font-bold"},
            {data:"name",name:"name",className:""},
            {data:"method",name:"method",className:"text-center"},
            {data:"umur_ekonomis",name:"umur_ekonomis"},
            {data:"action",name:"id",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            if($rootScope.roleList.includes('finance.asset.group.edit')) {
                $(row).find('td').attr('ui-sref', 'finance.kelompok_asset.edit({id:' + data.id + '})')
                $(row).find('td:last-child').removeAttr('ui-sref')
            } else {
                $(oTable.table().node()).removeClass('table-hover')
            }
            $compile(angular.element(row).contents())($scope);
        }
    });

    oTable.buttons().container().appendTo('.ibox-tools')

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
    $('.ibox-content').addClass('sk-loading');

    $scope.method=[
        {id:1,name:'Garis Lurus'}
    ]

    $http.get(baseUrl+'/finance/asset_group/create').then(function(data) {
        $scope.data=data.data;
        $('.ibox-content').removeClass('sk-loading');
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
  $('.ibox-content').addClass('sk-loading');

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
    $('.ibox-content').removeClass('sk-loading');
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
  $scope.formData = {};
  $('.ibox-content').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    serverSide: true,
    dom: 'Blfrtip',
    buttons: [
      {
        'extend' : 'excel',
        'enabled' : true,
        'text' : '<span class="fa fa-file-excel-o"></span> Export Excel',
        'className' : 'btn btn-default btn-sm',
        'filename' : 'Saldo Awal - '+new Date(),
        'sheetName' : 'Data',
        'title' : 'Saldo Awal'
      },
    ],
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/asset_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },

    columns:[
      {data:"comp",name:"c.name",className:""},
      {data:"code",name:"code",className:"font-bold"},
      {data:"name",name:"name",className:""},
      {data:"ag_name",name:"ag.name",className:""},
      // {data:"date_needed",name:"date_needed",className:""},
      {data:"asset_type",name:"asset_type",className:"text-center"},
      {data:"method",name:"method",className:"text-center"},
      {data:"status",name:"status",className:"text-center"},
      {data:null,name:"id",className:"text-center",render: function(res) {
        const html = `
        <a ui-sref="finance.saldoawal_asset.show({id:${res.id}})" title='Show Detail'><span class='fa fa-folder-o' title='Detail Data'></span></a>&nbsp;
        <a ui-sref="finance.saldoawal_asset.edit({id:${res.id}})" ng-if="${res.j_status} != 3" title='Show Detail'><span class='fa fa-edit' title='Edit Data'></span></a>&nbsp;
        <a title='Delete' ng-if="${res.j_status} != 3" ng-click="deletes(${res.id})"><span class='fa fa-trash' title='Edit Data'></span></a>
        `
        return html
      }},
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ui-sref', 'finance.saldoawal_asset.show({id:' + data.id + '})')
      $(row).find('td:last-child').removeAttr('ui-sref')
      $compile(angular.element(row).contents())($scope);
    }
  });

  oTable.buttons().container().appendTo( '#button_export_saldo_awal' );


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
      $http.delete(baseUrl+'/finance/asset/'+ids,{_token:csrfToken}).then(function success(data) {
        // $state.reload();
        oTable.ajax.reload();
        toastr.success("Data Berhasil Dihapus!");
      }, function error(data) {
        toastr.error("Tidak dapat menghapus data karna sudah tercatat transaksi!","Error Has Found!");
      });
    }
  }

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.asset_type=[
    {id:1,name:'Aset tidak Berwujud'},
    {id:2,name:'Aset Berwujud'},
  ]

});

app.controller('SaldoAwalAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Tambah Saldo Asset";
  $scope.formData={};
  $scope.formData.company_id=compId;
  $scope.formData.date_transaction=dateNow;
  $scope.formData.purchase_price=0;
  $scope.formData.method=1;
  $scope.formData.asset_type=1;
  $('.ibox-content').addClass('sk-loading');

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.type=[
    {id:1,name:'Aset Berwujud'},
    {id:2,name:'Aset Tidak Berwujud'},
  ]

  $http.get(baseUrl+'/finance/asset/create').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
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
app.controller('SaldoAwalAssetEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Edit Saldo Asset";
  $scope.formData={};
  $scope.data = []
  $scope.type=[
    {id:1,name:'Aset Berwujud'},
    {id:2,name:'Aset Tidak Berwujud'},
  ]
  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.init = async function(){
    $scope.disBtn = true
    try {
      let data = await $http.get(`${baseUrl}/finance/asset/create`);
      let item = await $http.get(`${baseUrl}/finance/asset/${$stateParams.id}/edit`);
      $scope.data = data.data
      $scope.formData = item.data.data
      $scope.formData.date_purchase = $filter('minDate')(item.data.data.date_purchase)
    } catch(e) {
      console.log(e)
    }
    $scope.disBtn = false
  }
  $scope.init()
  $scope.submitForm=async function() {
    $scope.disBtn = true
    try {
      await $http.put(`${baseUrl}/finance/asset/${$stateParams.id}`, $scope.formData)
      $scope.disBtn = false
      toastr.success("Saldo Asset berhasil dirubah!");
      $state.go(`finance.saldoawal_asset`);
    } catch(e) {
      $scope.disBtn = false
      console.log(e)
    }
  }
})

app.controller('SaldoAwalAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Saldo Awal Asset";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/finance/asset/'+$stateParams.id).then(function(data) {
    $scope.data=data.data;
    $scope.item=data.data.item;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ]
  $scope.asset_type=[
    {id:1,name:'Aset Berwujud'},
    {id:2,name:'Aset Tidak Berwujud'},
  ]

});

app.controller('PembelianAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Pembelian  Asset";
  $scope.formData = {};
  $('.ibox-content').addClass('sk-loading');

  oTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
    dom:'lfrtip',
    ajax: {
      headers : {'Authorization' : 'Bearer '+authUser.api_token},
      url : baseUrl+'/api/finance/asset_purchase_datatable',
      dataSrc: function(d) {
        $('.ibox-content').removeClass('sk-loading');
        return d.data;
      }
    },
    columns:[
      {data:"code",name:"code",className:"font-bold"},
      {data:"company.name",name:"company.name"},
      { 
        data:null,
        orderable:false,
        searchable:false,
        render:resp => $filter('fullDate')(resp.date_transaction)
      },
      {data:"supplier.name",name:"supplier.name"},
      {data:"total_price",name:"total_price"},
      {data:"status",name:"status",className:"text-center"},
      {data:"action",name:"action",className:"text-center"},
    ],
    createdRow: function(row, data, dataIndex) {
      $(row).find('td').attr('ui-sref', 'finance.pembelian_asset.show({id:' + data.id + '})')
      $(row).find('td:last-child').removeAttr('ui-sref')
      $compile(angular.element(row).contents())($scope);
    }
  });

  $scope.searchData = function() {
    oTable.ajax.reload();
  }

  $scope.resetFilter = function() {
    $scope.formData = {};
    oTable.ajax.reload();
  }

  $scope.method=[
    {id:1,name:'Garis Lurus'}
  ];

  $scope.asset_type=[
    {id:1,name:'Aset Berwujud'},
    {id:2,name:'Aset Tidak Berwujud'},
  ];
});

app.controller('PembelianAssetCreate',
  function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Tambah";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={};
    $scope.formData.detail=[];
    $scope.detail={};
    $scope.formData.company_id=compId;
    $scope.formData.type_bayar=1;
    $scope.formData.termin=1;
    $scope.formData.date_transaction=dateNow;
    $scope.detail.price=0;
    $scope.detail.residu=0;
    var urut=0;

    $scope.type_asset = [
      {id:1,name:'Aset Berwujud'},
      {id:2,name:'Aset Tidak Berwujud'},
    ];

    $scope.termin = [
      {id:1,name:"Cash"},
      {id:2,name:"Kredit"},
    ];

    $http.get(baseUrl+'/finance/asset/create').then(function(data) {
      $scope.data = data.data;
      $scope.data.cash_account = data.data.account;
      $('.ibox-content').removeClass('sk-loading');
    });

    $scope.appendTable = function() {
      $scope.detail.id = Math.floor(Math.random() * 9999)
      $scope.formData.detail.push($scope.detail);

      $scope.detail={};
      $scope.detail.residu=0;
      $scope.detail.price=0;
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
        total+=parseFloat(val.price);
      });
      $scope.formData.total_bayar=total;
    }
    $scope.hitungTotalBayar()

    $scope.edit = function(id) {
        var detail = $scope.formData.detail.find(x => x.id == id)
        if(detail) {
            $scope.detail = Object.assign({}, detail)
        }
    }

    $scope.show = function() {
        if($stateParams.id) {
            $http.get(baseUrl+'/finance/asset_purchase/' + $stateParams.id).then(function(data) {
                $scope.formData=data.data.item;
                $scope.formData.detail=data.data.details;
                $scope.formData.contact_id = parseInt($scope.formData.supplier_id)
                $scope.formData.type_bayar = ($scope.formData.termin > 1) ? 2 : 1
                $scope.hitungTotalBayar()
            });            
        }
    }

    $scope.show()

    $scope.cancelEdit = function() {
        $scope.detail = {}
    }

    $scope.updateTable = function() {
        if($scope.detail.id) {
            var index = $scope.formData.detail.findIndex(x => x.id == $scope.detail.id)
            if(index > -1) {
                $scope.formData.detail[index] = $scope.detail
                $scope.detail = {}
            }
        }
    }

    $scope.deleteAppend=function(ids) {
        $scope.formData.detail.splice(ids, 1);
        $scope.hitungTotalBayar();
    }

    $scope.submitForm = function() {
      $scope.disBtn=true;
      var url = baseUrl+'/finance/asset_purchase'
      var method = 'post'
      if($stateParams.id) {
          url = baseUrl+'/finance/asset_purchase/' + $stateParams.id
          method = 'put'
      }
      $http[method](
        url,
        $scope.formData)
      .then(
        function(data) {
          $timeout(function() {
              $state.reload();
          },1000)

          $scope.disBtn=false;

          toastr.success("Data Berhasil Disimpan!");
        },
        function(error) {
            $scope.disBtn = false;
            if (error.status == 422) {
              var det="";
              angular.forEach(error.data.errors,function(val,i) {
                det+="- "+val+"<br>";
              });
              toastr.warning(det,error.data.message);
            } else {
              toastr.error(error.data.message,"Error Has Found !");
            }
        }
      );
    }
  }
);

app.controller(
  'PembelianAssetShow',
  function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = "Detail Pembelian Asset";
    $scope.item = {};
    $('.ibox-content').addClass('sk-loading');

    $scope.status = [
      {id:1, name:'Draft'},
      {id:2, name:'Available'}
    ]

    $scope.approve = function() {
      $scope.disBtn=true;
      $http.post(
        baseUrl+'/finance/asset_purchase/approve/'+$stateParams.id,
        $scope.item)
      .then(
        function(data) {
          $timeout(function() {
            $state.reload();
          },1000)

          $scope.disBtn=false;

          toastr.success("Data Berhasil Disimpan!");
        },
        function(error) {
          $scope.disBtn = false;
          if (error.status == 422) {
            var det="";
            angular.forEach(error.data.errors,function(val,i) {
                det+="- "+val+"<br>";
            });
            toastr.warning(det,error.data.message);
          } else {
            toastr.error(error.data.message,"Error Has Found !");
          }
        }
      );
    }

    $http.get(baseUrl+'/finance/asset_purchase/'+$stateParams.id)
    .then(function(data) {
        $scope.item = data.data.item;
        $scope.details = data.data.details;
        $('.ibox-content').removeClass('sk-loading');
    });
});

app.controller('DaftarAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Daftar Asset";
    $scope.isFilter = false;
    $scope.formData = {};
    $('.ibox-content').addClass('sk-loading');

    $http.get(baseUrl+'/operational_warehouse/receipt/create').then(function(data) {
        $scope.data=data.data;
    });

    oTable = $('#datatable').DataTable({
        processing: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        serverSide: true,
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/finance/daftarasset_datatable',
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },

        columns:[
            {data:"code",name:"code",className:"font-bold"},
            {data:"name",name:"name",className:""},
            {data:"asset_group.name",name:"asset_group.name",className:""},
            {data:"method",name:"method",className:"text-center"},
            {data:"status",name:"status",className:"text-center"},
            {data:"action",name:"id",className:"text-center"},
        ],

        createdRow: function(row, data, dataIndex) {
          if($rootScope.roleList.includes('finance.asset.list_asset')) {
            $(row).find('td').attr('ui-sref', 'finance.daftar_asset.show({id:' + data.id + '})')
            $(row).find('td:last-child').removeAttr('ui-sref')
          } else {
            $(oTable.table().node()).removeClass('table-hover')
          }
            $compile(angular.element(row).contents())($scope);
        }
    });

    $scope.searchData = function() {
        oTable.ajax.reload();
    }

    $scope.resetFilter = function() {
        $scope.formData = {};
        oTable.ajax.reload();
    }

    $scope.method=[
        {id:1,name:'Garis Lurus'}
    ]

    $scope.asset_type=[
        {id:1,name:'Aset Berwujud'},
        {id:2,name:'Aset Tidak Berwujud'},
    ]
});

app.controller('DaftarAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Asset";
    $scope.item = {};
    $('.ibox-content').addClass('sk-loading');

    $scope.method = [
        {id:1, name:'Garis Lurus'}
    ]

    $scope.status = [
        {id:1, name:'Draft'},
        {id:2, name:'Available'}
    ]

    $http.get(baseUrl+'/finance/asset/'+$stateParams.id)
    .then(function(data) {
        $scope.item=data.data.item;
        //$scope.detail=data.data.detail;
        $('.ibox-content').removeClass('sk-loading');
    });

    // Memunculkan modal depresiasi aset
    $scope.depreciate = function() {
        $scope.depresiasiData = {};
        $scope.depresiasiData.nominal = $scope.item.beban_bulan;
        $('#modalDepresiasi').modal('show');
    }

    $scope.submitDepresiasi=function() {
        $scope.disBtn=true;
        $http.post(
            baseUrl+'/finance/asset/depreciate/'+$stateParams.id,
            $scope.depresiasiData)
        .then(
            function(data) {
                $('#modalDepresiasi').modal('hide');
                $timeout(function() {
                    $state.reload();
                },1000)

                $scope.disBtn=false;

                toastr.success("Data Berhasil Disimpan!");

            },
            function(error) {
                $scope.disBtn = false;
                if (error.status == 422) {
                    var det="";
                    angular.forEach(error.data.errors,function(val,i) {
                        det+="- "+val+"<br>";
                    });
                    toastr.warning(det,error.data.message);
                } else {
                    toastr.error(error.data.message,"Error Has Found !");
                }
            }
        );
    }
});


app.controller(
  'DepresiasiAsset',
  function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Depresiasi Asset";
    $('.ibox-content').addClass('sk-loading');
    /*
    $http.get(
      baseUrl+'/api/finance/asset_depreciation_datatable',
      { headers : {'Authorization' : 'Bearer '+authUser.api_token} }
    );
    */
    $scope.filterData={}
    $scope.filter=function() {
      oTable.ajax.reload()
    }

    $scope.reset_filter=function() {
      $scope.filterData={}
      oTable.ajax.reload()
    }

    $http.get(baseUrl+'/finance/asset_depreciation').then(function(data) {
      $scope.data=data.data;
    })

    oTable = $('#datatable').DataTable({
      processing: true,
      lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
      serverSide: true,
      order:[[4,'desc']],
      ajax: {
        headers : {'Authorization' : 'Bearer '+authUser.api_token},
        url : baseUrl+'/api/finance/asset_depreciation_datatable',
        data: function(d) {
          d.asset_id=$scope.filterData.asset_id
          d.start_date=$scope.filterData.start_date
          d.end_date=$scope.filterData.end_date
        },
        dataSrc: function(d) {
            $('.ibox-content').removeClass('sk-loading');
            return d.data;
        }
      },

      columns:[
        {data:"asset_code",name:"assets.code",className:"font-bold"},
        {data:"asset_name",name:"assets.name",className:"text-center"},
        {data:"date_utility",name:"date_utility",className:"text-center"},
        {data:"depreciation_cost",name:"depreciation_cost",className:""},
        {data:"action",name:"created_at",className:"text-center"},
      ],

      createdRow: function(row, data, dataIndex) {
        if($rootScope.roleList.includes('finance.asset.depreciation.detail')) {
          $(row).find('td').attr('ui-sref', 'finance.depresiasi_asset.show({id:' + data.id + '})')
          $(row).find('td:last-child').removeAttr('ui-sref')
        } else {
          $(oTable.table().node()).removeClass('table-hover')
        }
          $compile(angular.element(row).contents())($scope);
      }
    });
  }
);

app.controller('DepresiasiAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Depresiasi";
  $scope.item = {};
  $('.ibox-content').addClass('sk-loading');

    $scope.method = [
        {id:1, name:'Garis Lurus'}
    ]

    $scope.status = [
        {id:1, name:'Draft'},
        {id:2, name:'Available'}
    ]

    $http.get(baseUrl+'/finance/asset_depreciation/'+$stateParams.id)
    .then(function(data) {
      $scope.item=data.data.item;
      $('.ibox-content').removeClass('sk-loading');
    });
});

app.controller('PengafkiranAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Penghapusan Asset";
    $('.ibox-content').addClass('sk-loading');

    oTable = $('#datatable').DataTable({
        processing: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        serverSide: true,
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/finance/asset_afkir_datatable',
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },

        columns:[
            {data:"code",name:"code",className:"font-bold"},
            {data:"asset",name:"assets.name",className:""},
            {data:"date_transaction",name:"date_transaction",className:""},
            {data:"company",name:"companies.name",className:""},
            // {data:"date_needed",name:"date_needed",className:""},
            {data:"loss_amount",name:"loss_amount",className:"text-right"},
            {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
          if($rootScope.roleList.includes('finance.asset.rejected.detail')) {
            $(row).find('td').attr('ui-sref', 'finance.pengafkiran_asset.show({id:' + data.id + '})')
            $(row).find('td:last-child').removeAttr('ui-sref')
          } else {
            $(oTable.table().node()).removeClass('table-hover')
          }
            $compile(angular.element(row).contents())($scope);
        }
    });

});

app.controller('PengafkiranAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Penghapusan Asset";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/finance/asset_afkir/'+$stateParams.id).then(function(data) {
    $scope.item=data.data.item;
    $('.ibox-content').removeClass('sk-loading');
  });

  $scope.approve=function(id) {
    var cofs=confirm("apakah anda yakin ?");
    if (!cofs) {
      return null;
    }
    $http.post(baseUrl+'/finance/asset_afkir/approve/'+$stateParams.id).then(function(data) {
      $state.reload()
    });
  }

  $scope.status=[
    {id:1,name:'<span class="label label-warning">Pengajuan</span>'},
    {id:2,name:'<span class="label label-success">Disetujui</span>'}
  ]

});

app.controller('PengafkiranAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    if($stateParams.id) {
        $rootScope.pageTitle = "Edit Penghapusan Asset";
    } else {
        $rootScope.pageTitle = "Tambah Penghapusan Asset";
    }
    $('.ibox-content').addClass('sk-loading');
    $scope.ieEdit = false;
    $scope.formData={};
    $scope.formData.company_id=compId;
    $scope.formData.date_transaction=dateNow;
    $scope.formData.akumulasi_depresiasi=0;
    $scope.formData.loss_amount=0;

    $http.get(baseUrl+'/finance/asset_afkir/create').then(function(data) {
        $scope.data=data.data;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.changeAsset=function(id, fn = null) {
      $http.get(baseUrl+'/finance/asset/find/'+id
        ,{ params: { tanggal: $scope.formData.date_transaction }})
        .then(function(data) {
        $scope.formData.akumulasi_depresiasi = data.data.beban_akumulasi;
        $scope.formData.loss_amount = data.data.nilai_buku;
        if(fn) {
            fn()
        }
      })
    }

    $scope.show = function() {
        if($stateParams.id) {
            $http.get(baseUrl+'/finance/asset_afkir/' + $stateParams.id, $scope.formData).then(function(data) {
                $scope.formData = data.data.item
                $scope.formData.date_transaction = $filter('minDate')($scope.formData.date_transaction)
                $scope.origin_loss_amount = $scope.formData.loss_amount

                $scope.changeAsset($scope.formData.asset_id, function(){
                    $scope.formData.loss_amount = $scope.origin_loss_amount
                })
            }, function(error) {
                
            });
        }
    }
    $scope.show()


    $scope.disBtn=false;
    $scope.submitForm=function() {
        var method = 'post'
        var url = baseUrl+'/finance/asset_afkir'
        if($stateParams.id) {
            method = 'put'
            url = baseUrl+'/finance/asset_afkir/' + $stateParams.id
        }
        $scope.disBtn=true;
        $http[method](url, $scope.formData).then(function(data) {
            $state.go('finance.pengafkiran_asset');
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

app.controller('PengafkiranAssetEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle = "Edit Penghapusan Asset";
  $('.ibox-content').addClass('sk-loading');
  $scope.formData = {};
  $scope.isEdit = true;

  $http.get(baseUrl+'/finance/asset_afkir/'+$stateParams.id+'/edit').then(function(data) {
    $scope.data = data.data;
    $scope.formData = data.data.item;
    $scope.formData.date_transaction = $filter('date')(data.data.item.date_transaction, 'dd-MM-yyyy');
    $http.get(baseUrl+'/finance/asset/find/'+ $stateParams.id
      ,{ params: { tanggal: data.data.item.date_transaction }})
      .then(function(data) {
        $scope.formData.akumulasi_depresiasi = data.data.beban_akumulasi;
        // $scope.formData.loss_amount = data.data.nilai_buku;
        $('.ibox-content').removeClass('sk-loading');
      });
  });

  $scope.disBtn=false;
  $scope.submitForm=function() {
    $scope.disBtn = true;
    postUrl = baseUrl +'/finance/asset_afkir/'+ $stateParams.id;
    $http.put(postUrl, $scope.formData).then(function(data) {
      $state.go('finance.pengafkiran_asset');
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

app.controller('PenjualanAsset', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile, $filter) {
    $rootScope.pageTitle="Penjualan Asset";
    $('.ibox-content').addClass('sk-loading');

    oTable = $('#datatable').DataTable({
        processing: true,
        lengthMenu:[[10,25,50,100,-1],[10,25,50,100,'All']],
        serverSide: true,
        ajax: {
            headers : {'Authorization' : 'Bearer '+authUser.api_token},
            url : baseUrl+'/api/finance/asset_sales_datatable',
            dataSrc: function(d) {
                $('.ibox-content').removeClass('sk-loading');
                return d.data;
            }
        },

        columns:[
            {data:"code",name:"code",className:"font-bold"},
            {data:"company.name",name:"company.name",className:""},
            {
              data:null,
              orderable:false,
              searchable:false,
              render:resp => $filter('fullDate')(resp.date_transaction)
            },
            {data:"costumer.name",name:"costumer.name",className:""},
            {data:"total_price",name:"total_price",className:"text-right"},
            {data:"status",name:"status",className:"text-right"},
            {data:"description",name:"description",className:"text-right"},
            {data:"action",name:"created_at",className:"text-center"},
        ],
        createdRow: function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        }
    });
});

app.controller('PenjualanAssetCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Penjualan Asset";
    $('.ibox-content').addClass('sk-loading');
    $scope.isEdit = false;

    $scope.formData={};
    $scope.formData.detail=[];
    $scope.detail={};
    $scope.formData.company_id=compId;
    $scope.formData.type_bayar=1;
    $scope.formData.termin=1;
    $scope.formData.date_transaction=dateNow;
    $scope.detail.nb=0;
    $scope.detail.price=0;

    $scope.termin = [
        {id:1,name:"Cash"},
        {id:2,name:"Kredit"},
    ];

    $http.get(baseUrl+'/finance/asset_sales/create')
    .then(function(data) {
        $scope.data=data.data;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.changeAsset=function(asset) {
        $http.get(baseUrl+'/finance/asset/find/'+asset.id)
        .then(function(data) {
            $scope.detail.nilai_buku = data.data.nilai_buku;
            $scope.detail.asset_id = asset.id;
            $scope.detail.asset_name = asset.name;
        })
    }

    $scope.cancelEdit = function() {
        $scope.detail = {}
    }

    $scope.updateTable = function() {
        if($scope.detail.id) {
            var index = $scope.formData.detail.findIndex(x => x.id = $scope.detail.id)
            if(index > -1) {
                $scope.formData.detail[index] = detail
                $scope.detail = {}
            }
        }
    }

    $scope.appendTable = function() {
        $scope.formData.detail.push($scope.detail);
        $scope.detail = {};
        $scope.detail.nilai_buku = 0;
        $scope.detail.price = 0;
        $scope.hitungTotalBayar();
    }

    $scope.hitungTotalBayar = function() {
        var total=0;
        angular.forEach($scope.formData.detail,function(val,i) {
            total += parseFloat(val.price);
        });
        $scope.formData.total_price = total;
    }

    $scope.deleteAppend = function(ids) {
        $scope.formData.detail.splice(ids, 1);
        $scope.hitungTotalBayar();
    }

    $scope.disBtn=false;
    $scope.submitForm = function() {
      $scope.disBtn=true;
      $scope.send = $scope.formData;
      $http.post(baseUrl+'/finance/asset_sales',$scope.formData).then(function(data) {
        $state.go('finance.penjualan_asset');
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

app.controller('PenjualanAssetEdit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Penjualan Asset";
    $('.ibox-content').addClass('sk-loading');
    $scope.isEdit = true;

    $scope.formData = {};
    $scope.formData.detail = [];
    $scope.detail = {};
    $scope.formData.company_id = compId;
    $scope.formData.type_bayar = 1;
    $scope.formData.termin = 1;
    $scope.formData.date_transaction = dateNow;
    $scope.detail.nb = 0;
    $scope.detail.price = 0;

    $scope.termin = [
        {id:1,name:"Cash"},
        {id:2,name:"Kredit"},
    ];

    $http.get(baseUrl+'/finance/asset_sales/'+ $stateParams.id + "/edit")
      .then(function(data) {
        $scope.data = data.data;
        $scope.item = $scope.data.item;

        $scope.formData.company_id = $scope.item.company_id;
        $scope.formData.contact_id = $scope.item.costumer_id;
        $scope.formData.type_bayar = ($scope.item.termin > 1) ? 2 : 1;
        $scope.formData.cash_account_id = $scope.item.cash_account_id;
        $scope.formData.sales_account_id = $scope.item.sales_account_id;
        $scope.formData.termin = $scope.item.termin;
        $scope.formData.date_transaction = $filter('date')($scope.item.date_transaction, "dd-MM-yyyy");
        $scope.formData.description = $scope.item.description;

        angular.forEach($scope.data.details, function(detail, i) {
          var asset = $scope.data.asset.find(x => x.id == detail.asset_id);
          detail.asset_name = asset.name;
          $scope.formData.detail.push(detail);
          $scope.hitungTotalBayar();
        });

        $('.ibox-content').removeClass('sk-loading');
      }
    );

    $scope.changeAsset=function(asset) {
        $http.get(baseUrl+'/finance/asset/find/'+asset.id)
        .then(function(data) {
            $scope.detail.nilai_buku = data.data.nilai_buku;
            $scope.detail.asset_id = asset.id;
            $scope.detail.asset_name = asset.name;
        })
    }

    $scope.appendTable = function() {
        $scope.formData.detail.push($scope.detail);
        //alert(JSON.stringify($scope.formData.detail));
        $scope.detail = {};
        $scope.detail.nilai_buku = 0;
        $scope.detail.price = 0;
        $scope.hitungTotalBayar();
    }

    $scope.hitungTotalBayar = function() {
        var total=0;
        angular.forEach($scope.formData.detail,function(val,i) {
            total += parseFloat(val.price);
        });
        $scope.formData.total_price = total;
    }

    $scope.deleteAppend = function(ids) {
        if($scope.formData.detail.length > 1) {
          var detail = $scope.formData.detail[ids];

          if(detail.id)
            $http.delete(baseUrl +'/finance/asset_sales/delete_detail/'+ detail.id);

          $scope.formData.detail.splice(ids, 1);
          $scope.hitungTotalBayar();
        } else {
          alert("Detail penjualan aset minimal 1 item!! Tambahkan item lain terlebih dahulu untuk mengganti");
        }
    }

    $scope.disBtn=false;
    $scope.submitForm = function() {
      $scope.disBtn=true;
      $scope.send = $scope.formData;
      $http.put(baseUrl+'/finance/asset_sales/'+$stateParams.id,$scope.formData).then(function(data) {
        $state.go('finance.penjualan_asset');
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

app.controller('PenjualanAssetShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Detail Penjualan Asset";
    $('.ibox-content').addClass('sk-loading');

    $scope.termin = [
        {id:1, name:"Cash"},
        {id:2, name:"Kredit"}
    ]

    $http.get(baseUrl+'/finance/asset_sales/'+$stateParams.id).then(function(data) {
        $scope.item = data.data.item;
        $scope.details = data.data.details;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.approve=function(id) {
        let postUrl = baseUrl + '/finance/asset_sales/approve/' + $stateParams.id;
        if (!confirm("apakah anda yakin ?")) {
            return null;
        }
        $http.post(postUrl).then(function(data) {
            toastr.success("Data Berhasil Disimpan!");
            $state.reload()
        });
    }

    $scope.status=[
        {id:1,name:'Pengajuan'},
        {id:2,name:'Disetujui'}
    ]
});
