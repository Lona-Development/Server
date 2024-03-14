#!/bin/bash
clear
echo "[Installer for LonaDB Server]"

sudo apt-get update -y
sudo apt-get upgrade -y
sudo apt-get install php-common php-cli php-dev -y

wget https://lona-development.org/download/LonaDB-4.5.0-stable.phar
wget https://lona-development.org/download/start.sh

sudo chmod 777 start.sh

clear
echo "[Installed for LonaDB Server]"
echo "Done! Start by using ./start.sh"