# Contributing Guide

This project does not have formalized or rigid contribution processes. We keep it simple and subscribe to a "see something, say something" philosophy with a "if it's broken, figure out where and fix it". Due to the simple architecture, it's likely that any problems encountered can be fixed within a single method or with a find and replace of a repeated line of code.

Please consider these to be guidelines. If in doubt, please create an issue and tag the [maintainers](README.md#maintainers) to discuss.

## Feature Requests and Ideas

Please [create an issue](https://gitlab.com/gitlab-it/okta-sdk/-/issues) and describe what you'd like to see. Since this project is designed as an internal tool, we will help where we can but no guarantees.

## Code Contributions

Please create an issue first to document the purpose of the contribution from a changelog and release notes perspective. After the issue is created, create a merge request from inside the issue, then checkout the branch that was created automatically for the issue and merge request. By creating the merge request from inside the issue, everything stays connected automatically and there are no name disparities.

Before assigning your MR to a maintainer, please review the pipeline CI job outputs for any errors and fix anything that appears.

All merge requests can be assigned to one or all of the maintainers at your discretion. It is helpful to comment in the issue when you're ready to merge with any context that the maintainer/reviewer should know or be on the look out for.

## Environment Configuration

### Configuring Your Development Environment with Working Copies of Packages

When you run `composer install`, you will get the latest copy of the packages from the GitHub and GitLab repositories. However, you won't be able to see real-time changes if you change any code in the packages.

You can mitigate this problem by creating a local symlink (with resolved namespaces) for the package inside of your application that you're using for development and testing. By symlinking the packages into the newly created `packages` directory, you'll be able to preview and test your work without doing any Git commits (bad practice).

```bash
# Pre-Requisite (you should already have this)
# You can use any directory you want (if not using ~/Sites)
cd ~/Sites
git clone https://gitlab.com/gitlab-it/okta-sdk.git
```

```bash
cd ~/Sites/{my-laravel-app}
mkdir -p packages/glamstack
cd packages/gitlab-it
ln -s ~/Sites/okta-sdk okta-sdk
```

### Application Composer

Update the `composer.json` file in your testing application (not the package) to add the package to the `autoload.psr-4` array (append the array, don't replace anything).

```json
# ~/Sites/{my-laravel-app}/composer.json

"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Glamstack\\Okta\\": "packages/gitlab-it/okta-sdk/src",
    }
},
```

### Configure Local Composer Repository

Credit: https://laravel-news.com/developing-laravel-packages-with-local-composer-dependencies

```bash
cd ~/Sites/{my-laravel-app}

composer config repositories.okta-sdk '{"type": "path", "url": "packages/gitlab-it/okta-sdk"}' --file composer.json

composer require gitlab-it/okta-sdk:dev-main

# Package operations: 1 install, 0 updates, 0 removals
#  - Installing glamstack/okta-sdk (dev-main): Symlinking from packages/glamstack/okta-sdk
```

### Validation and Config Copy

```bash
php artisan vendor:publish --tag=okta-sdk

# Copied File [/Users/jmartin/Sites/okta-sdk/src/Config/okta-sdk.php] To [/config/okta-sdk.php]
# Publishing complete.
```

### Caching Problems

If you run into any classes or files that are renamed and are throwing `Not Found` errors, you may need to use the `composer dump-autoload` command.
