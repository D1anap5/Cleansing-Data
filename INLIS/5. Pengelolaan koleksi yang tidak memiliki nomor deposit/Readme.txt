Cara menjalankan source code :
1. Taruh folder di XAMPP atau di web server lainnya (IIS, Apache, NGINX, dll)
2. Jalankan pada browser dengan memanggil file index.php
3. halaman awal yang terbuka seperti pada index.jpg

Setiap langkah dijalankan secara bertahap
1. Membuat daftar Koleksi dengan noinduk_deposit null
Mengambil list koleksi yang noinduk_deposit = null pada tabel collections
Source code : langkah-1.php
Hasil dari proses ini adalah 1 file csv dengan nama "langkah-1.csv"


2. Membuat daftar Koleksi yang noinduk_deposit null join dengan catalog pada tag 090
Tag 090 pada catalog ruas biasanya berisi nomor panggil, sehingga kemungkinan ada yang berisi nomor induk deposit.
Data ini dapat digunakan untuk mengisi noinduk_deposit yang null pada tabel collections.
Source code : langkah-2.php
Hasil dari proses ini adalah 1 file csv dengan nama "langkah-2.csv"

3. Clean tag 090
Value tag 090 pada tabel catalog_sub_ruas perlu di normalisasi sesuai standar nomor induk deposit.
Pada tahapan ini akan menggunakan file 'langkah-2.csv' yang dihasilkan pada tahap sebelumnya.
Source code : langkah-3.php
Hasil dari proses ini berupa 2 file csv dengan nama "langkah-3.csv" dan "langkah-3-jilid.csv"
langkah-3.csv adalah file yang dapat dieksekusi pada tahapan berikutnya karena dianggap paling memenuhi syarat.
sementara langkah-3-jilid berisi nomor induk deposit jilid yang digabung menjadi satu, contohnya :
- CB-D.9 2010-8788(5)/4347-2012; ..(6)/5525-2012; ..(18)/4528-2009 ; 4283-2012 ; 2011-1890/6361-2011 ; 2009-.../5361-2011 ; 2009-.../3691-2011 ; 2010- /3789-2011 (8) ; 4131-2012 (8) ; 4132-2012 (9) ; 4133-2012 (10) ; 4134-2012 (17) ; 2010-9733(4) ; 2010-9605 (8)
- CB-D.11 2013-3460(jil.2)/7791-2013 ; 2013-3460(jil.3)/7792-2013 ; 2013-3460(jil.4)/7793-2013 ; 2013-3460(Jil.6)/7794-2013
pada kolom noinduk_deposit tabel collections string panjang tersebut tidak dapat di insert karena maksimum 100 char.
Perlu pertimbangan dari pengguna aplikasi mengenai langkah selanjutnya

4. Eksekusi ke database untuk koleksi non jilid
Tahapan terakhir dalam melakukan pengisian nomor induk deposit.
source code : langkah-4.php
Hasil dari proses ini adalah data yang telah terupdate pada server.
Jika sudah selesai akan tampak seperti gambar "langkah-4.jpg"