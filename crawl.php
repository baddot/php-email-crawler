 <?php
 
  
 /**
  * Crawl - Email Web Crawler
  *
  * Copyright (C) 2012-2013 Jan Hendrik van Essen <cowthinker@gmail.com>
  *	https://github.com/icysheep <<>> http://cowthink.net
  *
  * This program is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
  * 
  */
 
    echo "==================================================\r\n";
    echo " Welcome to Crawl 1.01 \r\n";
    echo "==================================================\r\n";
    echo " Make sure cURL is activated for best perfomance  \r\n";
    echo "==================================================\r\n";
    if(!isset($argv[2])) {
        echo " Usage: php ".$argv[0]." HOST r_Level\r\n";
        echo " Example: php ".$argv[0]." http://www.theverge.com/ 3\r\n";
        echo "==================================================\r\n";
        exit();
    }
    else {
        echo " Working... time depends on recursion level\r\n";
        echo "==================================================\r\n";
        $start = new Crawl($argv[1], 0, $argv[2], array());
        $start->printResult($start->start());
        exit();
    }
	
class Crawl{
	
    /**
     * Constructor
     * @param string $arg1, int $arg2, int $arg3, string $arg4
     */
    public function __construct($arg1, $arg2, $arg3, $arg4) {
        if(!$this->isCli()) die("Please use php-cli!");
        $this->hp = $arg1;
        $this->rlevel = $arg2;
        $this->rmax = $arg3;
        $this->mails = $arg4;
    }
 
    /**
     * Check if you use the php command line to run this script
     * @return boolean
     */
    private function isCli() {
        return php_sapi_name()==="cli";
    }
	
    /**
     * Get the content of the current page ($this->hp)
     * @return string
     */
    public function getContent() {
        if (!function_exists('curl_init')){
            $content=file_get_contents($this->hp, false, $this->getContext());
        } 
		else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->hp);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
        }
		return $content;
    }
  
    /**
     * Get the context to open the FGC socket
     * @return stream_context_resource
     */
    private function getContext() {
        $opts = array(
            'http' => array(
                'method'=>"GET",
                'header'=>"Content-Type: text/html; charset=utf-8"
            )
        );
        return stream_context_create($opts);
    }
	
    /**
     * Use the content to create an email array
     * Make sure we don't save the same email address multiple times
     * @return array
     */
    public  function getEmailArray() {
        $email_pattern_normal="(([-_.\w]+@[a-zA-Z0-9_]+?\.[a-zA-Z0-9]{2,6}))";
		$email_pattern_exp1="(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,3})";
        preg_match_all($email_pattern_normal, $this->content, $result_email_normal, PREG_PATTERN_ORDER);
		preg_match_all($email_pattern_exp1, $this->content, $result_email_exp1, PREG_PATTERN_ORDER);
        $email_array=array_merge($result_email_normal, $result_email_exp1);
		$unique_emails=$this->array_unique_deep($email_array);
        return $unique_emails;
    }
 
    /**
     * Deletes duplicate values on multi dimensional arrays
     * @return array
     */
    private function array_unique_deep($array) {
        $values=array();
        foreach ($array as $part) {
            if (is_array($part)) {
				$values=array_merge($values,$this->array_unique_deep($part));
			}
            else { 
				$values[]=$part;
			}
        }
        return array_unique($values);
    }
    
    /**
     * Fetch URLs from the current site to use them later (recursion)
     * Make sure to delete duplicate entries
     * @return array
     */
    public function getURLArray() {
	    $url_pattern='((\:href=\"|(http(s?))\://){1}\S+)';
        preg_match_all($url_pattern, $this->content, $result_url, PREG_PATTERN_ORDER);
	    array_walk($result_url[0], function(&$item) { $item = substr($item, 0, strpos($item, '"')); });
        $unique_urls=$this->array_unique_deep($result_url[0]);
        $unique_urls=array_unique($this->setURLPrefix($unique_urls));
        return $unique_urls;
    }
 
    /**
     * A little function to set www/http prefixes
     * @param URL-Array $array
     * @return array
     */
    private function setURLPrefix($array) {
        $prefix_array=array();
        $i=0;
        foreach ($array as $part) {
            if(preg_match('/^(www\.)/', $part)) $prefix_array[$i]='http://'.$part;
            else $prefix_array[$i]=$part;
            $i++;
        }
        return $prefix_array;
    }
	
	/**
	 * Temporarily function to print the result
	 */
	public function printResult($data) {
		print_r($data);
	}
	
    /**
     * Start function with recursion
     * Creates new instances depending on recursion depth
     * Merges the obtained email addresses and returns them
     * @return mails
     */
    public function start() {
       if($this->rlevel<$this->rmax) {
           $this->content = $this->getContent();
           $this->urls = $this->getURLArray();
           $mails = $this->getEmailArray();
           foreach($this->urls as $url) {
              $temp = new Crawl($url, $this->rlevel+1, $this->rmax, $this->mails);
              $this->mails = array_unique(array_merge($temp->start(), $mails));
           }
       }
       return $this->mails;
    }
}
	
?>

