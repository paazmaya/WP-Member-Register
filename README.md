# Member Register - A Wordpress plugin

> Member register offers a way to keep the information of the members organised.

[![Code Climate Maintainability](https://api.codeclimate.com/v1/badges/2e1da8a17b8f699848e7/maintainability)](https://codeclimate.com/github/paazmaya/WP-Member-Register/maintainability)
[![Analytics](https://ga-beacon.appspot.com/UA-2643697-15/wp-member-register/index?flat-gif)](https://github.com/igrigorik/ga-beacon)
[![Wordpress Plugin](https://img.shields.io/wordpress/plugin/r/member-register.svg?style=flat-square)](https://wordpress.org/plugins/member-register/)

The personal information is stored and if the given member has been registered to use
WordPress via WordPress user, they can change their personal settings.
Also martial art grades and membership payments are stored.

For further [WordPress](https://wordpress.org/) related information,
please see [`readme.txt`](./readme.txt),
which also includes the version history and changelog.

Minimum PHP version supported is `5.4.0`.

## Development

Docker utilising environment made originally by
https://medium.com/soluto-engineering/testing-wordpress-plugins-can-be-fun-d926a20452b0


## Publishing to the WordPress Plugin Repository (Subversion)

[Detailed background here](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/).

Clean checkout:

```sh
svn co https://plugins.svn.wordpress.org/member-register wordpress-member-register
```

Or in an existing checkout:

```sh
svn up
```

Now delete all contents from `wordpress-member-register/trunk/`, copy everything from `WP-Member-Register/src/` to `wordpress-member-register/trunk/` and commit.
Follow by creating a version folder under `wordpress-member-register/tags/`, such as `0.22.7` and copy all contents of `wordpress-member-register/trunk/` to there, commit.

In case there has been changes to anything under `WP-Member-Register/assets/`, then also copy those over,
but they go to `wordpress-member-register/assets/`

Remember: Whatever ends up under `wordpress-member-register/tags/` should also be tagged in this git repository, prefixed with `v` as in version.

## Updating translations

TODO: check these commands, since they are really old without comments...

```sh
"C:\Program Files\Poedit\bin\xgettext.exe" --join-existing --default-domain=member-register --indent --sort-output --no-wrap --language=PHP --from-code=UTF-8 --copyright-holder="Jukka Paasonen" --package-name=MemberRegister --package-version=0.6.0 --strict --debug --keyword=__  member-forum.php member-functions.php member-install.php member-register.php member-club.php member-member.php


xgettext \
 --default-domain=member-register \
 --sort-output \
 --no-wrap \
 --language=PHP \
 --from-code=UTF-8 \
 --copyright-holder="Jukka Paasonen" \
 --package-name=MemberRegister \
 --package-version=0.13.0 \
 --strict \
 --debug \
 --keyword=__ \
 -o member-register.pot \
 lang/member-register.pot \
  *.php
```


## Contributing

[Please refer to a GitHub blog post on how to create somewhat perfect pull request.](https://github.com/blog/1943-how-to-write-the-perfect-pull-request "How to write the perfect pull request")

["A Beginner's Guide to Open Source: The Best Advice for Making your First Contribution"](http://www.erikaheidi.com/blog/a-beginners-guide-to-open-source-the-best-advice-for-making-your-first-contribution/).

[Also there is a blog post about "45 Github Issues Dos and Don’ts"](https://davidwalsh.name/45-github-issues-dos-donts).

## Version history

See [`readme.txt`](./readme.txt) for details, which follows the structure defined by WordPress for its plugins.

## License

Licensed under [the MIT license](http://opensource.org/licenses/MIT).

Copyright (c) [Juga Paazmaya](https://paazmaya.fi) <paazmaya@yahoo.com>
