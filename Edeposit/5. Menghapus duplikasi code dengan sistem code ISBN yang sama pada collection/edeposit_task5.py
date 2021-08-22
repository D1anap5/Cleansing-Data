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

#######################################################################################
#### 5. select duplikasi judul dan ISBN yang sama pada pemantauan dan pengelolaan ####
#######################################################################################
colums_list = ['code', 'id', 'duplicate_id', 'status', 'duplicate_status', 'title', 'duplicate_title', 'created_at', 'duplicate_created_at']
df_csv = pd.DataFrame(columns=colums_list)
colums_list_collec_categories = ['id', 'collection_id', 'category_id']
df_csv_collec_categories = pd.DataFrame(columns=colums_list_collec_categories)
colums_list_collec_contributors = ['id', 'collection_id', 'contributors_id', 'authors_id']
df_csv_collec_contributors = pd.DataFrame(columns=colums_list_collec_contributors)
colums_list_collec_media = ['id', 'collection_id', 'deleted_link', 'delete_status']
df_csv_collec_media = pd.DataFrame(columns=colums_list_collec_media)
colums_list_collec_problems = ['id', 'collection_id', 'problem_id']
df_csv_collec_problems = pd.DataFrame(columns=colums_list_collec_problems)
colums_list_collec_requests = ['id', 'collection_id', 'request_letter', 'delete_status']
df_csv_collec_requests = pd.DataFrame(columns=colums_list_collec_requests)
colums_list_collec_subjects = ['id', 'collection_id', 'subject_id']
df_csv_collec_subjects = pd.DataFrame(columns=colums_list_collec_subjects)

query = (
            collections
            .select(
                collections.id, 
                collections.code, 
                collections.title, 
                fn.COUNT(collections.code).alias('code_count'))
            .where( 
                collections.type == 1,
                collections.code != None,
                collections.code_type == 1,
            )
            .group_by(collections.code)
            .order_by(fn.COUNT(collections.code).desc(), collections.code)
            .having(fn.COUNT(collections.code) > 1)
        )

for index, row in tqdm(enumerate(query)):
    main_id_buffer         = ""
    main_code_buffer       = ""
    main_title_buffer      = ""
    main_created_at_buffer = ""
    main_status_buffer     = 0
    main_receivedby        = None
    collection_buffer_list = []
    for index2, row2 in enumerate(collections.select().where(collections.code == row.code).order_by(collections.created_at.asc())):
        collection_buffer_list.append(row2)
        if(index2 == 0 ):
            pass
        else:
            if(main_status_buffer != 2 and row2.status == 2):
                pass
            else:
                if(main_receivedby == None and row2.received_by != None):
                    pass
                else:
                    continue
        main_id_buffer         = row2.id
        main_status_buffer     = row2.status
        main_code_buffer       = row2.code
        main_title_buffer      = row2.title
        main_created_at_buffer = row2.created_at
        main_received_by       = row2.received_by

    for index2, row2 in enumerate(collection_buffer_list):
        if(row2.id == main_id_buffer):
            continue
        list_buffer = [ main_code_buffer, main_id_buffer, row2.id, main_status_buffer, row2.status,
                        main_title_buffer, row2.title, main_created_at_buffer, row2.created_at ]
        df_buffer = pd.Series(list_buffer, index = df_csv.columns)
        df_csv = df_csv.append(df_buffer, ignore_index=True)

        for index3, row3 in enumerate(collection_media.select().where(collection_media.collection_id == row2.id)):
            delete_status = 0
            try:
                buff_link_folder = row3.link[:row3.link.index(str(row3.collection_id))+len(str(row3.collection_id))+1]
                # print(buff_link_folder)
                try:
                    shutil.rmtree(buff_link_folder)
                    delete_status = 1
                except FileNotFoundError:
                    delete_status = 0
            except:
                buff_link_folder = row3.link
                # print(row3.link)
                try:
                    os.remove(row3.link)
                    delete_status = 1
                except FileNotFoundError:
                    delete_status = 0
            list_buffer = [row3.id, row3.collection_id, buff_link_folder, delete_status]
            df_buffer = pd.Series(list_buffer, index = df_csv_collec_media.columns)
            df_csv_collec_media = df_csv_collec_media.append(df_buffer, ignore_index=True)
        
        for index3, row3 in enumerate(collection_categories.select().where(collection_categories.collection_id == row2.id)):
            list_buffer =   [row3.id, row3.collection_id, row3.category_id]
            df_buffer = pd.Series(list_buffer, index = df_csv_collec_categories.columns)
            df_csv_collec_categories = df_csv_collec_categories.append(df_buffer, ignore_index=True)
        for index3, row3 in enumerate(collection_contributors.select().where(collection_contributors.collection_id == row2.id)):
            list_buffer =   [row3.id, row3.collection_id, row3.contributor_id, row3.author_id]
            df_buffer = pd.Series(list_buffer, index = df_csv_collec_contributors.columns)
            df_csv_collec_contributors = df_csv_collec_contributors.append(df_buffer, ignore_index=True)
        for index3, row3 in enumerate(collection_problems.select().where(collection_problems.collection_id == row2.id)):
            list_buffer =   [row3.id, row3.collection_id, row3.problem_id]
            df_buffer = pd.Series(list_buffer, index = df_csv_collec_problems.columns)
            df_csv_collec_problems = df_csv_collec_problems.append(df_buffer, ignore_index=True)
        for index3, row3 in enumerate(collection_subjects.select().where(collection_subjects.collection_id == row2.id)):
            list_buffer =   [row3.id, row3.collection_id, row3.subject_id]
            df_buffer = pd.Series(list_buffer, index = df_csv_collec_subjects.columns)
            df_csv_collec_subjects = df_csv_collec_subjects.append(df_buffer, ignore_index=True)
        for index3, row3 in enumerate(collection_requests.select().where(collection_requests.collection_id == row2.id)):
            delete_status = 0
            try:
                buff_link_folder = row3.request_letter[:row3.request_letter.index(str(row3.collection_id))+len(str(row3.collection_id))+1]
                try:
                    shutil.rmtree(buff_link_folder)
                    delete_status = 1
                except FileNotFoundError:
                    delete_status = 0
            except:
                buff_link_folder = row3.request_letter
                try:
                    os.remove(row3.request_letter)
                    delete_status = 1
                except FileNotFoundError:
                    delete_status = 0
            list_buffer =   [row3.id, row3.collection_id, buff_link_folder, delete_status]
            df_buffer = pd.Series(list_buffer, index = df_csv_collec_requests.columns)
            df_csv_collec_requests = df_csv_collec_requests.append(df_buffer, ignore_index=True)

        #----------------#
        # cascade delete #
        #----------------#
        collection_media.delete().where(collection_media.collection_id == row2.id).execute()
        collection_categories.delete().where(collection_categories.collection_id == row2.id).execute()
        collection_contributors.delete().where(collection_contributors.collection_id == row2.id).execute()
        collection_problems.delete().where(collection_problems.collection_id == row2.id).execute()
        collection_requests.delete().where(collection_requests.collection_id == row2.id).execute()
        collection_subjects.delete().where(collection_subjects.collection_id == row2.id).execute()
        collection.delete().where(collection.id == row2.id).execute()

print("export to csv...")
filename ='5. Duplikasi ISBN yang sama pada pemantauan dan pengelolaan.csv'
if(not os.path.isfile(filename)):
    df_csv.to_csv(filename, index=False)
else:
    df_csv.to_csv(filename, mode='a', header=False, index=False)
filename ='5.a. Deleted collection category.csv'
if(not os.path.isfile(filename)):
    df_csv_collec_categories.to_csv(filename, index=False)
else:
    df_csv_collec_categories.to_csv(filename, mode='a', header=False, index=False)
filename ='5.b. Deleted collection contributor.csv'
if(not os.path.isfile(filename)):
    df_csv_collec_contributors.to_csv(filename, index=False)
else:
    df_csv_collec_contributors.to_csv(filename, mode='a', header=False, index=False)
filename ='5.c. Deleted collection media.csv'
if(not os.path.isfile(filename)):
    df_csv_collec_media.to_csv(filename, index=False)
else:
    df_csv_collec_media.to_csv(filename, mode='a', header=False, index=False)
filename ='5.d. Deleted collection problem.csv'
if(not os.path.isfile(filename)):
    df_csv_collec_problems.to_csv(filename, index=False)
else:
    df_csv_collec_problems.to_csv(filename, mode='a', header=False, index=False)
filename ='5.e. Deleted collection subject.csv'
if(not os.path.isfile(filename)):
    df_csv_collec_subjects.to_csv(filename, index=False)
else:
    df_csv_collec_subjects.to_csv(filename, mode='a', header=False, index=False)
filename ='5.f. Deleted collection request.csv'
if(not os.path.isfile(filename)):
    df_csv_collec_requests.to_csv(filename, index=False)
else:
    df_csv_collec_requests.to_csv(filename, mode='a', header=False, index=False)
print("done.")