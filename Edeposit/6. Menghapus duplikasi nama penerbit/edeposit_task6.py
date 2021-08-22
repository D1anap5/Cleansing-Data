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

##################################################################
#### 6. Menghapus duplikasi penerbit dengan code system sama ####
##################################################################

colums_list = ['id', 'name', 'code_system', 'duplicate_id', 'duplicate_name', 'duplicate_code_system', 'same_address_flag', 'same_contact_flag', 'same_phone_flag']
df_csv = pd.DataFrame(columns=colums_list)

query = publishers.select()
print("Checking duplicate...")
for index, row in tqdm(enumerate(query.where(publishers.system_type == "isbn"))):
  # finding duplicate publisher
  for index2, row2 in enumerate(query.where(publishers.id > row.id,
                                            publishers.code_system == row.code_system)):
    same_address_flag = row.address == row2.address
    same_contact_flag = row.contact == row2.contact
    same_phone_flag = row.phone == row2.phone
    list_buffer = [row.id, row.name, row.code_system, row2.id, row2.name, row2.code_system, int(same_address_flag), int(same_contact_flag), int(same_phone_flag)]
    
    # update to csv
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)

    # update collection with publisher_id = duplicate_publisher's id
    collections.update(publisher_id = row.id).where(collections.publisher_id == row2.id).execute()

    # update main publisher with duplicate publisher
    update_query = publishers.get(publishers.id == row.id)
    if (row.province_id == None) and (row2.province_id != None):
      update_query.update(province_id = row2.province_id).execute()
    if (row.city_id == None) and (row2.city_id != None):
      update_query.update(city_id = row2.city_id).execute()
    if (row.district_id == None) and (row2.district_id != None):
      update_query.update(district_id = row2.district_id).execute()
    if (row.village_id == None) and (row2.village_id != None):
      update_query.update(village_id = row2.village_id).execute()
    if (0 if row.name == None else len(row.name) < 0 if row2.name == None else len(row2.name)):
      update_query.update(name = row2.name).execute()
    if (0 if row.phone == None else len(row.phone) < 0 if row2.phone == None else len(row2.phone)):
      update_query.update(phone = row2.phone).execute()
    if (0 if row.website == None else len(row.website) < 0 if row2.website == None else len(row2.website)):
      update_query.update(website = row2.website).execute()
    if (0 if row.address == None else len(row.address) < 0 if row2.address == None else len(row2.address)):
      update_query.update(address = row2.address).execute()

    # delete duplicate publisher
    publishers.delete().where(publishers.id == row2.id).execute()
    
print("export to csv...")
df_csv.to_csv('6. Menghapus duplikasi penerbit dengan code system sama.csv', index=False)
print("done.")