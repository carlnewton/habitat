# Habitat

This project is currently in very early development. For information on the purpose of Habitat, [read the blog post](https://carlnewton.github.io/posts/location-based-social-network/).

## Requirements

* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

## Getting Started

1. Navigate to the `Docker` directory and copy the `.env.dist` file to create a new file called `.env`
2. Modify the newly created `.env` file to update the `DB_USER` and `DB_PASSWORD` variables with something secure

    ```
    DB_USER=AzureDiamond
    DB_PASSWORD=Hunter2
    ```

3. Run `docker-compose up`

## Development

Run any application, symfony and composer commands from within the `habitat-apache-php` container:

```sh
docker exec -it habitat-apache-php bash
```

Habitat can be loaded in the web browser from [localhost](http://localhost).
