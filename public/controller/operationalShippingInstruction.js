app.controller('operationalShippingInstruction', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile,$filter) {
  $rootScope.pageTitle="Report Shipping Instruction";
  $('.ibox-content').addClass('sk-loading');

  $scope.formDatas={};
  $scope.formDatas.qty=1;
  $scope.formDatas.nw=1;
  $scope.formDatas.gw=1;

  $http.get(baseUrl+'/operational/report/index_shipment_instruction').then(function(data) {
    $scope.data=data.data;
    $('.ibox-content').removeClass('sk-loading');
  })

  $scope.submitForm=function() {
    $('.ibox-content').addClass('sk-loading');
    // console.log($scope.formDatas)
    $http({method: 'POST', url: baseUrl+'/operational/report/shipment_instruction', params: $scope.formDatas, responseType: 'arraybuffer'})
    .then(function successCallback(data) {
      
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
    function errorCallback(data) {
      
      toastr.error(data.statusText);
      $('.ibox-content').removeClass('sk-loading');
    });
  }
});
