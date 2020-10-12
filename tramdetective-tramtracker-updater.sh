#!/bin/sh
(
  if flock -n 200
  then

        exec 5>&1
        OUTPUT=$(/usr/bin/php -f /var/www/html/tramdetective.wongm.com/cron.php token=bf8c6789 odds=1 length=1,2,3 &
                /usr/bin/php -f /var/www/html/tramdetective.wongm.com/cron.php token=bf8c6789 odds=1 length=4 &
                /usr/bin/php -f /var/www/html/tramdetective.wongm.com/cron.php token=bf8c6789 odds=0 length=1,2,3 &
                /usr/bin/php -f /var/www/html/tramdetective.wongm.com/cron.php token=bf8c6789 odds=0 length=4  | tee >(cat - >&5))

        if [[ $OUTPUT == *"Warning"* ]]; then
                >&2 echo $OUTPUT
                exit 1;
        fi

        exit 0

  else
        echo "Already running..."
        exit 0;
  fi
) 200>lockfile

echo $?
