# Habitat

This project is currently in very early development. For information on the purpose of Habitat,
[read the blog post](https://carlnewton.github.io/posts/location-based-social-network/).

## Getting Started

Fork this repository to create your own instance of Habitat. To adhere to the AGPL license, your fork must be a public
repository, so be careful not to ever commit any secrets to your fork.

## Linux Server Hosting

The packages and setup required for hosting Habitat on a Linux server are in the Ansible playbook.

To run the ansible playbook:

1. Navigate to the `Ansible` directory
2. Copy `vars.yaml.template` to `vars.yaml` and amend its contents accordingly
3. Run `ansible-playbook -i "domain-or-ip-address.example.com," -u example-user playbook.yaml --private-key=~/.ssh/example-key`

## Local Development

1. Navigate to the `Docker/dev` directory and copy the `.env.dist` file to create a new file called `.env`
2. Run `docker-compose up`

Run any application, symfony and composer commands from within the `habitat-apache-php` container:

```sh
docker exec -it habitat-apache-php bash
```

Habitat can be loaded in the web browser from [localhost](http://localhost).

### Running the data retention task

Cron tasks do not run automatically for local development environments. You can run them manually from within the
`habitat-apache-php` container with the following command:

```sh
php /var/www/html/bin/console habitat:data-retention
```
