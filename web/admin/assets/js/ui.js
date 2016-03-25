var hideMetadata = function() {
  $('.permanent,.temporary,.shareable').hide();
};
var selectMethod = function(_method) {
  hideMetadata();
  if (typeof(_method) === 'undefined') {
    _method = 'temporary';
  }
  $('#selectedMethod').html(_method);
  $('.' + _method).show();
};

var init = (function() {
  hideMetadata();
})();

var makeAlias = function(charCount) {
  var text = '';
  var possible = '-_ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

  for (var i = 0; i < charCount; i++) {
    text += possible.charAt(Math.floor(Math.random() * possible.length));
  }

  return text;
};

var aliasExists = function(alias) {
  result = false;
  if (typeof(alias) === 'undefined') {
    result = true;
  } else {
    $.ajax({
      'url': '/admin/?alias=' + alias,
      'async': false,
      'success': function(data) {
        if (data.alias == alias) {
          result = true;
        }
      }
    });
  }
  return result;
};

var validateDetail = function(data, isNew) {
  var result = true;
  var _uidLong = 5;

  if (result && typeof(data.destination) === 'undefined') {
    result = false;
  }
  if (result && typeof(data.alias) === 'undefined') {
    data.alias = makeAlias(5);
  }
  if (result && typeof(data.method) === 'undefined') {
    result = false;
  }
  if (result && typeof(data.expiration) !== 'undefined' && data.expiration < 15) {
    result = false;
  }

  var _uidLongIncrement = 10;
  while (isNew && aliasExists(data.alias)) {
    if (_uidLongIncrement-- < 0) {
      _uidLong++;
      _uidLongIncrement = 100;
    }
    data.alias = makeAlias(_uidLong);
  }
  return result;
};

var security = function($scope, $rootScope, $notification) {
  $scope.security = {};
  $.ajax({
    'url': '/admin/?settings=sec',
    'success': function(data) {
      $scope.$apply(function() {
        $scope.security.username = data.username;
      });
    }
  });
  $scope.save = function() {
    if ($scope.security.password === $scope.security.pwdConfirm) {
      delete $scope.security.pwdConfirm;
      $.ajax({
        'url': '/admin/?settings=sec',
        'method': 'PUT',
        'data': JSON.stringify($scope.security),
        'success': function() {
          $notification('Configuration saved', {
                                              delay: 10000
                                          });
        }
      });
    }
  };
};

var settings = function($scope, $rootScope, $notification) {
  $scope.settings = {};
  $scope.settings.brand = 'Url Simplifier';
  $scope.settings.defaultUrl = '/';
  $scope.settings.testDomain = '';
  $.ajax({
    'url': '/admin/?settings=global',
    'success': function(data) {
      $scope.$apply(function() {
        $scope.settings = $.extend({}, $scope.settings, data);
        $rootScope.$broadcast('testdomain_updated', $scope.settings.testDomain);
      });
    }
  });
  $scope.save = function() {
    $.ajax({
      'url': '/admin/?settings=global',
      'method': 'PUT',
      'data': JSON.stringify($scope.settings),
      'success': function() {
        $notification('Settings saved', {
                                            delay: 10000
                                        });
      }
    });
  };
};

var detail = function($scope, $rootScope, $notification) {
  $scope.new = true;
  $scope.data = {
    'method': 'temporary',
    'username': '-',
    'password': '-'
  };
  setTimeout(function() {
    $scope.$applyAsync(function() {
      $scope.data = {
        'method': 'temporary',
        'username': '',
        'password': ''
      };
    });
  });
  selectMethod('temporary');
  $scope.save = function() {
    if (validateDetail($scope.data, $scope.new)) {
      $scope.data.active = true;
      $.ajax({
        'url': '/admin/?alias=' + $scope.data.alias,
        'method': 'PUT',
        'data': JSON.stringify($scope.data),
        'success': function() {
          $scope.$apply(function() {
            $scope.new = true;
            $scope.data = {};
            selectMethod('temporary');
            $rootScope.$broadcast('reloadlist');
            $notification('Redirect saved', {
                                                delay: 10000
                                            });
          });
        }
      });
    }
  };
  $scope.setMethod = function(_method) {
    $scope.data.method = _method;
    selectMethod(_method);
  };
  $scope.$on('loaddetail', function(response, alias) {
    $.ajax({
      'url': '/admin/?alias=' + alias,
      'success': function(data) {
        $scope.$apply(function() {
          $scope.new = false;
          $scope.data = data;
          selectMethod($scope.data.method);
        });
      }
    });
  });
  $scope.$on('deletedetail', function(response, alias) {
    $.ajax({
      'url': '/admin/?alias=' + alias,
      'method': 'DELETE',
      'success': function(data) {
        $scope.$apply(function() {
          $rootScope.$broadcast('reloadlist');
          $notification('Redirect ' + data, {
                                              delay: 10000
                                          });
        });
      }
    });
  });
};

var list = function($scope, $rootScope, $notification) {
  $scope.redirects = [];

  $scope.updateList = function() {
    $.ajax({
      'url': '/admin/?alias=*',
      'success': function(redirects) {
        $scope.$apply(function() {
          $scope.redirects = redirects;
        });
      }
    });
  };

  $scope.updateList();
  $scope.$on('reloadlist', $scope.updateList);
  $scope.$on('testdomain_updated', function(response, testDomain) {
    $scope.$applyAsync(function() {
      $scope.testDomain = testDomain;
    });
  });

  $scope.edit = function($event) {
    $rootScope.$broadcast('loaddetail', $($event.target).data('alias'));
  };
  $scope.delete = function($event) {
    $rootScope.$broadcast('deletedetail', $($event.target).data('alias'));
  };
};

angular.module('urlshorter', ['notification'])
    .controller('security', security)
    .controller('settings', settings)
    .controller('detail', detail)
    .controller('list', list);
