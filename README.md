# Backup & Restore for WordPress

You know the problem, you've created a website backup using a WordPress backup plugin and you can't do the the restore via the WP dashboard because your website is broken. Using this backup tool you're able to create a backup outside WordPress and access the tool again if you need to restore the website. We use the tool to create backups if we install a new theme / plugin or before we do some updates.

### How does it work?

The backup tool makes a copy from you WordPress website and stores the files in a directory with a hashed name. A database dump is stored in the same directory. During the restore function the files from the backup are moved back to the original location. Next the database dump is placed back as well.

### Test the tool first!
This tool is using the Linux command line tool "rsync" to copy all the files. We're using the tool for years and it works perfect on all our web servers. It might break your site during the restore, so test it first on a test site.

### Security
The tool doesn't store any database logins and all files are stored in a directory with a hashed name. If you protect the "mybackup" directory, your files should be pretty safe.

## Features
* Super fast, a backup from a 500MB website takes only seconds!
* Backup with a single mouse click (full or partly backups)
* Exclude themes, plugins or media files
* Restore even if the WordPress website doesn't work anymore
* Protect the "mybackup" directory with your IP address or with username and password
* Delete old backups with a single click
* Place notes with every backup

## Installation

Download the files as a zip or via the GIT tools on your server. Place/upload the files into a directory named "mybackup" and place it into the website's public folder.

### Installation snippet for ManageWP users

Use this snippet if you use ManageWP. Just run the code and don't forget to protect the directory after Installation.

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


## Disclaimer

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
