var hideMetadata = function() {
  $('.permanent,.temporary,.shareable').hide();
};
var selectMethod = function(_method, label) {
  hideMetadata();
  if (typeof(_method) === 'undefined') {
    _method = 'temporary';
  }
  $('#selectedMethod').html(label);
  $('.' + _method).show();
};

var init = (function() {
  hideMetadata();
})();

var makeAlias = function(charCount) {
  var text = '';
  var possible =
    '-_ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

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
  if (result && typeof(data.expiration) !== 'undefined' &&
      data.expiration < 15) {
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

var security = function($scope, $rootScope, $notification, $translate) {
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
      if (typeof($scope.security.username) === 'undefined') {
        $scope.security.username = '';
      }
      if (typeof($scope.security.password) === 'undefined') {
        $scope.security.password = '';
      }
      delete $scope.security.pwdConfirm;
      $.ajax({
        'url': '/admin/?settings=sec',
        'method': 'PUT',
        'data': JSON.stringify($scope.security),
        'success': function() {
          $translate('msg_saved_cfg').then(function(translation) {
            $notification(translation, {
                                          'delay': 10000
                                      });
          });
        }
      });
    }
  };
};

var settings = function($scope, $rootScope, $notification, $translate,
    $localStorage) {
  $scope.settings = {};
  $scope.settings.brand = 'Url Simplifier';
  $scope.settings.defaultUrl = location.protocol + '//' + location.host + '/';
  $scope.settings.testDomain = '';
  $scope.$storage = $localStorage.$default({
    'lang': 'en'
  });
  $scope.lang = $scope.$storage.lang;
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
        $translate('msg_saved_settings').then(function(translation) {
          $notification(translation, {
                                        'delay': 10000
                                    });
        });
      }
    });
  };
  $scope.changeLang = function() {
    $scope.$storage.lang = $scope.lang;
    $translate.use($scope.lang);
  };
};

var detail = function($scope, $rootScope, $notification, $translate) {
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
  $translate('val_method_temporary').then(function(translatation) {
    selectMethod('temporary', translatation);
  });
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
            $translate('val_method_temporary').then(function(translatation) {
              selectMethod('temporary', translatation);
            });
            $rootScope.$broadcast('reloadlist');
            $translate('msg_saved_redirect').then(function(translation) {
              $notification(translation, {
                                            'delay': 10000
                                        });
            });
          });
        }
      });
    }
  };
  $scope.setMethod = function(_method) {
    $scope.data.method = _method;
    $translate('val_method_' + _method).then(function(translatation) {
      selectMethod(_method, translatation);
    });
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
          $translate('msg_' + data + '_redirect').then(function(translation) {
            $notification(translation, {
                                          'delay': 10000
                                      });
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

var backup = function($scope, $rootScope, $notification, $translate) {
  $scope.restore = function() {
    var $fileInput = $(event.target)
        .parents('.section').find('input[type="file"]');
    if ($fileInput.get(0).files.length > 0) {
      var file = $fileInput.get(0).files[0];
      var formData = new FormData();
      formData.append('file', file);

      $.ajax({
             'url': '/admin/?backup',
             'type': 'POST',
             'data': formData,
             'processData': false,  // tell jQuery not to process the data
             'contentType': false,  // tell jQuery not to set contentType
             'success': function(data) {
                $translate('msg_' + data + '_backup').then(function(translation) {
                  $notification(translation, {
                                                'delay': 10000
                                            });
                });
              }
      });
    }
  };
  $scope.get = function() {
    $('body, a.btn.btn-primary').css('cursor', 'progress');
    $.ajax({
      'url': '/admin/?backup',
      'success': function(data) {
        $scope.$apply(function() {
          $('body').append($('<iframe/>')
              .css({
                'height': 0,
                'width': 0
              })
              .attr('src', data['link']));
          $('body, a.btn.btn-primary').css('cursor', '');
        });
      }
    });
  };
};

angular.module('urlshorter', ['notification', 'pascalprecht.translate',
    'ngStorage'])
    .config(['$translateProvider', '$localStorageProvider',
        function($translateProvider, $localStorageProvider) {
        $translateProvider
            .useStaticFilesLoader({
              prefix: '/admin/assets/js/translations/',
              suffix: '.json'
            })
            .useSanitizeValueStrategy('escape')
            .preferredLanguage($localStorageProvider.get('lang') || 'en');
      }])
    .controller('security', ['$scope', '$rootScope', '$notification',
        '$translate', security])
    .controller('settings', ['$scope', '$rootScope', '$notification',
        '$translate', '$localStorage', settings])
    .controller('detail', ['$scope', '$rootScope', '$notification',
        '$translate', detail])
    .controller('list', ['$scope', '$rootScope', '$notification', list])
    .controller('backup', ['$scope', '$rootScope', '$notification',
        '$translate', backup]);
