# Habitat

Habitat is a free and open source, self-hosted platform for communities to discover and discuss their local area.

Locals can create posts linked to a specific location on a map, making it easy for others to find and join the
conversation about their local area.

## Getting Started

### Docker Compose

To install with Docker Compose create a `habitat-compose.yml` file and add the following contents:

```yaml
services:
  habitat-app:
    container_name: habitat
    image: carlnewton/habitat:latest
    restart: unless-stopped
    environment:
      SERVER_NAME: https://${DOMAIN}
      MERCURE_PUBLIC_URL: https://${DOMAIN}/.well-known/mercure
      DEFAULT_URI: https://${DOMAIN}"
      APP_SECRET: ${APP_SECRET}
      ENCRYPTION_KEY: ${ENCRYPTION_KEY}
      DATABASE_URL: postgresql://${POSTGRES_USER:-app}:${POSTGRES_PASSWORD:-!ChangeMe!}@habitat-database:5432/${POSTGRES_DB:-app}?serverVersion=${POSTGRES_VERSION:-15}&charset=${POSTGRES_CHARSET:-utf8}
    volumes:
      - caddy_data:/data
      - caddy_config:/config
      - habitat_uploads:/uploads
    networks:
      habitat:
    security_opt:
      - no-new-privileges:true

  habitat-database:
    image: postgres:${POSTGRES_VERSION:-16}-alpine
    environment:
      POSTGRES_DB: ${POSTGRES_DB:-app}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-!ChangeMe!}
      POSTGRES_USER: ${POSTGRES_USER:-app}
    healthcheck:
      test: ["CMD", "pg_isready", "-d", "${POSTGRES_DB:-app}", "-U", "${POSTGRES_USER:-app}"]
      timeout: 5s
      retries: 5
      start_period: 60s
    networks:
      habitat:
    volumes:
      - database_data:/var/lib/postgresql/data:rw
    security_opt:
      - no-new-privileges:true

networks:
  habitat:

volumes:
  caddy_data:
  caddy_config:
  habitat_uploads:
  database_data:
```

and a `.env` file in the same directory containing the following:

```env
# The domain of your Habitat instance
DOMAIN=example.com

# The APP_SECRET should be a 32 character string of characters, numbers and symbols. It should be unique to your Habitat
# instance, and should be kept secret. It is also good practice to change this ahead of running composer pull.
# See https://symfony.com/doc/current/reference/configuration/framework.html#secret
APP_SECRET=!YouMustChangeThisAppSecret!

ENCRYPTION_KEY=!YouMustChangeThisEncryptionKey!
POSTGRES_USER=!YouMustChangeThisPostgresUser!
POSTGRES_PASSWORD=!YouMustChangeThisPostgresPassword!
POSTGRES_DB=habitat
```

## Local Development

1. Navigate to the `Docker/dev` directory and copy the `.env.dist` file to create a new file called `.env`
2. Run `docker-compose up`

Run any application, symfony and composer commands from within the `habitat-apache-php` container:

```sh
docker exec -it habitat-apache-php bash
```

Habitat can be loaded in the web browser from [localhost](http://localhost).

## Further Information

- [Could We Build a Decentralised Social Platform Rooted in Place?](https://carlnewton.github.io/posts/location-based-social-network/)
- [I'm Building Habitat](https://carlnewton.github.io/posts/building-habitat/)
