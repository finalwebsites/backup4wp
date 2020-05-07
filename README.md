# MyBackup for WordPress

You know the problem, you've created a backup for your website using a WordPress backup plugin and your WordPress website breaks after an update. Next you can't restore your website to the previous version because you can't access the WP dashboard anymore.
Using this backup tool you're able to create a backup outside WordPress. Access the tool again if you need to restore the website. We use the tool to create backups if we install a new theme / plugin or before we do some (smaller) updates.

### How does it work?

The backup tool makes a copy from your WordPress website and stores the files in a directory outside the public directory. A database dump is also stored in the same directory. During the restore function the files from the backup are moved back to the original location. The database dump is used to restore the database as well.

### Test the tool first!
This tool is using the Linux command line tool "rsync" to copy all the files. We're using the tool for years and it works perfect on all our web servers. It might break your site during the restore, so try the backup tool first on a similar test site.

### Security
The tool doesn't store any database logins and all files are stored in a directory which is not accessible via the public website. To access the MyBackup tool, you need to authorize via a link that is send to your own email address. The session expires 4 hours of activity.

## Features
* Super fast, a backup from a 500MB website takes only seconds!
* Backup with a single mouse click (full or partly backups)
* Exclude themes, plugins or media files
* Restore your site even if the WordPress website doesn't work anymore
* Delete old backups with a single click
* Place notes with every backup

## Installation

Download the files as a zip or via the GIT tools on your server. Place/upload the files into a directory named "mybackup" and place it into the website's public folder. Access the tool and enter your email address and enter you Sendgrid API key. Confirm your email address via the link you get in your mailbox.

### Installation snippet for ManageWP users

Use this snippet if you use ManageWP. Just run the code and access the tool and finish the "Installation".

    <?php
    $dir = dirname(dirname(dirname(__DIR__))).'/mybackup';
    if (file_exists($dir)) {
    	echo 'The MyBackup tool already exists!';
    } else {
    	$url = 'https://github.com/finalwebsites/mybackup/archive/master.zip';
    	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    	$result = curl_exec ($ch);
    	if (curl_errno($ch)) {
    		die('Curl error: ' . curl_error($ch));
    	}
    	curl_close ($ch);
    	if ($success = file_put_contents('master.zip', $result)) {
    		echo 'Downloaded zip file ('.$success.' bytes)';
    		exec('unzip master.zip && mv mybackup-master mybackup');
    		unlink('master.zip');
    	} else {
    		echo 'Error while downloading zip file';
    	}
    }


## Credits
* [Mysqldump by Diego Torres](https://github.com/ifsnop/mysqldump-php)
* [PHP library for the Sendgrid API v3](https://github.com/sendgrid/sendgrid-php/)

## Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
