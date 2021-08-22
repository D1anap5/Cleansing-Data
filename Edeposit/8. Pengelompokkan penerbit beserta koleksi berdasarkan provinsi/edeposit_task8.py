from peewee import *
from playhouse.reflection import generate_models
import csv
import pandas as pd
from tqdm import tqdm
import os
import datetime
import shutil

########################################
#### Opening Connection to Database ####
########################################
edeposit_db = MySQLDatabase(
                'edeposit_v3', 
                host='localhost', 
                port=3306, 
                user='root', 
                password='root'
              )
models = generate_models(edeposit_db)
globals().update(models)

##################################################################################
#### 8.1 Pengelompokan penerbit berdasarkan provinsi menggunakan keyword kota ####
##################################################################################
csv_file_name = "8.1 Updated publishers province_id menggunakan keyword kota.csv"

colums_list = ['updated_at', 'id', 'name', 'kota', 'city_id', 'province_id', 'province']
df_csv = pd.DataFrame(columns=colums_list)
for index, row in tqdm(enumerate(publishers.select().where(publishers.province_id == None, publishers.name.contains('KOTA')))):
  kota_name_buffer = row.name[row.name.upper().index("KOTA"):]
  for index2, row2 in enumerate(cities.select().where(cities.name == kota_name_buffer)):
    list_buffer = [datetime.datetime.now(), row.id, row.name, row2.name, row2.id, row2.province_id, provinces.get_by_id(row2.province_id).name]
    
    publishers.update(city_id = row2.id, province_id = row2.province_id).where(publishers.id == row.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)
print("export to csv...")
if(not os.path.isfile(csv_file_name)):
    df_csv.to_csv(csv_file_name, index=False)
else:
    df_csv.to_csv(csv_file_name, mode='a', header=False, index=False)
print("done.")

#######################################################################################
#### 8.2 Pengelompokan penerbit berdasarkan provinsi menggunakan keyword kabupaten ####
#######################################################################################
csv_file_name = '8.2 Updated publishers province_id menggunakan keyword kabupaten.csv'

colums_list = ['updated_at', 'id', 'name', 'kabupaten', 'city_id','province_id', 'province']
df_csv = pd.DataFrame(columns=colums_list)
for index, row in tqdm(enumerate(publishers.select().where(publishers.province_id == None, publishers.name.contains('KABUPATEN')))):
  kota_name_buffer = row.name[row.name.upper().index("KABUPATEN"):]
  for index2, row2 in enumerate(cities.select().where(cities.name==kota_name_buffer)):
    list_buffer = [datetime.datetime.now(), row.id, row.name, row2.name, row2.id ,row2.province_id, provinces.get_by_id(row2.province_id).name]
    
    publishers.update(city_id = row2.id, 
                      province_id = row2.province_id, 
                      updated_at = datetime.datetime.now()).where(publishers.id == row.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)
print("export to csv...")
if(not os.path.isfile(csv_file_name)):
    df_csv.to_csv(csv_file_name, index=False)
else:
    df_csv.to_csv(csv_file_name, mode='a', header=False, index=False)
print("done.")

#######################################################################################
#### 8.3 Pengelompokan penerbit berdasarkan provinsi menggunakan keyword provinsi  ####
#######################################################################################

csv_file_name = '8.3 Updated publishers province_id menggunakan keyword provinsi.csv'

colums_list = ['updated_at', 'id', 'name', 'province_id', 'province']
df_csv = pd.DataFrame(columns=colums_list)
for index, row in tqdm(enumerate(publishers.select().where(publishers.province_id == None, publishers.name.contains('PROVINSI')))):
  buff = row.name[row.name.upper().index("PROVINSI")+9:]
  for index2, row2 in enumerate(provinces.select().where(provinces.name==buff)):
    list_buffer = [datetime.datetime.now(), row.id, row.name, row2.id, row2.name]
    
    publishers.update(province_id = row2.id).where(publishers.id == row.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)
print("export to csv...")
if(not os.path.isfile(csv_file_name)):
    df_csv.to_csv(csv_file_name, index=False)
else:
    df_csv.to_csv(csv_file_name, mode='a', header=False, index=False)
print("done.")

#####################################################################################
#### 8.4 Pengelompokan penerbitberdasarkan provinsi menggunakan akhiran address  ####
#####################################################################################

csv_file_name = '8.4 Updated publishers province_id menggunakan akhiran alamat.csv'

colums_list = ['updated_at', 'id', 'name', 'address', 'distric', 'distric_id', 'city', 'city_id', 'province', 'province_id']

df_csv = pd.DataFrame(columns=colums_list)

print("Cek berdasarkan distrik pada akhir alamat...")
query = districts.select()
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                        ~(publishers.address.contains("kota")),
                                                        ~(publishers.name.contains("kabupaten")),
                                                        ~(publishers.name.contains("provinsi")),
                                                          publishers.address.endswith(", "+row.name))):
    city_buffer = cities.get_by_id(row.city_id)
    prov_buffer = provinces.get_by_id(city_buffer.province_id)
    list_buffer = [datetime.datetime.now(), row2.id, row2.name, row2.address, row.name, row.id, city_buffer.name, city_buffer.id, prov_buffer.name, prov_buffer.id]
    
    publishers.update(district_id = row.id, city_id = city_buffer.id, province_id = prov_buffer.id).where(publishers.id == row2.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

    
print("Cek berdasarkan kota pada akhir alamat...")
query = cities.select().where(cities.name.startswith('KOTA'))
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                        ~(publishers.address.contains("kota")),
                                                        ~(publishers.name.contains("kabupaten")),
                                                        ~(publishers.name.contains("provinsi")),
                                                          publishers.address.endswith(", "+row.name[5:]))):

    prov_buffer = provinces.get_by_id(row.province_id)
    list_buffer = [datetime.datetime.now(), row2.id, row2.name, row2.address, None, None, row.name, row.id, prov_buffer.name, prov_buffer.id]
    
    publishers.update(city_id = row.id, province_id = prov_buffer.id).where(publishers.id == row2.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

print("Cek berdasarkan kabupaten pada akhir alamat...")
query = cities.select().where(cities.name.startswith('KABUPATEN'))
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                        ~(publishers.address.contains("kota")),
                                                        ~(publishers.name.contains("kabupaten")),
                                                        ~(publishers.name.contains("provinsi")),
                                                          publishers.address.endswith(", "+row.name[10:]))):

    prov_buffer = provinces.get_by_id(row.province_id)
    list_buffer = [datetime.datetime.now(), row2.id, row2.name, row2.address, None, None, row.name, row.id, prov_buffer.name, prov_buffer.id]
    
    publishers.update(city_id = row.id, province_id = prov_buffer.id).where(publishers.id == row2.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

print("Cek berdasarkan provinsi pada akhir alamat...")
query = provinces.select()
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                        ~(publishers.address.contains("kota")),
                                                        ~(publishers.name.contains("kabupaten")),
                                                        ~(publishers.name.contains("provinsi")),
                                                          publishers.address.endswith(row.name))):
    list_buffer = [datetime.datetime.now(), row2.id, row2.name, row2.address, None, None, None, None, row.name, row.id]
    
    publishers.update(province_id = row.id).where(publishers.id == row2.id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)
#=========================================================================================================================
 
print("export to csv...")

if(not os.path.isfile(csv_file_name)):
    df_csv.to_csv(csv_file_name, index=False)
else:
    df_csv.to_csv(csv_file_name, mode='a', header=False, index=False)
    
print("done.")
