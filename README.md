# Cross-Origin Auth Backend

This repository contains the **back-end** part of a project built with [**Docker**](https://www.docker.com/).  
It is designed to communicate securely with an external **API hosted on a different origin** (domain, port, or protocol). The link of the front-end repo is [here](https://github.com/alanakra/cross-origin-auth-frontend)

## Environment

- **Front-end:** http://demo-register-client.local:5173  
- **Back-end (API):** http://demo-register-server.local:8080/

This architecture is considered **cross-site**, since both the client and server use different hostnames and ports.

## Setting up Custom Domains (Localhost)

To make your local development setup more realistic and modular, you can serve your apps using **custom local domains** such as:
- `demo-register-client.local`
- `demo-register-server.local`

### 1. Edit your `hosts` file (If isn't done.)
Map these domains to your local IP address (`127.0.0.1`).

#### On Windows:
Edit the file located at: `C:\Windows\System32\drivers\etc\hosts`

Add:

`127.0.0.1 demo-register-client.local`
`127.0.0.1 demo-register-server.local`


#### On macOS / Linux:
Edit `/etc/hosts` (requires `sudo`):

`127.0.0.1 demo-register-client.local`
`127.0.0.1 demo-register-server.local`

### 1bis. Set environments variables

Add a first `.env` file at the root of the repo with the following structure:
```
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=app_db
MYSQL_USER=user
MYSQL_PASSWORD=user
```
Theses values are for MYSQL container in `docker-compose.yml` file.

Add a second `.env` file at the root of `www` folder with the following structure:
```
DB_HOST=mysql
DB_USERNAME=user
DB_PASSWORD=user
DB_NAME=app_db
JWT_SECRET=ADD_GENERATED_JWT_SECRET
```
To generate JWT secret, run on your terminal `openssl rand -base64 64` or `node -e "console.log(require('crypto').randomBytes(64).toString('hex'))`.

### 2. Create Docker image
Run `docker build -t docker-php-auth .`

### 3. Start containers
Run `docker compose up -d`

Then go to `http://demo-register-server.local:8080` for API endpoint and `http://demo-register-server.local:8081/` to access to PhpmMyAdmin.

### 4. Import database
Import the following [database](https://gist.github.com/alanakra/4227596bbb85f3745cf97bed5b35d833) and import it via PhpMyAdmin.