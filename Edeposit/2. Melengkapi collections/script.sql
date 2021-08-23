SET @namaFolder := 'nama folder'; #masukkan nama folder Anda

#isi kolom access
SELECT id,title,access 
FROM collections 
WHERE access is null 
INTO OUTFILE concat(@namaFolder,'/','1_access_null.csv') FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';

UPDATE collections SET access = 2 
WHERE access is null;

#get list koleksi yang preview nya null
SELECT id, title, preview,
CASE
WHEN type = 1 THEN "buku"
WHEN type = 2 THEN "partitur"
WHEN type = 3 THEN "peta"
WHEN type = 4 THEN "serial"
WHEN type = 5 THEN "audio"
WHEN type = 6 THEN "film"
END 
FROM collections
WHERE preview is null 
INTO OUTFILE concat(@namaFolder,'/', '2_preview_null.csv') FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';

#update collections yang previewnya null
UPDATE collections SET preview = 
CASE
WHEN type = 1 THEN "1-10" #buku
WHEN type = 2 THEN "1-1" #partitur
WHEN type = 3 THEN "1-1" #peta
WHEN type = 4 THEN "1-1" #serial
WHEN type = 5 THEN "00:00-00:45" #audio
WHEN type = 6 THEN "00:00-01:30" #film
END
WHERE preview is null;

#check apakah masih ada koleksi null
SELECT id, title, preview,
CASE
WHEN type = 1 THEN "buku"
WHEN type = 2 THEN "partitur"
WHEN type = 3 THEN "peta"
WHEN type = 4 THEN "serial"
WHEN type = 5 THEN "audio"
WHEN type = 6 THEN "film"
END 
FROM collections
INTO OUTFILE concat(@namaFolder,'/','3_hasil_cleansing_preview_koleksi.csv') FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';

#get collections yang ada city_id nya dan bandingkan dengan publishers
SELECT collections.id, title, collections.city_id, publishers.city_id
FROM collections
JOIN publishers  ON collections.publisher_id = publishers.id 
WHERE collections.city_id is not null
INTO OUTFILE concat(@namaFolder,'/', '4_collections_yang_ada_city_id.csv') FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';

#get collections yang city_id null dan bandingkan dengan publishers
SELECT collections.id, title, collections.city_id, publishers.city_id
FROM collections
JOIN publishers  ON collections.publisher_id = publishers.id 
WHERE collections.city_id is null
INTO OUTFILE concat(@namaFolder,'/','5_collections_yang_city_id_null.csv') FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';

UPDATE collections, publishers
INNER JOIN publishers ON collections.publisher_id = publishers.id
SET collections.city_id = publisher_id.city_id
WHERE collections.city_id IS NULL;

#cek semua city_id pada koleksi
SELECT collections.id, title, collections.city_id, publishers.city_id
FROM collections
JOIN publishers  ON collections.publisher_id = publishers.id 
INTO OUTFILE concat(@namaFolder,'/','6_hasil_cleansing_collections_city_id.csv') FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';
