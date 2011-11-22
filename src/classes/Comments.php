<?php
/**
 * This file implements the class Comments.
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * This file is part of PhotoShow.
 *
 * PhotoShow is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PhotoShow is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PhotoShow.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Website
 * @package   Photoshow
 * @author    Thibaud Rohmer <thibaud.rohmer@gmail.com>
 * @copyright 2011 Thibaud Rohmer
 * @license   http://www.gnu.org/licenses/
 * @link      http://github.com/thibaud-rohmer/PhotoShow-v2
 */

/**
 * Comments
 *
 * Implements the creating, reading, editing, and
 * displaying of the comments, from and to an xml
 * file.
 * The file is stored in 
 * [Thumbs]/[imagepath]/.[image]_comments.xml
 * Comments Structure:
 * - Comment (multiple) contain:
 * 		- Login
 * 		- Date
 * 		- Content
 * 
 * @category  Website
 * @package   Photoshow
 * @author    Thibaud Rohmer <thibaud.rohmer@gmail.com>
 * @copyright Thibaud Rohmer
 * @license   http://www.gnu.org/licenses/
 * @link      http://github.com/thibaud-rohmer/PhotoShow-v2
 */
class Comments implements HTMLObject
{
	/// Array of the comments
	private $comments=array();
	
	/// Path to item
	private $file;
	
	/// Path to comments file
	private $commentsfile;

	/// Urlencoded version of relative path to item
	private $webfile;
	
	/**
	 * Read comments for item $file
	 *
	 * @param string $file 
	 * @author Thibaud Rohmer
	 */
	public function __construct($file=null){
		
		/// No item, no comment !
		if(!isset($file)) return;
		
		/// Comments are only supported for Images... who said "for now" ?
		if(File::Type($file) != "Image")
			throw new Exception("$file is not an image");
		
		/// Set variables
		$this->file	=	$file;
		$settings	=	new Settings();
		$basefile	= 	new File($file);
		$basepath	=	File::a2r($file);

		/// Urlencode basepath
		$this->webfile = urlencode(File::a2r($file));

		/// Build relative path to comments file
		$comments	=	dirname($basepath)."/.".$basefile->name."_comments.xml";
		
		/// Set absolute path to comments file
		$this->commentsfile =	File::r2a($comments,Settings::$thumbs_dir);
		
		/// Check that comments file exists
		if(file_exists($this->commentsfile)){
			$this->parse_comments_file();
		}
	}

	/**
	 * Add a comment for item $file
	 * 
	 * @param string $file
	 * @param string $login
	 * @param string $comment
	 * @author Thibaud Rohmer
	 */
	public static function add($file,$content,$login=""){
		
		if($login == ""){
			if(isset(CurrentUser::$account)){
				$login = CurrentUser::$account->login;
			}else{
				$login = "Anonymous";
			}
		}

		/// Get existing comments
		$comments = new Comments($file);

		/// Create new comment
		$new_comm =	new Comment($login,$content,date('j-m-y, h-i-s'));

		/// Append comment
		$comments->comments[] = $new_comm;
		$comments->save();
	}

	public function save(){
		
		$xml = new SimpleXMLElement("<comments></comments>");

		/// Treat each of the comments
		foreach ($this->comments as $comment){
			$c = $xml->addChild("comment");
			$c->addChild("login"	, $comment->login);
			$c->addChild("date"		, $comment->date);
			$c->addChild("content"	, $comment->content);
		}

		if(!file_exists(dirname($this->commentsfile))){
			mkdir(dirname($this->commentsfile),0750,true);
		}
		/// Write xml
		$xml->asXML($this->commentsfile);
	}


	/**
	 * Read contents of comments file, and
	 * store comments in the comments array
	 *
	 * @return void
	 * @author Thibaud Rohmer
	 */
	private function parse_comments_file(){
		$xml		=	simplexml_load_file($this->commentsfile);
		
		foreach( $xml as $comm ){
			$this->comments[]=new Comment((string)$comm->login,(string)$comm->content,(string)$comm->date);
		}
	}
	
	/**
	 * Display comments on website
	 *
	 * @return void
	 * @author Thibaud Rohmer
	 */
	public function toHTML(){
		echo "<div id='comments' class='box'>";
		
		/// Display each comment
		foreach($this->comments as $com){
			$com->toHTML();
		}
		echo "<div id='comments_form_div'>\n";
			echo "<form action='?t=Com&f=".$this->webfile."' id='comments_form' method='post'>\n";
				if(!isset(CurrentUser::$account)){					
					echo "<div class='label'>Name</div>\n";
					echo "<input type='text' name='login' id='login'>\n";					
				}
				echo "<div class='label'>Comment</div>\n";
				echo "<textarea name='content' id='content'></textarea>\n";
				echo "<input type='submit' value='Send' class='button blue'>\n";
			echo "</form>\n";
		echo "</div>\n";
			
		echo "</div>";
	}
}

?>