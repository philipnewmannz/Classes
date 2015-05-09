<?php
# ---------------------------------------------------- #
# FILE: Authenticate.php		       		     	   #
# ---------------------------------------------------- #
# DEVELOPER: PHILIP J. NEWMAN  (Primal Media Limited)  #
# DEVELOPER: http://www.primalmedia.co.nz/			   #
# ---------------------------------------------------- #
# VERSION 0.0.12 - Updated: 09/05/2015	     	  	   #
# ---------------------------------------------------- #

# THIS CLASS PROVIDES METHODS FOR SELECT, INSERT AND UPDATE
# INTO THE WEBSITE'S DATABASE.  ITEMS THAT ARE DELETED
# SHOULD HAVE THE 'DELETED' FIELD UPDATED WITH '1'
# SCRIPT (c) PRIMAL MEDIA LIMITED

# 0.0.1 - Added clear session to the login box. Will remove anything not required when user logs in.
# 0.0.2 - Added session cookie login and updated logout to remove session login cookie.
# 0.0.3 - Added method for updating access_logins table when a member logs in.
# 0.0.4 - Added 'mActive' to Last_Access_Info() function sql.
# 0.0.5 - Added a block so that mActive is not updated if mActive = N (New account unverified)
# 0.0.6 - Updated default return for website login to root page.
# 0.0.7 - Added site version to Set_Session_Settings so we'll know if we need to update anything on users account.
# 0.0.8 - Set DNS logging default to 0.
# 0.0.9 - Added 'aLoginsBrowser' to 'access_logins' table and updated Last_Login_Info() to add new info.
# 0.0.10 - Added 'aLoginsMobile' to to 'access_logins' table and updated Last_Login_Info() and allow it to be pushed though the authenticate process.
# 0.0.11 - Added 'mAccessGroup' to 'members' table.  Saved as 'siteAccessGroup' in the session.
# 0.0.12 - Changed from MD5 passwords to crypty passwords. Table names changed from members and members_data to users and usermeta. Field name changes.

// These constants should be set in the applications config file.

if (!defined('LOGIN_USING')) {
// load defult items.
    define('LOGIN_USING' , 'email,username');
}

if (!defined('LOGIN_DISPLAY')) {
	define('LOGIN_DISPLAY' , 'username');
}

if (!defined('SESSION_COOKIE')) {
	define('SESSION_COOKIE' , 'session_cookie');
}

if (!defined('DNS_LOGGING')) {
	define('DNS_LOGGING' , '0');
}

include_once ("Database.php");

class Authenticate extends Database {

	/** 
	 * Define the vars that we'll be carrying around this class.
	 * 
	 * @param 
	 * @return 
	 */

	public $_auth;
	
	public $_dnslogging;
	
	public $_dnshost;
	
	public $_token;
	
	public $_mobile;
	
	public $_session;
	
	public $group;
	
	public $userid;
	
	/** 
	 * Process a login using HTTP $_POST.
	 * 
	 * @param input is array $input.
	 * @return true with $_auth['error'], true
	 */
		
		public function Website_Login($input=array()) {
		// make sure the username is all right.
			$input['username'] = str_replace(" ", "", $input['username']);
			
			if (!isset($input['type'])) {
				$input['type'] = "website";
			} // END if
			
			if (!isset($input['remember'])) {
				$input['remember'] = "0";
			} // END if
			
			if (!isset($input['mobile'])) {
				$input['mobile'] = "0";
			} // END if
			
		// push data into array.
			$push = array(
			  	'username'=>strtolower($input['username']),
			  	'password'=>$input['password'],
			  	'type'=>$input['type'],
			  	'remember'=>$input['remember'],
			  	'mobile'=>$input['mobile'],
			  	'return'=>'/home'
			);
			
			if ($this->Login($push) === true) {
				return true;
			} else {
				$this->_auth['error'] = "login_fail";
				return false;
			}
		
		} // END Website_Login
		
	/** 
	 * Process a login using $_COOKIE
	 * 
	 * @param input is array $input.
	 * @return true with $_auth['error'], true
	 */
		
		public function Cookie_Login($input=array()) {
		// make sure the username is all right.
			#if (!isset($input['login_key'])) {
				#return false;
			#}
			
			#if (!isset($input['type'])) {
				#$input['type'] = "session";
			#}
			
		// get the user id.	
			#$sql = "SELECT users.uId,users.uUserName, access_logins.aLoginsMobile
			#		FROM access_logins
			#		LEFT JOIN users 
			#			ON users.uId = access_logins.aLoginsMember
			#		WHERE access_logins.aLoginsSession = '".$input['login_key']."'";
					
			#$user_data = $this->mysql_Query($sql);
			
			#if (isset($user_data[0]['mUserId'])) {
			// lets log a user in.
				#$user_data = $user_data[0];
			
			// create push array.
				#$push = array(
				 # 'username'=>strtolower($user_data['mUserName']),
				  #'password'=>$user_data['mPassWord'],
				  #'type'=>$input['type'],
				  #'mobile'=>$user_data['aLoginsMobile']);
				#
				#if ($this->Login($push) === true) {
				#	return true;
				#} else {
				#	$this->_auth['error'] = "login_fail";
				#	return false;
				#}
			
			#} // END IF.
			
		} // END Cookie_Login
		
	/** 
	 * Check the username and password against the databse.
	 * 
	 * @param input is array $input.
	 * @flag $input['type'] can be set to: both, email, name.
	 * @return with true, false.
	 */
		
		public function Login($input=array()) {
		// check incoming data.
		
		// clear all rows before we try login.
			$this->mysql_ClearRows();
			
		// clear the session.
			$_SESSION = array();
		
		// lets check the database for a result.
			$sql = "SELECT user_id,user_active,user_password,user_email,user_name,user_access_level,user_access_group 
					FROM users WHERE ";
			
			$sql .= $this->Login_Using($input); // 'LOGIN_USING' defined in the config file.
			
			$sql .= "AND (user_active = 'Y' OR user_active = 'H' OR user_active = 'N') 
					 LIMIT 1";

			$login_data = $this->mysql_Query($sql);
			
		// do we have the right information to authenticate?
			if (isset($login_data[0]['user_password']) AND isset($input['password'])) {
			// check the password matches.
				if (crypt($input['password'], $login_data[0]['user_password']) == $login_data[0]['user_password']) {
				// password matches.
					$login_info = $login_data[0];

				// user information that has been grabbed from the database.
					$_SESSION["siteUserId"] 	 = $login_info["user_id"];
					$_SESSION["siteUserActive"]  = $login_info["user_active"];
					$_SESSION['siteUserName'] 	 = $login_info["user_name"];
					$_SESSION["siteAccessLevel"] = $login_info["user_access_level"];
					$_SESSION["siteAccessGroup"] = $login_info["user_access_group"];
					
					$this->userid 				 = $login_info["user_id"];
					$this->group 				 = $login_info["user_access_group"];
					
				// set is mobile.
					if (isset($input['mobile']) AND !empty($input['mobile'])) {
						$_SESSION['siteMobile']  = $input['mobile'];
					} // END if
					
				// update last access info.
					if (isset($login_info['user_active']) AND $login_info['user_active'] != 'N') {
					// only update database if active != N
						$this->Last_Access_Info($login_info["user_id"]);
						$this->Last_Login_Info($login_info["user_access_group"],$login_info["user_id"]);
					
					} // END if
					
				// what do we need to display.
					if (LOGIN_DISPLAY == "email") {
						$_SESSION["siteDisplayName"] = $login_info["user_email"];
					} else {
						$_SESSION["siteDisplayName"] = $login_info["user_name"];
					}
				
				// add the LOGIN_CODE to the session needed to check login status.
					$_SESSION["siteLoginCheck"] = LOGIN_CODE;
					
				// if we are level five (admin) then load the admin code.
					if ($login_info["mAccessLevel"] == '1') {
						$_SESSION["siteAdminCheck"] = ADMIN_CODE;
					}
					
				// load other vers into the session that are used around the site.
					$_SESSION["siteRemoteAddress"] = $_SERVER["REMOTE_ADDR"];
					$_SESSION["siteUserOnline"] = "true";
					$_SESSION["siteSessionCount"] = 1;
					$_SESSION['siteMessages'] = 0;
					$_SESSION['siteNotifications'] = 0;
					
				// remember user to auto log them in.
					if ($input["remember"] == '1') {
						$this->Set_Cookie();
					} // END if
					
				// set up session settings.
					$this->Set_Session_Settings($login_info["user_id"]);
					
				// add session to objects
					$this->_session = $_SESSION;
					
					return true;
				
				} else {
				// password does not match.
					return false;
					
				} // END if	
				
			} else {
			// password does not match.
				return false;
				
			} // END if
			
		} // END Login
		
	/** 
	 * This is where we swich the method for login.
	 * 
	 * @LOGIN_USING must me set to 'username' or 'email'.  You can also set them
	 * @to allow both 'username,email' in the website config file.
	 * @return with $loginusing sql code.
	 */
		
		public function Login_Using($input=array()) {
		// get the items.
			$item = explode(",", str_replace(" ", "", LOGIN_USING));
			
			foreach ($item AS $value) {
			// create the correct veriables.
				if ($value == "email") {
				// set email in use.
					$email = "true";
				} elseif ($value == "username") {
					$username = "true";
				} else {
				// load the defult
					$email = "true";
					$username = "true";
				}
			}
			
		// lets start the feedback.
			if ((isset($email) AND $email == "true") AND (isset($username) AND $username = "true")) {
			// login using both username and email address.	
				$output = "(user_email = '".$input['username']."' OR user_name = '".$input['username']."') ";
			} elseif (isset($email) AND $email == "true") {
			// login using the email only.
				$output = "user_email = '".$input['username']."' ";
			} elseif (isset($username) AND $username == "true") {
			// login using the username only.
				$output = "user_name = '".$input['username']."' ";
			} else {
			// login using both username and email address.	
				$output = "(user_email = '".$input['username']."' OR user_name = '".$input['username']."') ";
			}
			
			return $output;
			
		} // END Login_Using
		
	/** 
	 * Get the users settings from the database and add them to the session array.
	 * 'sitePhoto' and 'siteVersion' have been added.
	 * 
	 * @param $userid
	 * @return true or false
	 */
		
		public function Set_Session_Settings($userid=0) {
		// lets go a head and send this update to the database.
			if (empty($userid)) {
				return false;
			} // END if
			
			$this->mysql_ClearRows();
			
			$sql = "SELECT meta_type, meta_value FROM usermeta WHERE (meta_type LIKE 'setting_%' OR meta_type LIKE 'image_tiny') AND meta_user = '".$userid."'";
			$settings = $this->mysql_Query($sql);
			
			foreach ($settings AS $key=>$item) {
			// lets load the vars we need to run this account.
				if (isset($item['meta_type']) AND $item['meta_type'] == 'image_tiny') {
					$_SESSION['sitePhoto'] = $item['meta_value'];
				} else if (isset($item['meta_type']) AND $item['meta_type'] == 'setting_version') {
					$_SESSION['siteVersion'] = $item['meta_value'];
				} 	 // END if		
			
			} // END foreach
			
			return true;
					
		} // END Set_Session_Settings
	
	/** 
	 * This is where we'll kill sessions and cookies for this website.
	 * 
	 * @no param needed
	 * @return with true when complete.
	 */
		
		public function Logout() {
		// unset all cookies.
			if (isset($_SERVER['HTTP_COOKIE'])) {
				$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
				foreach($cookies as $cookie) {
					$parts = explode('=', $cookie);
					$name = trim($parts[0]);
					setcookie($name, '', time()-1000);
					setcookie($name, '', time()-1000, '/');
				} // END foreach
			
			} // end if
			
		// unset session cookie.
			setcookie(SESSION_COOKIE, '', time()-1000, "/", ".".$_SERVER['HTTP_HOST']);
			
		// unset any basic auth
			unset($_SERVER['PHP_AUTH_PW']);
			unset($_SERVER['PHP_AUTH_USER']);
			
		// unset session array.
			$_SESSION = array();
			$_COOKIE = array();
			
		// return home.
			return true;
		
		} // END Logout
		
	/** 
	 * Set a cookie that will enable user to 'autologin' next time they visit.
	 * 
	 * @no param needed
	 * @return with true when complete.
	 */
		
		private function Set_Cookie() {
		// if remember me is true create the cookie.
			$session_id = session_id();
			
			if (empty($session_id)) {
				return false;
			}

			$setmycookie = setcookie(SESSION_COOKIE, $session_id, time()+60*60*24*30, "/", ".".$_SERVER['HTTP_HOST']);
			
			if ($setmycookie === TRUE) {
				return true;
			} else { 
				return false;
			}
					
		} // END Set_Cookie
		
	/** 
	 * Update last accessed information within the 'members' table.
	 * 'mUpdated' and 'mUpdatedIp' should be updated with every page view.
	 * 'mActive' is also updated to 'Y' should be good standing for every account.
	 * 
	 * @param $userid, $date, $ip
	 * @return with true when complete or false on error.
	 */
		
		public function Last_Access_Info($userid=0) {
		// lets go a head and send this update to the database.
			if (empty($userid)) {
				return false;
			}
			
			$sql = "UPDATE users 
					SET user_active = 'Y', user_updated = now(), 
					    user_ip = '".$_SERVER['REMOTE_ADDR']."' 
					WHERE user_id = '".$userid."' 
					LIMIT 1";
					
			$this->mysql_Query($sql);
			
			return true;
					
		} // END Last_Access_Info
		
	/** 
	 * Insert last login information within the 'access_logins' table.
	 * This is to be used with the session key for remembering a users login details.
	 * 
	 * @param $userid.
	 * @return with true when complete or false.
	 */
		
		public function Last_Login_Info($accountid=0,$userid=0) {
		// lets go a head and send this update to the database.
			if (empty($userid)) {
				return false;
			} // END if
			
			if (defined('DNS_LOGGING')) {
				$this->_dnslogging = DNS_LOGGING;
			} else {
				$this->_dnslogging = 0;
			} // END if
						
			if (!empty($this->_dnslogging)) {
				$this->_dnshost = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			}
			
		// are we a mobile device?
			if (isset($_SESSION['siteMobile'])) {
				$this->_mobile = $_SESSION['siteMobile'];
			} else {
				$this->_mobile = 0;
			} // END if
			
			$sql = "INSERT INTO access_logins (login_id,login_date,login_account,login_user,login_ip,login_host,login_mobile,login_browser,login_session,login_token) 
					VALUES (NULL, CURRENT_TIMESTAMP, '%s','%s','%s','%s','%s','%s','%s','%s')";
			
			$sql = sprintf(
				$sql,
				$accountid,
				$userid,
				$_SERVER['REMOTE_ADDR'],
				$this->_dnshost,
				$this->_mobile,
				$this->mysql_EscapeString($_SERVER['HTTP_USER_AGENT']),
				session_id(),
				$this->_token
			);
				
			$this->mysql_Query($sql);
			
			#print_r($this);
			#echo $sql; exit;
			
			return true;
					
		} // END Last_Access_Info
		
} // END Authenticate

?>