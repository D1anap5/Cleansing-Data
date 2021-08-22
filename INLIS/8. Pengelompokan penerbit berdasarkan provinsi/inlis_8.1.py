import cx_Oracle
import pandas as pd
from tqdm import tqdm
import os

connection = cx_Oracle.connect(
    user="INLIS",
    password="admin",
    dsn="localhost/XE")
    
print("Successfully connected to Oracle Database")
cursor1 = connection.cursor()
cursor2 = connection.cursor()
cursor_update = connection.cursor()

csv_file_name = "8.1 Updated MASTER_PUBLISHER province menggunakan data REGION.csv"

colums_list = ['PUBLISHER_ID', 'CITY', 'REGION', 'PROVINCE']
df_csv = pd.DataFrame(columns=colums_list)

# Now query the rows back
for row in tqdm(cursor1.execute('select * from MASTER_PUBLISHER Where PROVINCE is NULL and NOT REGION = \'all\'')):
  query_string ='select PROVINCE from MASTER_PUBLISHER Where PROVINCE is not NULL and REGION like \'%'+ row[7]+'%\' and ROWNUM <= 1'
  valid_province = cursor2.execute(query_string)
  for row2 in valid_province:
    list_buffer = [row[0],row[6], row[7], row2[0]]
    df_buffer = pd.Series(list_buffer, index = df_csv.columns)
    df_csv = df_csv.append(df_buffer, ignore_index=True)
    break
  query_string = "update MASTER_PUBLISHER SET PROVINCE = '" + str(row2[0]) + "' where PUBLISHER_ID = " + str(row[0])
  cursor_update.execute(query_string)
print("committing changes...")
connection.commit()
print("export to csv...")
if(not os.path.isfile(csv_file_name)):
    df_csv.to_csv(csv_file_name, index=False)
else:
    df_csv.to_csv(csv_file_name, mode='a', header=False, index=False)
print("done.")