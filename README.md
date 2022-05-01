# Backup4WP

You know the problem, you've created a backup for your website using a WordPress backup plugin and your WordPress website breaks after an update. Next you can't restore your website to the previous version because you can't access the WP dashboard anymore.
Using this backup tool you're able to create a backup outside WordPress. Access the tool again if you need to restore the website. We use the tool to create backups if we install a new theme / plugin or before we do some (smaller) updates.

> Are you looking for the Backup4WP WordPress plugin? The WordPress plugin review team decided to decline our submission. They think that the plugin's concept doesn't follow the rules for a listing in the repository. We accept their decision and removed the plugin here from Github as well.

### How does it work?
The backup tool makes a copy from your WordPress website and stores the files in a directory outside the public directory. A database dump is also stored in the same directory. During the restore function the files from the backup are moved back to the original location. The database dump is used to restore the database as well.

### Test the tool first!
This tool is using the Linux command line tool "rsync" to copy all the files. We're using the tool for years and it works perfect on all our web servers. It might break your site during the restore, so try the backup tool first on a similar test site.

### Security
The tool doesn't store any database logins and all files are stored in a directory which is not accessible via the public website. To access the Backup4WP tool, you need to authorize via a link that is send to your own email address. The session expires 4 hours of activity.

If your web host is based on Apache, the is an option to protect the directory using a login/password or by white-listing your IP address.

## Features
* Super fast, a backup from a 500MB website takes only seconds!
* Optional: Download your backups and use the ZIP file for the site import in Local (by Flywheel)
* Quick setup, using email credentials from existing plugins like Easy SMTP, WP Mail SMTP or Sendgrid
* Apache user can authenticate via login/password or IP address
* Backup with a single mouse click (full or partly backups)
* Exclude themes, plugins or media files
* Restore your site even if the WordPress website doesn't work anymore
* Delete old backups with a single click
* Place notes with every backup

## Installation

Using Composer, just run the following code within the public HTML directory from your WordPress website:

```
composer create-project finalwebsites/backup4wp mybackup
```

Replace the directory name "mybackup" with something else, if you like.

### Manual installation

Download the the zip file here. Extract and upload the files into a directory named "mybackup" (or some other name) right into the website's public folder. Accesss the tool and enter your email address and enter your Sendgrid API key or your SMTP credentials. Confirm your email address via the link you get in your mailbox.

If you like to use the authorization feature provided by Apache, than continue to "Apache authentication" and enter your details on that page. If you need the "Download" option for your backups, you need to change the variable ENABLE_DOWNLOADS to "true" inside the file *libs > func.php*.

### Installation snippet for ManageWP users

Use this snippet if you use ManageWP. Just run the code and access the tool and finish the "Installation". **Continue with the Installation as subscribed before!**

    <?php
    $dir = dirname(dirname(dirname(__DIR__))).'/mybackup';
    if (file_exists($dir)) {
    	echo 'A "mybackup" directory already exists!';
    } else {
        exec('composer -V', $output);
        if (substr($output[0], 0, 8) == 'Composer') {
    		exec('composer create-project finalwebsites/backup4wp mybackup'); // you can use a different directory name (instead of "mybackup")
            echo 'Downloaded and installed Backup4WP using Composer';
    	} else {
    		echo 'Composer isn't supported by your web host.';
    	}
    }


## Update notes

*1st May 2022*
First release v1.1.0, from today on we're using release tags. Do you like to use Composer? Than is this update for you. We packaged Backup4WP and you're able to install the tool using Composer. The PHPMailer, Sendgrid and Mysqldump library are not included in our distribution anymore. Don't worry for the manual installation, we offer a ZIP file with all the library files included. During the installation, you can choose the directory name. Instead of "mybackup", you can use your own name. This makes it a bit more safe if you choose a random name. From our prospective it's safe to update the application for  installations from the last year.

*17th April 2022*
If your WordPress website is using the **Easy SMTP plugin**, Backup4WP will recognize these settings too. Plus, if you use an API key from Sendgrid as a password for this SMTP plugin, the API key is also pre-filled inside the "Sendgrid" section. We changed also the order on how the obtained settings are used: 1. Easy SMTP, 2. WP Mail SMTP and 3. the old Sendgrid plugin.

*3rd April 2022*
If you like to download your backups, it's necessary now to set the constant variable to "true" inside the file "func.php". Most people doesn't need the download feature. This modification is temproray solution until we find a better way to do this on the options page.

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
* [PHP library for the Sendgrid API v3](https://github.com/sendgrid/sendgrid-php/)
* [PHPMailer - The classic email sending library for PHP](https://github.com/PHPMailer/PHPMailer)
