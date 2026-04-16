# Deploy Thu Vien MVC bang Docker

## 1. Chuan bi

Sao chep file moi truong:

```bash
cp .env.example .env
```

Ho tren PowerShell:

```powershell
Copy-Item .env.example .env
```

Neu deploy that, hay doi it nhat:

- `DB_PASS`
- `DB_ROOT_PASSWORD`
- `APP_PORT`

## 2. Build va chay

```bash
docker compose up -d --build
```

Sau khi xong:

- Web: `http://localhost:8080`
- MySQL host tu may ngoai: `127.0.0.1:3307`

## 3. Du lieu mau

Container MySQL se tu dong import file:

`Project/database.sql`

Tai khoan mau:

- `admin` / `admin123`
- `nguyenvana` / `member123`

## 4. Lenh quan tri thuong dung

Xem log:

```bash
docker compose logs -f
```

Dung he thong:

```bash
docker compose down
```

Dung va xoa ca volume database:

```bash
docker compose down -v
```

## 5. Ghi chu deploy that

- App web chay trong container `web`.
- Database chay trong container `db`.
- Bien moi truong DB trong app da duoc map san qua `docker-compose.yml`.
- Neu ban deploy len VPS, chi can clone source, cai Docker/Docker Compose, tao `.env`, roi chay `docker compose up -d --build`.
