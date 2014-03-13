/**
 * CasperJS user interface tests.
 * http://docs.casperjs.org/en/latest/modules/index.html
 * This script uses features that require minimum version 1.1 of CasperJS.
 *
 * Run:
 *  casperjs casperjs-tests.js --engine=slimerjs --debug=yes
 *
 * Then, inspired by https://gist.github.com/danzajdband/9334753
 * Make a video of the sequential screen shots, with reduced resolution.
 *
 * ffmpeg -f image2 -framerate 4 -i "sequential-%05d.png" -vcodec libx264 -vf scale=1024:-1 -b:v 600k output.mp4
 *
 * In order to use -pattern_type glob, at least version 1.0 of FFmpeg is needed.
 * That would allow to use -i "sequential-*.png", thus not requiring all numbers in the
 * sequence to exist. The feature was added in 6 Aug 2012:
 * http://git.videolan.org/?p=ffmpeg.git;a=commit;h=3a06ea843656891fdb4d1072d9df2d5c3c9426f5
 */
'use strict';

var baseUrl = 'http://192.168.1.37:8803/';
var screensDir = 'screens/';
var viewportSize = {
  width: 1480,
  height: 1200
};
var sequentialIntervalId;
var sequentialCount = 0;

var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  viewportSize: viewportSize,
  localToRemoteUrlAccessEnabled: true,
  pageSettings: {
    javascriptEnabled: true,
    loadImages: true,
    loadPlugins: true
  }
});

// http://docs.casperjs.org/en/latest/modules/utils.html
var utils = require('utils');

// https://github.com/ariya/phantomjs/wiki/API-Reference-FileSystem
var fs = require('fs');

casper.on('waitFor.timeout', function (timeout, details) {
  this.echo('waitFor.timeout: ' + details);
  casper.page.render(screensDir + 'debug' + Date.now() + '.png');
  utils.dump(details);
});
casper.on('remote.callback', function (data) {
  this.echo('remote.callback: ' + data);
  utils.dump(data);
});
casper.on('remote.message', function (msg) {
  this.echo('remote.message: ' + msg);
});
casper.on('resource.error', function (err) {
  this.echo('resource.error: ' + err);
  utils.dump(err);
});
casper.on('page.error', function (err) {
  this.echo('page.error: ' + err);
});
casper.on('error', function (err) {
  this.echo('error: ' + err);
});

// Capture everything with this method
var captureElement = function (self, file, selector) {
  self.then(function () {
    var filePath = screensDir + file + '.png';
    self.captureSelector(filePath, selector);
    captured.push(filePath);
  });
};


/*
Dummy data that will be used for creating new content
Each item should contain array of objects.
Each of those objects are used to fill and submit the given form.

Please note that CasperJS can select only one item even in select[multiple] case.
*/
var dummyData = {
  publicRegistrationUsers: [
    {
      user_login: '',
      access: '',
      email: '',

      firstname: '',
      lastname: '',
      birthdate: '',
      address: '',
      zipcode: '',
      postal: '',
      phone: '',
      nationality: '',
      martial: '',
      club: ''
    }
  ],
  users: [ // role: subscriber
    {
      user_login: '',
      access: '',
      firstname: '',
      lastname: '',
      birthdate: '',
      address: '',
      zipcode: '',
      postal: '',
      phone: '',
      email: '',
      nationality: '',
      joindate: '',
      passnro: '',
      martial: '',
      notes: '',
      active: '', // radio, should be the value
      club: ''
    }
  ],
  grades: [
    {
      'members[]': [],
      grade: '',
      type: 'yuishinkai', // radio
      location: '',
      nominator: '',
      day: ''
    }
  ],
  payments: [
    {
      'members[]': [],
      type: '',
      amount: '',
      deadline: '',
      validuntil: '',
      alreadypaid: false
    }
  ],
  groups: [
    {
      title: '',
      'members[]': []
    }
  ],
  clubs: [
    {
      title: '',
      address: ''
    }
  ],
  files: [
    {
      uploadfile: fs.workingDirectory + '/README.md' , // file
      directory: '',
      club: '',
      grade: '',
      art: '',
      group: ''
    },
    {
      uploadfile: fs.workingDirectory + '/readme.txt' , // file
      directory: 'testing-only',
      club: '',
      grade: '',
      art: '',
      group: ''
    },
    {
      uploadfile: fs.workingDirectory + '/screenshot-1.jpg' , // file
      directory: '',
      club: '1',
      grade: '',
      art: '',
      group: ''
    },
    {
      uploadfile: fs.workingDirectory + '/screenshot-2.jpg' , // file
      directory: '',
      club: '',
      grade: '1K',
      art: '',
      group: ''
    },
    {
      uploadfile: fs.workingDirectory + '/xgettext.txt' , // file
      directory: '',
      club: '',
      grade: '',
      art: 'karate',
      group: ''
    },
    {
      uploadfile: fs.workingDirectory + '/.editorconfig' , // file
      directory: '',
      club: '',
      grade: '',
      art: '',
      group: '1'
    }
  ],
  topics: [
    {
      title: 'So much to talk...'
    }
  ],
  messages: [
    {
      content: 'First going there'
    },
    {
      content: 'Secondly coming here'
    }
  ]
};

/*
User names and passwords for each access level.
Only the highest access levels needing user needs to be created by hand and given all access.
The password should be randomised before using this script.
*/
var testUsers = [
  {
    username: 'test-ui-user-1',
    password: '',
    access: 1
  },
  {
    username: 'test-ui-user-2',
    password: '',
    access: 2
  },
  {
    username: 'test-ui-user-4',
    password: '',
    access: 4
  },
  {
    username: 'test-ui-user-8',
    password: '',
    access: 8
  },
  {
    username: 'test-ui-user-16',
    password: '',
    access: 16
  },
  {
    username: 'test-ui-user-32',
    password: '',
    access: 32
  },
  {
    username: 'test-ui-user-64',
    password: '',
    access: 64
  },
  {
    username: 'test-ui-user-128',
    password: '',
    access: 128
  },
  {
    username: 'test-ui-user-256',
    password: '',
    access: 256
  },
  {
    username: 'test-ui-user-512',
    password: '',
    access: 512
  },
  {
    username: 'test-ui-user-1024',
    password: '',
    access: 1024
  },
  {
    username: 'test-ui-user-2048',
    password: 'GnPzvM0d!bv9YVes0',
    email: 'test-ui-user-2048@192.168.1.37',
    access: 2048
  }

];

// Record images to be used in a video
var startSequentialCaptures = function () {
  var timeout = 100;
  return setInterval(function () {
    var n = ((sequentialCount < 10) ? '0' : '') +
      ((sequentialCount < 100) ? '0' : '') +
      ((sequentialCount < 1000) ? '0' : '') +
      ((sequentialCount < 10000) ? '0' : '') + sequentialCount;
    var box = viewportSize;
    box.top = 0;
    box.left = 0;
    casper.capture(screensDir + 'sequential-' + n + '.png', box);
    ++sequentialCount;
  }, timeout);
};


// kirjaudu-sisaan/
casper.start(baseUrl + 'wp-login.php', function () {
  sequentialIntervalId = startSequentialCaptures();
  this.echo(this.getTitle());

  var lastUser = testUsers[testUsers.length - 1];

  // Do login...
  this.fillSelectors(
    'form#loginform',
    {
      'input#user_login': lastUser.username,
      'input#user_pass': lastUser.password
    },
    false
  );
  this.click('#wp-submit', 'Login form submit clicked');
  this.waitForSelector('#wpadminbar');
});

// Go through all form functionality, by creating new items and deleting them.
casper.setFilter('page.confirm', function(msg) {
  return true;
});
// Topic and messages
casper.thenOpen(baseUrl + 'wp-admin/admin.php?page=member-forum', function () {
  var topic = dummyData.topics[0];
  this.fill('form', topic, true);
  this.then(function () {
    // Now find that topic from the list and click its title
    this.click('td[data-sort-value="' + topic.title + '"] > a', 'Open topic clicked');
  });

  // Topic list page, from PHP mr_show_form_post() method
  this.eachThen(dummyData.messages, function (response) {
    var message = response.data;
    this.waitForSelector('textarea[name="content"]', function () {
      this.fill('form[name="form1"]', message, true);
    });
  });

  // Remove last own message
  this.then(function () {
    this.click('tr:last-child a.dashicons-dismiss', 'Remove last message in the list clicked');
  });
});

// Files, upload and delete
casper.thenOpen(baseUrl + 'wp-admin/admin.php?page=member-files-new', function () {
  this.waitForSelector('input[name="uploadfile"]', function () {
    this.eachThen(dummyData.files, function (response) {
      var files = response.data;
      this.waitForSelector('input[name="uploadfile"]', function () {
        this.fill('form[name="form1"]', files, true);
      });
    });
  });
});
casper.thenOpen(baseUrl + 'wp-admin/admin.php?page=member-files', function () {
  this.eachThen(dummyData.files, function (response) {
    var files = response.data;
    var basename = files.uploadfile.split('/').pop();
    this.thenEvaluate(function(basename) {
      jQuery('a[rel="remove"][title*="' + basename + '"]').click();
    }, basename);
    this.wait(100);
  });
});

// Groups
casper.thenOpen(baseUrl + 'wp-admin/admin.php?page=member-group-list', function () {
  this.waitForSelector('a[href~="create-group"]', function () {
    this.eachThen(dummyData.groups, function (response) {
      var files = response.data;
      this.waitForSelector('input[name="uploadfile"]', function () {
        this.fill('form[name="form1"]', files, true);
      });
    });
  });
});



casper.wait(200);

casper.run(function() {
  clearInterval(sequentialIntervalId);
  this.exit();
});


