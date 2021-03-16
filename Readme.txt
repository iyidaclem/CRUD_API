Copy the v1 folder into the htdocs folder for apache (make sure the .htaccess file is stored within the v1 folder).

Open phpMyAdmin, from the Home screen click the import tab.
Under file to import click the choose file and select the file tasksdb_withuserandimage.sql under the SQL folder.
Then Click Go
The new tasksdb database should now have been created.

The set up is now done and you should be able to test the tasks API using Postman using the endpoints mentioned in the course, e.g. http://localhost:8888/v1/tasks

The TestFiles folder contains a separate readme.txt file which you should read if you want to use these files (they test the functionality of the task, response models as well as the database connection controller (they are optional).

Make sure you also create the taskimages folder one directory outside of the web server root folder (e.g. htdocs folder).