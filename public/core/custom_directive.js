app.directive('datepick', function() {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, el, attr, ngModel) {
      // console.log(ngModel);
      var sDate = attr['datepickStartDate'];
      var eDate = attr['datepickEndDate'];
      if (sDate) {
        var startDate=sDate;
      } else {
        var startDate=null
      }
      if (eDate) {
        var endDate=eDate;
      } else {
        var endDate=null
      }
      $(el).datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true,
        todayHighlight: true,
        // startDate:startDate,
        // endDate:endDate,
        onSelect: function(dateText) {
          scope.$apply(function() {
            ngModel.$setViewValue(dateText);
          });
        },
      });
      ngModel.$render=function(evt) {
        $(el).datepicker('update', ngModel.$modelValue);
        $(el).datepicker('setStartDate', startDate);
        $(el).datepicker('setEndDate', endDate);
      }

      // console.log(ngModel);
      // $(el).datepicker('update', ngModel.$modelValue);
      // ngModel.$formatters.push(function(modelValue) {
      //   console.log(modelValue);
      //   $(el).datepicker('update', modelValue);
      // });
    }
  };
});

app.directive('monthpick', function() {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, el, attr, ngModel) {
      let minDate = attr['minDate'];
      if (! minDate) {
        minDate=null
      }

      $(el).datepicker({
        autoclose: true,
        minViewMode: 1,
        format: 'mm-yyyy',
      });
      ngModel.$render=function(evt) {
        $(el).datepicker('update', ngModel.$modelValue);
        $(el).datepicker("option", "minDate", minDate);
      }
    }
  };
});

app.directive('yearpick', function() {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, el, attr, ngModel) {

      $(el).datepicker({
        autoclose: true,
        minViewMode: 2,
        format: 'yyyy',
      });
      ngModel.$render=function(evt) {
        $(el).datepicker('update', ngModel.$modelValue);
      }
    }
  };
});

app.directive('clockpick', function() {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, el, attr, ngModel) {
      // console.log(ngModel);
      var attr = attr['clockpickPosition'];
      if (attr) {
        var aname=attr;
      } else {
        var aname='top'
      }
      $(el).clockpicker({
        placement:aname,
        autoclose:true,
        donetext:'DONE',
      });
    }
  };
});
app.directive('jnumber', function($filter, $browser) {
    return {
        require: 'ngModel',
        restrict: 'A',
        scope: {
          ngModel: '='
        },
        link: function(scope, $element, $attrs, ngModelCtrl) {
            var listener = function() {
                var value = $element.val().replace(/,/g, '')
                $element.val($filter('number')(value, false))
            }

            // This runs when we update the text field
            ngModelCtrl.$parsers.push(function(viewValue) {
                return viewValue.replace(/,/g, '');
            })

            // This runs when the model gets updated on the scope directly and keeps our view in sync
            ngModelCtrl.$render = function() {
                if (isNaN(ngModelCtrl.$viewValue)) {
                  $element.val(0)
                } else {
                  $element.val($filter('number')(ngModelCtrl.$viewValue, false))
                }
            }

            $element.bind('change', listener)
            $element.bind('keydown', function(event) {
                var key = event.keyCode
                // If the keys include the CTRL, SHIFT, ALT, or META keys, or the arrow keys, do nothing.
                // This lets us support copy and paste too
                if (key == 91 || (15 < key && key < 19) || (37 <= key && key <= 40))
                    return
                $browser.defer(listener) // Have to do this or changes don't get picked up properly
            })

            $element.bind('paste cut', function() {
                $browser.defer(listener)
            })
        }

    }
});
app.directive('jnumber2', function($filter,$browser) {
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, element, attr, ngModel) {
      var viewValue, noCommasVal;
      var wholeNosReg = /^(?=.{1,9}(\.|$))(?!0(?!\.))\d{1,3}(,\d{3})?$/;
      function testValue(value) {
        ngModel.$setValidity('pattern',wholeNosReg.test(value));
      }
      function setThousandSeperator(value) {
            var el = element[0]
            var start = el.selectionStart
        if (value) {
          noCommasVal = value.toString().replace(/,/g, '');
          viewValue = noCommasVal.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
          ngModel.$setViewValue(viewValue);
          ngModel.$render();
          var word = ''
          var plus = 0
          var length = viewValue.split(',').length
          if(length > 1) {
              for(x = 0;x < start;x++) {
                 if(x == start - 1) {
                    if(viewValue[x] == ',') {
                        break
                    }
                 }
                 word += viewValue[x]
              }
              lengthOver = word.split(',').length 
              if(lengthOver == 1) {
                  plus =  lengthOver - 1
              }

              if(start + lengthOver - 1 == viewValue.length) {
                  plus =  lengthOver - 1
              }
          }
        }
      }


      ngModel.$parsers.push(function(value) {
        if (!value) {
          ngModel.$setValidity('pattern',true);
        } else {
          testValue(value);
          setThousandSeperator(value);
          return noCommasVal;
        }
      });
      ngModel.$formatters.push(function(value) {
        if (!value) {
          ngModel.$setValidity('pattern',true);
          return value;
        } else {
          testValue(value);
          setThousandSeperator(value);
          return viewValue;
        }
      });
    }
  }
})
app.directive('inputThousandSeparator', [
      function() {
        return {
          restrict: 'A',
          require: 'ngModel',
          link: function(scope, element, attr, ngModel) {

            var viewValue, noCommasVal;
            var numberMode = attr['inputThousandSeparator'];

            var currencyReg = /^(?!0+\.00)(?=.{1,9}(\.|$))(?!0(?!\.))\d{1,3}(,\d{3})*(\.[0-9]{2})?$/;
            var percentageReg = /(^100([.]0{1,2})?)$|(^\d{1,2}([.]\d{1,2})?)$/;
            var wholeNosReg = /^(?=.{1,9}(\.|$))(?!0(?!\.))\d{1,3}(,\d{3})?$/;

            function testValue(value) {
              switch(numberMode) {
                case 'currency':
                  ngModel.$setValidity('pattern',currencyReg.test(value));
                  break;

                case 'percentage':
                  ngModel.$setValidity('pattern',percentageReg.test(value));
                  break;

                case 'whole':
                  ngModel.$setValidity('pattern',wholeNosReg.test(value));
                  break;
              }
            }

            function setThousandSeperator(value) {
              if (value) {
                noCommasVal = value.toString().replace(/,/g, '');
                viewValue = noCommasVal.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                ngModel.$setViewValue(viewValue);
                ngModel.$render();
              }
            }

            ngModel.$parsers.push(function(value) {
              if (!value) {
                ngModel.$setValidity('pattern',true);
              } else {
                testValue(value);
                setThousandSeperator(value);
                return noCommasVal;
              }
            });
            ngModel.$formatters.push(function(value) {
              if (!value) {
                ngModel.$setValidity('pattern',true);
                return value;
              } else {
                testValue(value);
                setThousandSeperator(value);
                return viewValue;
              }
            });
          }
        };
      }
    ]);
app.directive('onlyNum', function($browser) {
      return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, element, attrs, modelCtrl) {
            var keyCode = [8,9,37,39,48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105,110,190];
            element.bind("keydown", function(event) {
                if (modelCtrl.$modelValue) {
                  // var hitungTitik=(modelCtrl.$modelValue.match(/./g)||[]).length;
                  var modelVal=modelCtrl.$modelValue;
                  var hitungTitik=modelVal.split('.').length-1;
                  var slength=modelVal.length;
                  // console.log(slength);
                  if (hitungTitik>0) {
                    keyCode=[8,9,37,39,48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105,110];
                  } else {
                    keyCode=[8,9,37,39,48,49,50,51,52,53,54,55,56,57,96,97,98,99,100,101,102,103,104,105,110,190];
                  }
                }
                if($.inArray(event.which,keyCode) == -1) {
                    scope.$apply(function(){
                        scope.$eval(attrs.onlyNum);
                        event.preventDefault();
                    });
                    event.preventDefault();
                }
            });
        }
      }
  });
app.directive('ngConfirmClick', [
    function(){
        return {
            link: function (scope, element, attr) {
                var msg = attr.ngConfirmClick || "Are you sure?";
                var clickAction = attr.confirmedClick;
                element.on('click',function (event) {
                    if ( window.confirm(msg) ) {
                        scope.$eval(clickAction)
                    }
                });
            }
        };
}])
