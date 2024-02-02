# Installation

To install the LonaDB server, follow these steps:

## 1. Run the install script with sudo:

```bash
sudo curl -fsSL https://lona-development.org/download/install.sh | sh
```

## 2. Start the Server:

```bash
./start.sh
```

## 3. Configure everything:

On every start, you have to put in your encryption key.
If the wrong key has been provided, the configuration file cannot be read and the Server will stop instantly.

If wanted, you can change the ```php ...``` line in the start.sh file to automatically put in your encryption key:
```bash
printf "yourEncryptionKey\n" | php ...
```
But this is not recommended since it will basically make everyone be able to find your root user password, wich is stored in the config file, which is encrypted with this key.

We don't store the key by default because of security of the root user.

If you don't have a configuration file, a users table or any data tables, the Server will run you through initial setup and create everything needed for the database to run.

## 4. Use your database:

Thats it! 
You now have your own instance of LonaDB!