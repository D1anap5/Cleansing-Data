Menggunakan SQL Programming;

Jika terdapat error “MySQL server is running with the –secure-file-priv”
Atur "my.ini"
cari secure_file_priv
isi dengan secure_file_priv=""
restart “MySQL" pada services

Lakukan setting folder untuk menyimpan hasil / bukti cleansing.
buka file script.sql dan ganti 'namaFolder' dengan Lokasi yang Anda inginkan.
Jalankan query pada command line dengan memanggil file script.sql