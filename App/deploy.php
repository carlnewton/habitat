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

task('habitat:generate_app_secret', function () {
    $secret = trim(shell_exec('openssl rand -hex 16'));
    $envFile = '/var/www/html/shared/.env.local';

    $exists = run("grep -q '^APP_SECRET=' $envFile && echo 'exists' || echo 'not_exists'");

    if ('exists' === $exists) {
        run("sed -i 's/^APP_SECRET=.*/APP_SECRET={$secret}/' $envFile");
    } else {
        run("echo 'APP_SECRET={$secret}' >> $envFile");
    }
});

task('habitat:generate_assets', function () {
    run('cd {{release_path}} && npm install --loglevel=verbose && npm run build');
});

task('habitat:prepare', [
    'habitat:generate_app_secret',
]);

task('habitat:deploy', [
    'habitat:generate_assets',
]);

after('deploy:shared', 'habitat:prepare');
after('deploy:vendors', 'habitat:deploy');
after('habitat:deploy', 'database:migrate');
after('deploy:failed', 'deploy:unlock');
