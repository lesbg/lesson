#!/bin/sh
rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@www:/www-data/html/secure/lesson
ssh -l root www "cd /www-data/html/secure/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp apache ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson; ln -s /networld/system_data/tftpboot/linux-install netboot"
#rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@www.lest.loc:/var/www/lesson
#ssh -l root www.lest.loc "cd /var/www/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp www-data ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson;"
