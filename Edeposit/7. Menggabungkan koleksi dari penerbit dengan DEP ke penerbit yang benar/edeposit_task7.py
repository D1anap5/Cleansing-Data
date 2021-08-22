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

#####################################
#### 7. Cek penerbit dengan /DEP ####
#####################################

colums_list_updated_publishers = ['updated_at', 'id', 'name', 'updated_name']
colums_list_deleted_publishers = ['deleted_at', 'id', 'original_id', 'original_name']
colums_list_updated_collection = ['updated_at', 'id', 'publisher_id', 'updated_publisher_id']

csv71_file_name = '7.1 Updated publishers DEP tanpa pair.csv'
csv72_file_name = '7.2 Delated publishers DEP dengan pair.csv'
csv73_file_name = '7.3 Updated collections dengan publisher_id baru.csv'

df_csv71 = pd.DataFrame(columns=colums_list_updated_publishers)
df_csv72 = pd.DataFrame(columns=colums_list_deleted_publishers)
df_csv73 = pd.DataFrame(columns=colums_list_updated_collection)

print("Searching publisher with name contain '/DEP'")
query = publishers.select().where(publishers.name.endswith("/DEP"))
for index, row in tqdm(enumerate(query)):

  try:
    original_one = publishers.get(publishers.name == row.name[:-4])
  except:
    original_one = None
    
  if(original_one != None):    
    list_buffers72 = [datetime.datetime.now(), row.id, original_one.id, original_one.name]
    df_buffer72 = pd.Series(list_buffers72, index = df_csv72.columns)
    df_csv72 = df_csv72.append(df_buffer72, ignore_index=True)
    
    for index2,row2 in enumerate(collections.select().where(collections.publisher_id == row.id)):
        list_buffer73 = [datetime.datetime.now(), row2.id, row2.publisher_id, original_one.id]
        df_buffer73 = pd.Series(list_buffer73, index = df_csv73.columns)
        df_csv73 = df_csv73.append(df_buffer73, ignore_index=True)

    collections.update(publisher_id = original_one.id).where(collections.publisher_id == row.id).execute()
    publishers.delete().where(publishers.id == row.id).execute()

  else:
    
    list_buffer71 = [datetime.datetime.now(), row.id, row.name, row.name[:-4]]
    
    df_buffer71 = pd.Series(list_buffer71, index = df_csv71.columns)
    df_csv71  = df_csv71.append(df_buffer71, ignore_index=True)

    publishers.update(name = row.name[:-4]).where(publishers.id == row.id).execute()

print("export to csv...") 
if(not os.path.isfile(csv71_file_name)):
    df_csv71.to_csv(csv71_file_name, index=False)
else:
    df_csv71.to_csv(csv71_file_name, mode='a', header=False, index=False)
if(not os.path.isfile(csv72_file_name)):
    df_csv72.to_csv(csv72_file_name, index=False)
else:
    df_csv72.to_csv(csv72_file_name, mode='a', header=False, index=False)      
if(not os.path.isfile(csv73_file_name)):
    df_csv73.to_csv(csv73_file_name, index=False)
else:
    df_csv73.to_csv(csv73_file_name, mode='a', header=False, index=False)
print("done.")