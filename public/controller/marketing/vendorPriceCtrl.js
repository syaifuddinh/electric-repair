app.controller('marketingVendorPrice', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle = $rootScope.solog.label.general.vendor_price;
});

app.controller('marketingVendorPriceCreate', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
    $rootScope.pageTitle="Add";
    $scope.id = $stateParams.id
});

app.controller('marketingVendorPriceShow', function($scope, $http, $rootScope,$state,$stateParams,$timeout,$compile) {
  $rootScope.pageTitle="Detail Harga Vendor";
  $('.ibox-content').addClass('sk-loading');

  $http.get(baseUrl+'/marketing/vendor_price/'+$stateParams.id).then(function(data) {
    $scope.item=data.data;
    
    $('.ibox-content').removeClass('sk-loading');
  });
});