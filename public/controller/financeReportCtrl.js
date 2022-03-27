app.controller('financeReport', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Keuangan";
  $scope.baseUrl=baseUrl;

  $scope.exportAccount=function() {
    // console.log($scope);
    window.open($scope.baseUrl+'/finance/report/export/account','_blank');
    // $http.get($scope.baseUrl+'/finance/report/export/account').then(function() {
    //   toastr.success('File Downloaded!');
    // })
  }
  $scope.exportUnpaidCost=function() {
    window.open($scope.baseUrl+'/pdf/unpaid_cost','_blank');
  }
  $scope.exportCostBalance=function() {
    window.open($scope.baseUrl+'/pdf/cost_balance','_blank');
  }
});

app.controller('financeReportJournal', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Jurnal Umum";
  $scope.baseUrl=baseUrl;

  $scope.status=[
    {id: 1, name:'Draft'},
    {id: 2, name:'Disetujui'},
    {id: 3, name:'Posting'},
  ];
  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/journal').then(function(data) {
    $scope.data=data.data;
  });

  $scope.exportJournal=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/journal', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
    // window.location.href=baseUrl+'/finance/report/export/journal?company_id='+$scope.formData.company_id+'&start_date='+$scope.formData.start_date+'&end_date='+$scope.formData.end_date+'&status='+$scope.formData.status+'&type_transaction_id='+$scope.formData.type_transaction_id;
  }
});
app.controller('financeReportLedger', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$window) {
  $rootScope.pageTitle="Laporan Buku Besar";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ledger').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/ledger'+query,'_blank');
  }
});
app.controller('financeReportLedgerReceivable', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Buku Besar Piutang";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ledger_receivable').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/ledger_receivable'+query,'_blank');
  }
});
app.controller('financeReportLedgerPayable', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Buku Besar Hutang";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ledger_payable').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/ledger_payable'+query,'_blank');
  }
});
app.controller('financeReportLedgerUmSupplier', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Buku Besar Uang Muka Supplier";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ledger_um_supplier').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/ledger_um_supplier'+query,'_blank');
  }
});
app.controller('financeReportLedgerUmCustomer', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Buku Besar Uang Muka Customer";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ledger_um_customer').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/ledger_um_customer'+query,'_blank');
  }
});
app.controller('financeReportNeracaSaldo', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Neraca Saldo";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/neraca_saldo').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/neraca_saldo'+query,'_blank');
  }
});
app.controller('financeReportNeracaLajur', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Neraca Lajur";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/posisi_keuangan').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/neraca_lajur', params: $scope.formData, responseType: 'arraybuffer'})
    .then(function successCallback(data, status, headers, config) {

      headers = data.headers()
      var filename = headers['content-disposition'].split('"')[1];
      var contentType = headers['content-type'];
      var linkElement = document.createElement('a');
      try {
        var blob = new Blob([data.data], { type: contentType });
        var url = window.URL.createObjectURL(blob);
        linkElement.setAttribute('href', url);
        linkElement.setAttribute("download", filename);
        var clickEvent = new MouseEvent("click", {
          "view": window,
          "bubbles": true,
          "cancelable": false
        });
        linkElement.dispatchEvent(clickEvent);
      } catch (e) {

      }
    },
    function errorCallback(data, status, headers, config) {

      toastr.error(data.statusText);
    });
  }

});
app.controller('financeReportNeracaSaldoBanding', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Neraca Saldo Perbandingan";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/neraca_saldo').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/neraca_saldo_banding', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
    // window.location.href=baseUrl+'/finance/report/export/journal?company_id='+$scope.formData.company_id+'&start_date='+$scope.formData.start_date+'&end_date='+$scope.formData.end_date+'&status='+$scope.formData.status+'&type_transaction_id='+$scope.formData.type_transaction_id;
  }
});
app.controller('financeReportLabaRugi', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Laba Rugi";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/laba_rugi').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/laba_rugi'+query,'_blank');
  }
});
app.controller('financeReportEkuitas', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Ekuitas";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ekuitas').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/ekuitas', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
  }
});
app.controller('financeReportEkuitasBanding', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Ekuitas Perbandingan";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/ekuitas').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/ekuitas_banding', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
  }
});
app.controller('financeReportPosisiKeuangan', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Posisi Keuangan";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/posisi_keuangan').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/posisi_keuangan', params: $scope.formData, responseType: 'arraybuffer'})
    .then(function successCallback(data, status, headers, config) {

      headers = data.headers()
      var filename = headers['content-disposition'].split('"')[1];
      var contentType = headers['content-type'];
      var linkElement = document.createElement('a');
      try {
        var blob = new Blob([data.data], { type: contentType });
        var url = window.URL.createObjectURL(blob);
        linkElement.setAttribute('href', url);
        linkElement.setAttribute("download", filename);
        var clickEvent = new MouseEvent("click", {
          "view": window,
          "bubbles": true,
          "cancelable": false
        });
        linkElement.dispatchEvent(clickEvent);
      } catch (e) {

      }
    },
    function errorCallback(data, status, headers, config) {

      toastr.error(data.statusText);
    });
  }

});
app.controller('financeReportPosisiKeuanganBanding', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Posisi Keuangan Perbandingan";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/posisi_keuangan').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/posisi_keuangan_perbandingan', params: $scope.formData, responseType: 'arraybuffer'})
    .then(function successCallback(data, status, headers, config) {

      headers = data.headers()
      var filename = headers['content-disposition'].split('"')[1];
      var contentType = headers['content-type'];
      var linkElement = document.createElement('a');
      try {
        var blob = new Blob([data.data], { type: contentType });
        var url = window.URL.createObjectURL(blob);
        linkElement.setAttribute('href', url);
        linkElement.setAttribute("download", filename);
        var clickEvent = new MouseEvent("click", {
          "view": window,
          "bubbles": true,
          "cancelable": false
        });
        linkElement.dispatchEvent(clickEvent);
      } catch (e) {

      }
    },
    function errorCallback(data, status, headers, config) {

      toastr.error(data.statusText);
    });
  }

});
app.controller('financeReportOutstandingDebt', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Outstanding Piutang";
  $scope.baseUrl=baseUrl;
  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/outstanding_debt').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/outstanding_debt'+query,'_blank');
  }
});
app.controller('financeReportOutstandingCredit', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Outstanding Hutang";
  $scope.baseUrl=baseUrl;
  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/outstanding_credit').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/outstanding_credit'+query,'_blank');
  }
});
app.controller('financeReportProfitComparison', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Laba Rugi Perbandingan";
  $scope.baseUrl=baseUrl;
  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/laba_rugi_perbandingan').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    /*
    $http({method: 'GET', url: baseUrl+'/finance/report/export/laba_rugi_perbandingan', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
    */
    $scope.formData._token = csrfToken;
    var query = $.param($scope.formData);

    if(query != "")
      query = '?' + query;

    window.open($scope.baseUrl+'/finance/report/export/laba_rugi_perbandingan'+query,'_blank');

    // window.location.href=baseUrl+'/finance/report/export/journal?company_id='+$scope.formData.company_id+'&start_date='+$scope.formData.start_date+'&end_date='+$scope.formData.end_date+'&status='+$scope.formData.status+'&type_transaction_id='+$scope.formData.type_transaction_id;
  }
});
app.controller('financeReportArusKas', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Arus Kas";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/arus_kas').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/arus_kas', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
    // window.location.href=baseUrl+'/finance/report/export/journal?company_id='+$scope.formData.company_id+'&start_date='+$scope.formData.start_date+'&end_date='+$scope.formData.end_date+'&status='+$scope.formData.status+'&type_transaction_id='+$scope.formData.type_transaction_id;
  }

});
app.controller('financeReportArusKasBanding', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Laporan Arus Kas Perbandingan";
  $scope.baseUrl=baseUrl;

  // $scope.formData={};
  $http.get(baseUrl+'/finance/report/arus_kas').then(function(data) {
    $scope.data=data.data;
  });

  $scope.export=function() {
    $http({method: 'GET', url: baseUrl+'/finance/report/export/arus_kas_perbandingan', params: $scope.formData, responseType: 'blob'})
    .then(function successCallback(response, status, headers, config) {
      var blob = response.data;
      var contentType = response.headers("content-type");
      var fileURL = URL.createObjectURL(blob);
      window.open(fileURL,'_blank');
    },
    function errorCallback(data, status, headers, config) {
      toastr.error(data.statusText,"Error!");
    });
    // window.location.href=baseUrl+'/finance/report/export/journal?company_id='+$scope.formData.company_id+'&start_date='+$scope.formData.start_date+'&end_date='+$scope.formData.end_date+'&status='+$scope.formData.status+'&type_transaction_id='+$scope.formData.type_transaction_id;
  }

});
