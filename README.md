# Backup4WP

You know the problem, you've created a backup for your website using a WordPress backup plugin and your WordPress website breaks after an update. Next you can't restore your website to the previous version because you can't access the WP dashboard anymore.
Using this backup tool you're able to create a backup outside WordPress. Access the tool again if you need to restore the website. We use the tool to create backups if we install a new theme / plugin or before we do some (smaller) updates.

> [!TIP]
> Now it is also possible to install AND update Backup4WP via the WordPress dashboard. [Download the WordPress plugin](https://backup4wp.com/wordpress-plugin/) from our website and install the recent version of Backup4WP with a simple mouse click.

### How does it work?
The backup tool makes a copy from your WordPress website and saves the files in a directory outside the public directory. A database dump is saved in the same directory. During the restore function the files from the backup are moved back to the original location. The database dump is used to restore the database as well.

### Test the tool first!
This tool is using the Linux command line tool "rsync" to copy all the files. We're using the tool for years and it works perfect on all our web servers. It might break your website during a restore action, so try the backup tool first on a similar test/staging site.

### Security
The tool doesn't store any database logins and all files are saved in a directory which is not accessible via the public website. To access the Backup4WP tool, you need to authorize via a link that is send to your own email address. The (cookie) session expires after 4 hours of inactivity.

If your web host is based on Apache, there is an option to protect the directory using a login/password or by white-listing your IP address.

## Features
* Super fast, a backup from a 500MB website takes only seconds!
* Optional: Download your backups and use the ZIP file for the site import in Local (by Flywheel)
* Quick setup, using email credentials from existing plugins like [Maileroo](https://maileroo.com/?r=backupforwp) or WP Mail SMTP
* Apache server users can authenticate via login/password or IP address
* Backup with a single mouse click (full or partly backups)
* Exclude themes, plugins or media files
* Restore your site even if the WordPress website doesn't work anymore
* Delete old backups with a single click
* Place notes with every backup

## Installation

Using Composer, just run the following code within the public HTML directory from your WordPress website:

```
composer create-project finalwebsites/backup4wp:dev-master mybackup
```

Replace the directory name "mybackup" with your unique name, if you like.

The best and easiest way to install Backup4WP is by using the WordPress plugin. You can download the plugin via the [Backup4WP website](https://backup4wp.com/).


## Update notes

*30 December*
The Maileroo PHP SDK is replaced and will be installed now via Composer. 

*9th November 2025*
In some cases, the settings for Maileroo and Mailersend weren't saved correctly. This has now been resolved. Error messages about invalid variables during a new installation have also been removed.
Whenever you perform an action (backup, restore, or delete), a popup will now appear showing the progress.

*6th August 2025*
Added email support using the Maileroo API, [Maileroo](https://maileroo.com/?r=backupforwp) is a very affordable transactional email provider with many features, even in the free version!
The Update function is gone. Use the Backup4WP WordPress plugin for a similar update feature.
To support an automatic authentication feature via the WordPress plugin, the cookie domain value is valid now for the site root.

*15th March 2025*
Text sanitization updates to prevent "deprecated warnings" in PHP 8.2

*17th November 2024*
Added a simple update function. If you install Backup4WP via Composer, you can update the backup tool via the update page (link in the menu).
The log-out link is fixed and redirets to the login page now.

*5th January 2024*
Inside the function get_db_conn_vals() there was a check for the existens of enviroment variabels. This old check worked only with some "rare" configurations. The check is replaced and the phpdotenv class is used to read the .env file from a website. 

*18th March 2023*
In this version we replaced the Sendgrid email option with [MailerSend](https://www.mailersend.com?ref=lol81qb1dqe0). Sendgrid changed their offer and the free option with 12.000 monthly emails isn't available anymore (for new accounts). In place of the Sendgrid API, you can use the Mailersend API. Their free version has also 3.000 emails per month, but they offer also other options, Sendgrid doesn't offer. If you still prefer Sendgrid, you can still use Backup4WP while using the SMTP email option.

*4th November 2022*
In some situations there was a PHP memory error while reading the database backup in the restore mode. We fixed it by reading the file line by line using fgets() instead of the file() function.

*11th September 2022*
We did several updates and bug fixes for the login function and the options page. In the past it doesn't worked well during the setup, if some setting wasn't correct. These should be fixed now. There is also also a log out function available now. We advise to update the application immediately to prevent yourself from future problems. You can keep you current database and files, only the files from the application are modified.

*1st May 2022*
First release v1.1.0, from today on we're using release tags. Do you like to use Composer? Than is this update for you. We packaged Backup4WP and you're able to install the tool using Composer. The PHPMailer, Sendgrid and Mysqldump library are not included in our distribution anymore. Don't worry for the manual installation, we offer a ZIP file with all the library files included. During the installation, you can choose the directory name. Instead of "mybackup", you can use your own name. This makes it a bit more safe if you choose a random name. From our prospective it's safe to update the application for  installations from the last year.

*17th April 2022*
If your WordPress website is using the **Easy SMTP plugin**, Backup4WP will recognize these settings too. Plus, if you use an API key from Sendgrid as a password for this SMTP plugin, the API key is also pre-filled inside the "Sendgrid" section. We changed also the order on how the obtained settings are used: 1. Easy SMTP, 2. WP Mail SMTP and 3. the old Sendgrid plugin.

*3rd April 2022*
If you like to download your backups, it's necessary now to set the constant variable to "true" inside the file "func.php". Most people doesn't need the download feature. This modification is temporary solution until we find a better way to do this on the options page.

*19th March 2022*
The email sender name is now the domain name. That makes it easier to recognize the email message in your inbox if you use the Backup4WP tool for multiple websites.

*30th January 2021*
The directory structure for single backup is changed. The site files are stored in a separate directory now. **Remove old backups before you update to the latest version of Backup4WP. The restore function will not work with old backups.** Beside the new directory structure, we added a new feature: Downloads. Now you can download your entire backup as one ZIP file. The structure of the ZIP file is compatible with the site import function in Local (by Flywheel).

*12th December 2020*
Some users doesn't like the authorization via the magic link. That's the reason that we've placed the "old" Apache authorization method back to the backup tool. If you enable the Apache authorization option, the email based method isn't used anymore.

*14th September 2020*
We changed the options page that users can use their SMTP server credentials now. To keep those settings in Backup4WP, we added several columns to the table "backupsettings". To get the update you need to replace the whole "mybackup" directory (keep your "backups" directory!) and visit the main page. The function "update_mybackup()" will add the missing database table columns. Optionally, add your own SMTP server credentials.

## Credits
* [Mysqldump by Diego Torres](https://github.com/ifsnop/mysqldump-php)
* [MailerSend PHP SDK](https://github.com/mailersend/mailersend-php/)
* [PHPMailer - The classic email sending library for PHP](https://github.com/PHPMailer/PHPMailer)
* [phpdotenv](https://github.com/vlucas/phpdotenv)
