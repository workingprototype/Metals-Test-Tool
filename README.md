STEPS to install

Install laragon, setup the software such that the port is 90 and root is in C:/laragon/www with the folder name "metals" inside it.

Open Laragon settings, Enable auto-run and auto-start both apache and mysql on startup

Copy the file config.json into the metals folder like this : C:/laragon/www/metals/config.json

After the application is installed, When you start the application, it copies the files to the directory: C:/laragon/www/metals/ overwriting everything except the config.json file which has the API keys for fast2sms, whatsapp and the zoom level for the testreportform page.

Database file is stored in the same root directory. Might need to be accessed from elsewhere and configured first.

Remote machine needs to be only configured with the config.json file having the remote server's IP address instead of localhost and the username,password of the laragon db.