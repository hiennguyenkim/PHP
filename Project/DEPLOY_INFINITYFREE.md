# Deploy len InfinityFree

## Luu y truoc khi deploy

- InfinityFree khong ho tro Docker tren free hosting.
- InfinityFree khong ho tro environment variables theo cach thong thuong, nen app nay da duoc chinh de doc DB config tu file `config.local.php` hoac `config.php`.
- PHP free hosting cua InfinityFree da o phien ban 8.3.
- Khong duoc chay `CREATE DATABASE` bang SQL tren free hosting, nen hay import file `database.infinityfree.sql`.

## 1. Tao hosting account va domain

Tao website tren InfinityFree, sau do vao thu muc web root `htdocs`.

Theo huong dan tren forum InfinityFree, file website phai duoc upload vao `htdocs`.

## 2. Upload source code

Upload toan bo noi dung trong thu muc `Project` len `htdocs`.

Ket qua dung la trong `htdocs` co cac muc nhu:

- `index.php`
- `Core`
- `Controllers`
- `Models`
- `Views`
- `Public`

Khong upload ca thu muc `Project` bao ngoai, neu khong URL se sai.

Neu upload zip khong on dinh, hay giai nen tren may truoc roi upload tung file/folder. InfinityFree co gioi han kich thuoc file, nen cach nay on dinh hon.

## 3. Tao file config database

1. Copy `config.php.example` thanh `config.local.php`.
2. Dien thong tin MySQL do InfinityFree cap.

Mau:

```php
<?php
return [
    'database' => [
        'host' => 'hostname-duoc-cap-trong-control-panel',
        'name' => 'epiz_12345678_mvc_app',
        'user' => 'epiz_12345678',
        'pass' => 'mat_khau_hosting_account',
    ],
];
```

Luu y:

- `host` khong phai `localhost` hay `127.0.0.1`.
- Dung chinh xac hostname trong muc `MySQL Databases` cua panel.
- `pass` tren InfinityFree thuong la hosting account password, khong phai client area password.
- `config.local.php` la file local de giu thong tin nhay cam va khong nen day len GitHub.

## 4. Tao database va import du lieu

1. Vao `MySQL Databases` trong control panel.
2. Tao database moi.
3. Mo `phpMyAdmin`.
4. Import file `database.infinityfree.sql`.

Neu import loi do file lon hoac session het han, InfinityFree forum khuyen vao lai phpMyAdmin va import lai.

Luu y:

- `database.sql` hien tai phu hop cho local/XAMPP vi co `CREATE DATABASE`.
- Tren InfinityFree, dung file `database.infinityfree.sql` de tranh loi quyen.

## 5. Tai khoan mau sau khi import

- `admin` / `admin123`
- `nguyenvana` / `member123`

## 6. Neu bi loi sau khi upload

- Kiem tra file co nam dung trong `htdocs` khong.
- Kiem tra `config.local.php` da dung host DB chua.
- Kiem tra database da import thanh cong chua.
- Bat xem loi PHP trong control panel neu trang trang.

## Nguon tham khao

- PHP 8.3 tren free hosting:
  https://forum.infinityfree.com/t/free-hosting-is-now-upgraded-to-php-8-3/109714
- Upload file vao `htdocs`:
  https://forum.infinityfree.com/t/why-are-my-files-deleted-after-uploading-them/49310
- Upload script lon / nen giai nen truoc khi upload:
  https://forum.infinityfree.com/t/how-to-upload-big-files-archives/49305
- Khong dung `localhost`, hay dung hostname MySQL trong panel:
  https://forum.infinityfree.com/t/common-mysql-connection-errors/49338
- Co the import `.sql` bang phpMyAdmin:
  https://forum.infinityfree.com/t/how-to-import-a-mysql-database-backup/49341
- Khong duoc `CREATE DATABASE` bang SQL tren free hosting:
  https://forum.infinityfree.com/t/access-denied-importing-database-1044/76797
- InfinityFree khong ho tro environment variables nhu mong doi:
  https://forum.infinityfree.com/t/putenv-has-been-disabled-for-security-reasons/7296/10
