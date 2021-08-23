Bahasa pemrograman = PHP
Library yang digunakan = php_mysql

Cara menjalankan source code :
1. Setting koneksi database pada file "connection.php"
2. Buka command line
3. ketik
    [folderphp]\php.exe script.php
4. source code akan berjalan pada background, tunggu hingga selesai

Bahasa Pemrograman : PHP
Library yang dibutuhkan : php_oci8, php_oci_11g

Setiap langkah dijalankan secara bertahap
1. Membuat list master_group_publisher yang duplikat
2. Membuat list master_publisher yang akan dipindahkan ke publisher_group_id yang baru
3. Mengganti publisher_group_id yang lama dengan yang baru
4. Deteksi publisher_group_id dengan nama yang mirip pada tabel master_publisher yang publisher_group_id nya masih null
5. Mengisi publisher_group_id ke dalam tabel master_publisher