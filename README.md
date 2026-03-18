## Run with Docker

Requirements:

- Docker
- Docker Compose

### 1) Environment

Copy `.env.example` to `.env` and adjust values if needed.

### 2) Start containers

```bash
make up
```

### 3) Install dependencies and run migrations

```bash
make init
```

### 4) Open the app

Open in your browser:

`http://localhost:8080`

### Stop

```bash
make down
```