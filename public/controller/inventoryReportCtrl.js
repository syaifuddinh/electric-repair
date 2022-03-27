app.controller('inventoryReport', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
    $rootScope.pageTitle="Report Inventory";
    $('.ibox-content').addClass('sk-loading');
    $scope.formData = {}
    $scope.formData.report_id = 1
  
    $scope.reports = [
        {id:1, name: "Laporan Penggunaan Sparepart"}
    ];

    $http.get(baseUrl+'/inventory/report').then(function(data) {
        $scope.data=data.data;
        $scope.data.active_vehicle = data.data.vehicle;
        $('.ibox-content').removeClass('sk-loading');
    });

    $scope.change_cabang = function() {
        if($scope.formData.company_id) {
            $scope.data.active_vehicle = $scope.data.vehicle.filter(function(item){
                return item.company_id == $scope.formData.company_id;
            });
        } else {
            $scope.data.active_vehicle = $scope.data.vehicle;
        }
    }
  
    $scope.resetFilter = function() {
        $scope.formData = {};
        $scope.isPreview = false;
    }
  
    $scope.showPreview = function() {
        $scope.isPreview = false;
        var esc = encodeURIComponent;
        var params = Object.keys($scope.formData).map(function(k) {
            if (!$scope.formData[k])
                return esc(k)+'=';

            return esc(k) + '=' + esc($scope.formData[k]);
        }).join('&')
    
        $http.get(baseUrl+'/inventory/report/preview?' + params).then(function(resp) {
            var outp = resp.data;
            $('#preview_box').html($compile(outp)($scope));
            $scope.isPreview = true;
        });
    }
  
    $scope.changeReport=function(fr) {
        $scope.formData = {}
        $scope.formData.report_id = fr.report_id
    }
  
    $scope.export=function() {
        $('.ibox-content').addClass('sk-loading');
        $http({method: 'POST', url: baseUrl+'/inventory/report/export', params: $scope.formData, responseType: 'arraybuffer'})
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
                toastr.error(e);
            }
            $('.ibox-content').removeClass('sk-loading');
        },
        function errorCallback(data, status, headers, config) {
            
            toastr.error(data.statusText);
            $('.ibox-content').removeClass('sk-loading');
        });
    }
});
  