<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config

set('repository', 'https://github.com/carlnewton/habitat.git');
set('keep_releases', 3);

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('example.com')
    ->set('remote_user', 'example-user')
    ->set('deploy_path', '/var/www/html')
    ->set('sub_directory', 'App')
;

// Hooks
task('habitat:stop_messenger', function () {
    if (has('previous_release')) {
        run('{{bin/php}} {{previous_release}}/bin/console messenger:stop-workers');
    }
});

task('habitat:generate_app_secret', function () {
    $secret = trim(shell_exec('openssl rand -hex 16'));
    $envFile = '/var/www/html/shared/.env.local';

    $exists = run("grep -q '^APP_SECRET=' $envFile && echo 'exists' || echo 'not_exists'");

    if ($exists === 'exists') {
        run("sed -i 's/^APP_SECRET=.*/APP_SECRET={$secret}/' $envFile");
    } else {
        run("echo 'APP_SECRET={$secret}' >> $envFile");
    }
});

task('habitat:generate_assets', function () {
    run("cd {{release_path}} && npm install --loglevel=verbose && npm run build");
});

task('habitat:start_messenger', function () {
    run('{{bin/php}} {{release_path}}/bin/console messenger:consume');
});
after('deploy:shared', 'habitat:generate_app_secret');
after('deploy:writable', 'habitat:generate_assets');
after('deploy:failed', 'deploy:unlock');
