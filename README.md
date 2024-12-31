# Habitat

This project is currently in very early development. For information on the purpose of Habitat,
[read the blog post](https://carlnewton.github.io/posts/location-based-social-network/).

## Getting Started

Fork this repository to create your own instance of Habitat. To adhere to the AGPL license, your fork must be a public
repository, so be careful not to ever commit any secrets to your fork.

## Linux Server Hosting

The packages and setup required for hosting Habitat on a Linux server are in the Ansible playbook.

Install ansible and the collections:

```sh
ansible-galaxy collection install community.general
ansible-galaxy collection install community.mysql
```

To run the ansible playbook:

1. Navigate to the `Ansible` directory
2. Copy `vars.yaml.template` to `vars.yaml` and amend its contents accordingly
3. Run `ansible-playbook -i "domain-or-ip-address.example.com," -u example-user playbook.yaml --key-file=~/.ssh/example-key`

### Deploying the application

1. Set the `repository` setting in `App/deploy.php` to your fork of Habitat
2. Update the `host` entry in `App/deploy.php` accordingly
3. Run `vendor/bin/dep deploy`

## Docker Hosting

Habitat can be setup to run on a container hosting service (Such as [Google Cloud Run](https://cloud.google.com/run)).

When hosting Habitat using the Dockerfile container, you'll need to connect it to:

- A volume at `/var/www/uploads/` for persistent image storage
- A database which will be connected to with an environment variable
- A mail service for sending out emails

### Continuous Deployment

Many container hosting services offer continous deployment so that any changes to your fork of the Habitat repository
automatically get deployed. 

To do so, ensure that the hosting service uses the following settings:

| Setting             | Value       |
| ------------------- | ----------- |
| Branch              | main        |
| Build type          | Dockerfile  |
| Dockerfile location | /Dockerfile |

### Configuration

| Setting        | Value |
| -------------- | ----- |
| Container port | 80    |

### Volumes

You'll need to attach a volume for image storage. If using cloud hosting, it's recommended to use a storage bucket, such
as [Google Cloud Storage](https://cloud.google.com/storage/) or [Amazon S3](https://docs.aws.amazon.com/AmazonS3/latest/userguide/Welcome.html).

| Setting    | Value            |
| ---------- | ---------------- |
| Mount path | /var/www/uploads |

### Environment Variables

You'll need to set environment variables to allow the connection of a database and email handler. If using Google Cloud
hosting, you can use the [Google Cloud SQL](https://cloud.google.com/sql) service for the database, and if using AWS,
you could use [AWS RDS](https://aws.amazon.com/rds/). For email handling, [Mailjet](https://www.mailjet.com/) have a
free tier that should be enough for most Habitat isntances.

| Name           | Value                                  |
| -------------- | -------------------------------------- |
| DATABASE_URL   | mysql://dbUsername:dbPassword@localhost:3306/example-db-name?unix_socket=/cloudsql/example:database-instance:connection-name&serverVersion=8.0.31&charset=utf8mb4 |
| APP_ENV        | prod                                   |
| ENCRYPTION_KEY | (Generate an encryption key*)          |

There are online key generators. It is recommended that the encryption key is no fewer than 32 characters, consists of
uppercase, lowercase, numeric and special characters.

## Local Development

1. Navigate to the `Docker/dev` directory and copy the `.env.dist` file to create a new file called `.env`
2. Run `docker-compose up`

Run any application, symfony and composer commands from within the `habitat-apache-php` container:

```sh
docker exec -it habitat-apache-php bash
```

Habitat can be loaded in the web browser from [localhost](http://localhost).

## Running the data retention task

Cron tasks do not run automatically for local development environments. You can run them manually from within the
`habitat-apache-php` container with the following command:

```sh
php /var/www/html/bin/console habitat:data-retention
```
