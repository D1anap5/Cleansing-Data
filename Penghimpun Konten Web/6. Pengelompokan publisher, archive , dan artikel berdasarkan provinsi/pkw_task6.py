from peewee import *
from playhouse.reflection import generate_models
import csv
import pandas as pd
from tqdm import tqdm
import os
import datetime

pkw = MySQLDatabase(
                'harvester1', 
                host='139.99.31.57', 
                port=3306, 
                user='root', 
                password='root'
              )
models = generate_models(pkw)
globals().update(models)
# =========================================================================
# =========================================================================
# =========================================================================

#####################################################################################################################
#### 6 Pengelompokan penerbitberdasarkan provinsi menggunakan akhiran nama distrik, kota, kabupaten, provinsi  ####
#####################################################################################################################

csv_file_name = '6 Pengelompokan penerbit berdasarkan provinsi menggunakan akhiran nama - distrik, kota, kabupaten, provinsi.csv'

colums_list = ['updated_at', 'id', 'name', 'address', 'distric', 'distric_id', 'regency', 'regency_id', 'province', 'province_id']

df_csv = pd.DataFrame(columns=colums_list)

print("Cek berdasarkan distrik pada akhir nama...")
query = districts.select().where(~districts.name.contains('negara')&~districts.name.contains('bandung'))
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                          publishers.name.endswith(" "+row.name))):
    regency_buffer = regencies.get_by_id(row.regency_id)
    prov_buffer = provinces.get_by_id(regency_buffer.province_id)
    list_buffer = [datetime.datetime.now(), row2.publisher_id, row2.name, row2.address, row.name, row.id, regency_buffer.name, regency_buffer.id, prov_buffer.name, prov_buffer.id]
    
    publishers.update(district_id = row.id, regency_id = regency_buffer.id, province_id = prov_buffer.id).where(publishers.publisher_id == row2.publisher_id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

    
print("Cek berdasarkan kota pada akhir nama...")
query = regencies.select().where(regencies.name.startswith('KOTA'))
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                          publishers.name.endswith(row.name[5:]))):

    prov_buffer = provinces.get_by_id(row.province_id)
    list_buffer = [datetime.datetime.now(), row2.publisher_id, row2.name, row2.address, None, None, row.name, row.id, prov_buffer.name, prov_buffer.id]
    
    publishers.update(regency_id = row.id, province_id = prov_buffer.id ).where(publishers.publisher_id == row2.publisher_id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

print("Cek berdasarkan kabupaten pada akhir nama...")
query = regencies.select().where(regencies.name.startswith('KABUPATEN'))
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                          publishers.name.endswith(row.name[10:]))):

    prov_buffer = provinces.get_by_id(row.province_id)
    list_buffer = [datetime.datetime.now(), row2.publisher_id, row2.name, row2.address, None, None, row.name, row.id, prov_buffer.name, prov_buffer.id]
    
    publishers.update(regency_id = row.id, province_id = prov_buffer.id ).where(publishers.publisher_id == row2.publisher_id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

print("Cek berdasarkan provinsi pada akhir nama...")
query = provinces.select()
for index, row in tqdm(enumerate(query)):
  for index2, row2 in enumerate(publishers.select().where(publishers.province_id == None,
                                                          publishers.name.contains(row.name))):
    list_buffer = [datetime.datetime.now(), row2.publisher_id, row2.name, row2.address, None, None, None, None, row.name, row.id]
    
    publishers.update(province_id = row.id ).where(publishers.publisher_id == row2.publisher_id).execute()
    
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)
#=========================================================================================================================
 
print("export to csv...")

if(not os.path.isfile(csv_file_name)):
    df_csv.to_csv(csv_file_name, index=False)
else:
    df_csv.to_csv(csv_file_name, mode='a', header=False, index=False)
    
print("done.")