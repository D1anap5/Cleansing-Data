Bahasa pemrograman = PHP
Library yang digunakan = php_mysql

Tahapan proses cleansing archive terdiri dari 7 tahap :
1. Membuat list archive dengan URL yang dapat di akses dan tidak dapat diakses
Tahapan ini akan mengecek seluruh url akses archive. Jika status code = 200, maka url archive dapat di akses.
Hasil proses ini disimpan dalam file "langkah-1.csv"

2. Menghapus records dan record_dc
Setelah di dapat archive_id yang akan dihapus, dilakukan iterasi terhadap data tersebut.
Tahapan ini akan menghapus data pada tabel records dan record_dc, dan history hasil hapus disimpan dalam file "langkah-2.csv"

3. Menghapus document/file dan metadata pada tabel files
History hasil hapus disimpan dalam file "langkah-3.csv"

4. Hapus archive_settings dengan archive_id yang tidak dapat diakses
History hasil hapus disimpan dalam file "langkah-4.csv"

5. Hapus archive_sessions dengan archive_id yang tidak dapat diakses
History hasil hapus disimpan dalam file "langkah-5.csv"

6. Hapus archive_subjects dengan archive_id yang tidak dapat diakses
History hasil hapus disimpan dalam file "langkah-6.csv"

7. Hapus archives dengan archive_id yang tidak dapat diakses
History hasil hapus disimpan dalam file "langkah-7.csv"

Hasil akhir archive yang telah dilakukan cleansing disimpan dalam file "langkah-8-hasil-akhir.csv"