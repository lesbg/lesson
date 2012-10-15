#!/bin/sh
# LESL Test
#rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@www.lesbg.loc:/www-data/secure/lesson-test
#ssh -l root www.lesbg.loc "cd /www-data/secure/lesson-test/; patch -p1 < /srv/lesson-config-1.patch; chgrp apache ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R;"

# LESL
rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@www.lesbg.loc:/www-data/secure/lesson
ssh -l root www.lesbg.loc "cd /www-data/secure/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp apache ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson; ln -s /networld/system_data/netboot netboot;"
rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@lesloueizeh.com:/var/www/html/lesson
ssh -l root lesloueizeh.com "cd /var/www/html/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp apache ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R;"

# LEST
#rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@www.lest.loc:/var/www/lesson
#ssh -l root www.lest.loc "cd /var/www/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp www-data ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson;"
#rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@tyre.lestyre.org:/var/www/lesson
#ssh -l root tyre.lestyre.org 'cd /var/www/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp www-data ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson; service apache2 restart;'

# LESAZ
#rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson/ root@www.lesaz.loc:/var/www/html/lesson
#ssh -v -l root www.lesaz.loc "cd /var/www/html/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp apache ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson;"
#rsync --delete --exclude=.[a-z0-9]* -rtvzH ../lesson root@lesaz.dyndns.org:/tmp
#ssh -l root lesaz.dyndns.org "rsync --delete --exclude=.[a-z0-9]* -rtvzH /tmp/lesson/ root@www.lesaz.loc:/var/www/html/lesson"
#ssh -l root lesaz.dyndns.org "ssh -l root www.lesaz.loc 'cd /var/www/html/lesson/; patch -p1 < /srv/lesson-config-1.patch; chgrp apache ./ -Rh; chmod 640 ./ -R; chmod ug+X ./ -R; ln -s ../lesson; /etc/init.d/httpd restart;' "
