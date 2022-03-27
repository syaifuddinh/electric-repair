app.controller('operationalReport', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Report Operasional";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData={}
    $scope.formData.report_id=11
    
    $scope.reports=[
    // {id:1,name:"Rekap Pengiriman"},
    // {id:2,name:"Pengiriman"},
    // {id:3,name:"Rekap Kepabeanan"},
    // {id:4,name:"Kepabeanan"},
    // {id:5,name:"Rekap Jasa Lainnya"},
    // {id:6,name:"Jasa Lainnya"},
    // {id:7,name:"Rekap Sewa Gudang"},
    // {id:8,name:"Sewa Gudang"},
    // {id:9,name:"Rekap Transport"},
    // {id:10,name:"Transport"},
    {id:11,name:"Operasional FF"},
    {id:12,name:"Laporan KPI Invoice"},
    {id:13,name:"Laporan KPI - Validasi Invoice"},
    ];

    $scope.resetFilter = function() {
        $scope.formData = {};
        $scope.isPreview = false;
    }

    $scope.showPreview = function() {
        $scope.isPreview = false;
        var esc=encodeURIComponent;
        var params=Object.keys($scope.formData).map(function(k) {
            if (!$scope.formData[k]) {
                return esc(k)+'=';
            }
            return esc(k) + '=' + esc($scope.formData[k]);
        }).join('&')

        $http.get(baseUrl+'/operational/report/preview?' + params).then(function(resp) {
            var outp = resp.data;
            $('#preview_box').html($compile(outp)($scope));
            $scope.isPreview = true;
        });
    }

    $scope.detailCost=function(joc_list) {
        $http.get(baseUrl+'/operational/report/show_cost', {params:{'list':joc_list}}).then(function(data){
            var st=data.data;
            var htm="";
            var tot=0
            angular.forEach(st, function(val,i) {
                tot+=val.total_price;
                var desc=val.description || '-';
                htm+="<tr>"
                htm+="<td>"+val.name+"</td>"
                htm+="<td>"+val.vendor+"</td>"
                htm+="<td class='text-right'>"+$filter('number')(val.price)+"</td>"
                htm+="<td class='text-right'>"+$filter('number')(val.qty)+"</td>"
                htm+="<td class='text-right'>"+$filter('number')(val.total_price)+"</td>"
                htm+="<td>"+desc+"</td>"
                htm+="</tr>"
            })
            htm+="<tr>"
            htm+="<td colspan='4'>TOTAL</td>"
            htm+="<td class='text-right'>"+$filter('number')(tot)+"</td>"
            htm+="<td></td>"
            htm+="</tr>"
            $('#costListTable tbody').html($compile(htm)($scope))
            $('#costListModal').modal()
        },function(err) {
            toastr.error("Biaya Tidak Ditemukan !");
        })
    }

    $scope.changeReport=function(fr) {
        $scope.formData={}
        $scope.formData.report_id=fr.report_id
    }

    $http.get(baseUrl+'/operational/report').then(function(data) {
        $scope.data=data.data;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.export=function() {
        $('.ibox-content').addClass('sk-loading');
        $http({method: 'POST', url: baseUrl+'/operational/report/export', params: $scope.formData, responseType: 'arraybuffer'})
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
            $('.ibox-content').removeClass('sk-loading');
        },
        function errorCallback(data, status, headers, config) {
            
            toastr.error(data.statusText);
            $('.ibox-content').removeClass('sk-loading');
        });
    }


    $scope.export_pdf=function() {
        $('.ibox-content').addClass('sk-loading');
        $http({method: 'POST', url: baseUrl+'/operational/report/export_pdf', params: $scope.formData, responseType: 'arraybuffer'})
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
            $('.ibox-content').removeClass('sk-loading');
        },
        function errorCallback(data, status, headers, config) {
            toastr.error(data.statusText);
            $('.ibox-content').removeClass('sk-loading');
        });
    }


    });
